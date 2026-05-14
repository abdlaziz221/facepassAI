<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\DemandeAbsence;
use App\Models\EmployeProfile;
use App\Models\Pointage;
use App\Models\User;
use App\Services\DashboardKpiService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Aiguille l'utilisateur vers son dashboard selon son rôle (Sprint 1, US-016).
 *
 * Sprint 6 cartes 10/11/12 (US-100/101/102) — Enrichi avec :
 *   - $kpi     : compteurs clés du jour (présents, retards, absents, demandes)
 *   - $charts  : données pour Chart.js (courbe 30j + camembert statuts)
 *   - $alertes : alertes priorisées par gravité
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $role = $user->role;

        // KPIs globaux (composition des comptes)
        $stats = [
            'total_users'   => User::count(),
            'admins'        => User::where('role', Role::Administrateur)->count(),
            'gestionnaires' => User::where('role', Role::Gestionnaire)->count(),
            'consultants'   => User::where('role', Role::Consultant)->count(),
            'employes'      => User::where('role', Role::Employe)->count(),
        ];

        // Sprint 6 cartes 10/11/12 — KPI métier + charts + alertes
        $kpiService = DashboardKpiService::fromCurrent();
        $kpi    = $kpiService->kpiCards();
        $charts = [
            'presences30'     => $kpiService->presencesParJour(30),
            'statutsAbsences' => $kpiService->repartitionStatutsAbsences(),
        ];
        $alertes = $kpiService->alertes();

        // KPI personnels pour l'employé connecté
        $employeKpi = null;
        if ($role === Role::Employe) {
            $employeKpi = $this->computeEmployeKpi($user);
        }

        $payload = compact('user', 'stats', 'kpi', 'charts', 'alertes', 'employeKpi');

        return match ($role) {
            Role::Administrateur => view('dashboards.administrateur', $payload),
            Role::Gestionnaire   => view('dashboards.gestionnaire',   $payload),
            Role::Consultant     => view('dashboards.consultant',     $payload),
            default              => view('dashboards.employe',        $payload),
        };
    }

    /**
     * Calcule les KPI personnels affichés sur le dashboard d'un employé.
     *
     * @return array{
     *   statut: string, statut_label: string, statut_detail: ?string,
     *   heures_mois: int, jours_pointes_mois: int,
     *   absences_validees: int, solde_conges: int, jours_pris: int
     * }
     */
    private function computeEmployeKpi(User $user): array
    {
        $profile = EmployeProfile::where('user_id', $user->id)->first();

        $default = [
            'statut'             => 'absent',
            'statut_label'       => 'Pas pointé',
            'statut_detail'      => null,
            'heures_mois'        => 0,
            'jours_pointes_mois' => 0,
            'absences_validees'  => 0,
            'solde_conges'       => 30,
            'jours_pris'         => 0,
        ];

        if (!$profile) {
            return $default;
        }

        $today        = Carbon::today();
        $startOfMonth = Carbon::today()->startOfMonth();

        // --- Statut du jour
        $todayPointages = Pointage::where('employe_id', $profile->id)
            ->whereDate('created_at', $today)
            ->orderBy('created_at')
            ->get();

        $statut       = 'absent';
        $statutLabel  = 'Pas pointé';
        $statutDetail = null;

        if ($todayPointages->isNotEmpty()) {
            $depart = $todayPointages->firstWhere('type', Pointage::TYPE_DEPART);
            if ($depart) {
                $statut       = 'sorti';
                $statutLabel  = 'Journée terminée';
                $statutDetail = 'Parti à ' . $depart->created_at->format('H:i');
            } else {
                $arrivee = $todayPointages->firstWhere('type', Pointage::TYPE_ARRIVEE);
                $statut  = 'present';
                $statutLabel = 'Présent';
                if ($arrivee) {
                    $statutDetail = 'Pointage à ' . $arrivee->created_at->format('H:i');
                }
            }
        }

        // --- Jours pointés ce mois (jours avec au moins une arrivée)
        $joursPointesMois = Pointage::where('employe_id', $profile->id)
            ->where('type', Pointage::TYPE_ARRIVEE)
            ->whereBetween('created_at', [$startOfMonth, Carbon::now()])
            ->selectRaw('COUNT(DISTINCT DATE(created_at)) as n')
            ->value('n');

        // Heures estimées = jours pointés × 8h (heures théoriques par jour)
        $heuresMois = (int) $joursPointesMois * 8;

        // --- Absences validées cette année
        $absencesValidees = DemandeAbsence::where('employe_id', $profile->id)
            ->where('statut', DemandeAbsence::STATUT_VALIDEE)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        // --- Solde de congés (simplifié : 30 j/an - jours pris)
        $joursPris = DemandeAbsence::where('employe_id', $profile->id)
            ->where('statut', DemandeAbsence::STATUT_VALIDEE)
            ->whereYear('date_debut', Carbon::now()->year)
            ->get()
            ->sum(fn ($d) => $d->date_debut->diffInDays($d->date_fin) + 1);
        $soldeConges = max(0, 30 - $joursPris);

        return [
            'statut'             => $statut,
            'statut_label'       => $statutLabel,
            'statut_detail'      => $statutDetail,
            'heures_mois'        => $heuresMois,
            'jours_pointes_mois' => (int) $joursPointesMois,
            'absences_validees'  => $absencesValidees,
            'solde_conges'       => $soldeConges,
            'jours_pris'         => $joursPris,
        ];
    }
}
