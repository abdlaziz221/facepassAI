<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\DemandeAbsence;
use App\Models\EmployeProfile;
use App\Models\JoursTravail;
use App\Models\Pointage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Sprint 6 cartes 10/11/12 (US-100/101/102) — KPIs, charts et alertes du dashboard.
 */
class DashboardKpiService
{
    public function __construct(
        public readonly RetardService $retardService,
        public readonly JoursTravail $config,
    ) {
    }

    public static function fromCurrent(): self
    {
        return new self(RetardService::fromCurrent(), JoursTravail::current());
    }

    // ========================================================================
    // Carte 10 (US-100) — KPI cards du jour
    // ========================================================================

    /** Nombre d'employés ayant au moins un pointage 'arrivee' aujourd'hui. */
    public function presentsAujourdhui(): int
    {
        return Pointage::query()
            ->whereDate('created_at', Carbon::today())
            ->where('type', Pointage::TYPE_ARRIVEE)
            ->distinct('employe_id')
            ->count('employe_id');
    }

    /** Nombre de pointages 'arrivee' en retard aujourd'hui. */
    public function retardsAujourdhui(): int
    {
        $arrivees = Pointage::query()
            ->whereDate('created_at', Carbon::today())
            ->where('type', Pointage::TYPE_ARRIVEE)
            ->get();

        return $arrivees->filter(
            fn ($p) => $this->retardService->isRetard($p->type, $p->created_at)
        )->count();
    }

    /**
     * Nombre d'employés actifs n'ayant aucun pointage 'arrivee' aujourd'hui
     * ET pas de demande d'absence validée couvrant aujourd'hui.
     */
    public function absentsAujourdhui(): int
    {
        if (!$this->estJourOuvre(Carbon::today())) {
            return 0;
        }

        $totalEmployes = EmployeProfile::count();
        $presents = $this->presentsAujourdhui();

        // Soustraire les employés couverts par une absence validée aujourd'hui
        $employesEnConge = DemandeAbsence::query()
            ->where('statut', DemandeAbsence::STATUT_VALIDEE)
            ->whereDate('date_debut', '<=', Carbon::today())
            ->whereDate('date_fin', '>=', Carbon::today())
            ->distinct('employe_id')
            ->count('employe_id');

        return max(0, $totalEmployes - $presents - $employesEnConge);
    }

    public function demandesEnAttente(): int
    {
        return DemandeAbsence::where('statut', DemandeAbsence::STATUT_EN_ATTENTE)->count();
    }

    /**
     * Renvoie le bloc de 4 KPI clés pour le dashboard.
     *
     * @return array{
     *   presents:int, total_employes:int, retards:int, absents:int,
     *   demandes_en_attente:int, taux_presence:float
     * }
     */
    public function kpiCards(): array
    {
        $totalEmployes = EmployeProfile::count();
        $presents = $this->presentsAujourdhui();
        $taux = $totalEmployes > 0 ? round($presents / $totalEmployes * 100, 1) : 0;

        return [
            'presents'            => $presents,
            'total_employes'     => $totalEmployes,
            'retards'             => $this->retardsAujourdhui(),
            'absents'             => $this->absentsAujourdhui(),
            'demandes_en_attente' => $this->demandesEnAttente(),
            'taux_presence'       => $taux,
        ];
    }

    // ========================================================================
    // Carte 11 (US-101) — Données pour graphiques Chart.js
    // ========================================================================

    /**
     * Nombre d'employés présents par jour sur les N derniers jours.
     *
     * @return array{labels: array<int,string>, data: array<int,int>}
     */
    public function presencesParJour(int $nbJours = 30): array
    {
        $start = Carbon::today()->subDays($nbJours - 1);

        // Une requête groupée pour éviter N+1
        $rows = Pointage::query()
            ->selectRaw('DATE(created_at) as jour, COUNT(DISTINCT employe_id) as n')
            ->where('type', Pointage::TYPE_ARRIVEE)
            ->whereDate('created_at', '>=', $start)
            ->groupBy('jour')
            ->pluck('n', 'jour');

        $labels = [];
        $data = [];
        for ($d = $start->copy(); $d->lessThanOrEqualTo(Carbon::today()); $d->addDay()) {
            $key = $d->format('Y-m-d');
            $labels[] = $d->format('d/m');
            $data[] = (int) ($rows[$key] ?? 0);
        }

        return compact('labels', 'data');
    }

    /**
     * Répartition des demandes d'absence par statut (sur la période).
     *
     * @return array{labels: array<int,string>, data: array<int,int>}
     */
    public function repartitionStatutsAbsences(?int $year = null, ?int $month = null): array
    {
        $q = DemandeAbsence::query();
        if ($year !== null) {
            $q->whereYear('created_at', $year);
        }
        if ($month !== null) {
            $q->whereMonth('created_at', $month);
        }

        $rows = $q->selectRaw('statut, COUNT(*) as n')
            ->groupBy('statut')
            ->pluck('n', 'statut');

        return [
            'labels' => ['En attente', 'Validées', 'Refusées'],
            'data'   => [
                (int) ($rows[DemandeAbsence::STATUT_EN_ATTENTE] ?? 0),
                (int) ($rows[DemandeAbsence::STATUT_VALIDEE]    ?? 0),
                (int) ($rows[DemandeAbsence::STATUT_REFUSEE]    ?? 0),
            ],
        ];
    }

    // ========================================================================
    // Carte 12 (US-102) — Alertes priorisées
    // ========================================================================

    /**
     * Renvoie les alertes du moment, triées par gravité décroissante.
     *
     * Chaque alerte : { level: 'high'|'medium'|'low', icon, title, message, url? }
     *
     * @return Collection<int, array>
     */
    public function alertes(): Collection
    {
        $alertes = collect();

        // Demandes en attente
        $pending = $this->demandesEnAttente();
        if ($pending >= 5) {
            $alertes->push([
                'level'   => 'high',
                'icon'    => '⚠',
                'title'   => "{$pending} demandes d'absence en attente",
                'message' => 'Plusieurs employés attendent une réponse — traitez-les rapidement.',
                'url'     => 'demandes-absence.index',
            ]);
        } elseif ($pending > 0) {
            $alertes->push([
                'level'   => 'medium',
                'icon'    => '📋',
                'title'   => "{$pending} demande(s) d'absence en attente",
                'message' => 'À valider ou refuser.',
                'url'     => 'demandes-absence.index',
            ]);
        }

        // Retards du jour
        $retards = $this->retardsAujourdhui();
        if ($retards >= 5) {
            $alertes->push([
                'level'   => 'high',
                'icon'    => '⏰',
                'title'   => "{$retards} retards aujourd'hui",
                'message' => 'Nombre inhabituel — vérifiez les pointages.',
                'url'     => 'pointages.retards',
            ]);
        } elseif ($retards > 0) {
            $alertes->push([
                'level'   => 'medium',
                'icon'    => '⏰',
                'title'   => "{$retards} retard(s) aujourd'hui",
                'message' => 'Consultez la liste des anomalies.',
                'url'     => 'pointages.retards',
            ]);
        }

        // Horaires non configurés
        if (!$this->config->isConfigured()) {
            $alertes->push([
                'level'   => 'medium',
                'icon'    => '⚙',
                'title'   => 'Horaires non configurés',
                'message' => "Les calculs utilisent les valeurs par défaut (8h-17h). Configurez-les.",
                'url'     => 'admin.horaires.edit',
            ]);
        }

        // Aucun gestionnaire actif
        $gestActifs = User::where('role', Role::Gestionnaire->value)
            ->where('est_actif', true)->count();
        if ($gestActifs === 0) {
            $alertes->push([
                'level'   => 'high',
                'icon'    => '👤',
                'title'   => 'Aucun gestionnaire actif',
                'message' => 'Les demandes d\'absence ne pourront pas être traitées. Créez un compte gestionnaire.',
                'url'     => 'admin.gestionnaires.index',
            ]);
        }

        // Tri par priorité décroissante (high > medium > low)
        $priority = ['high' => 3, 'medium' => 2, 'low' => 1];
        return $alertes->sortByDesc(fn ($a) => $priority[$a['level']] ?? 0)->values();
    }

    // ========================================================================
    // Helpers
    // ========================================================================

    private function estJourOuvre(Carbon $date): bool
    {
        $noms = $this->config->jours_ouvrables ?? ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi'];
        $mapping = [
            'dimanche' => 0, 'lundi' => 1, 'mardi' => 2, 'mercredi' => 3,
            'jeudi' => 4, 'vendredi' => 5, 'samedi' => 6,
        ];
        $dows = array_map(fn ($n) => $mapping[$n] ?? -1, $noms);
        return in_array($date->dayOfWeek, $dows, true);
    }
}
