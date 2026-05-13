<?php

namespace App\Services;

use App\Models\DemandeAbsence;
use App\Models\EmployeProfile;
use App\Models\JourFerie;
use App\Models\JoursTravail;
use App\Models\Pointage;
use Carbon\Carbon;

/**
 * Sprint 6 carte 1 (US-081) — Calcul de paie mensuelle.
 *
 * Pipeline :
 *   calculerSalaireBrut  → montant brut depuis le profil
 *   calculerDeductions   → retards + départs anticipés + absences
 *   calculerNet          → max(0, brut - total déductions)
 *
 * Hypothèses :
 *   - salaire_brut mensuel fixé sur le profil de l'employé
 *   - tarif horaire = brut / (jours ouvrables du mois × heures/jour)
 *   - jours ouvrables = ceux configurés dans JoursTravail, hors jours fériés
 *   - une absence = jour ouvrable où l'employé n'a aucun pointage et
 *     n'est pas couvert par une demande d'absence validée
 *   - les jours futurs ne comptent pas comme absence
 */
class PayrollService
{
    private const FRENCH_DAY_TO_DOW = [
        'dimanche' => 0,
        'lundi'    => 1,
        'mardi'    => 2,
        'mercredi' => 3,
        'jeudi'    => 4,
        'vendredi' => 5,
        'samedi'   => 6,
    ];

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
    // 1) Salaire brut
    // ========================================================================

    public function calculerSalaireBrut(EmployeProfile $emp): float
    {
        return (float) ($emp->salaire_brut ?? 0);
    }

    /**
     * Sprint 6 carte 4 (US-083) — Détection des données manquantes
     * qui empêchent un calcul complet de la fiche de paie.
     *
     * Retourne la liste des champs problématiques (vides) :
     *   - 'salaire_brut' : non défini ou nul (= 0)
     *   - 'matricule'    : non défini ou vide
     *
     * Si le tableau est vide, les données sont complètes.
     *
     * @return array<int, string>
     */
    public static function donneesManquantes(EmployeProfile $profile): array
    {
        $manquantes = [];

        if ($profile->salaire_brut === null || (float) $profile->salaire_brut <= 0) {
            $manquantes[] = 'salaire_brut';
        }
        if (empty(trim((string) ($profile->matricule ?? '')))) {
            $manquantes[] = 'matricule';
        }

        return $manquantes;
    }

    // ========================================================================
    // 2) Déductions (retards + départs anticipés + absences)
    // ========================================================================

    /**
     * @return array{
     *   retards: array{minutes:int, montant:float},
     *   departs_anticipes: array{minutes:int, montant:float},
     *   absences: array{jours:int, jours_detail: array<int, string>, montant:float},
     *   total: float,
     *   meta: array<string, float|int>
     * }
     */
    public function calculerDeductions(EmployeProfile $emp, int $year, int $month): array
    {
        $brut = $this->calculerSalaireBrut($emp);

        $heuresParJour    = $this->heuresParJourTheoriques();
        $joursOuvrables   = $this->joursOuvrablesDuMois($year, $month);
        $heuresMois       = $heuresParJour * count($joursOuvrables);
        $tarifHoraire     = $heuresMois > 0 ? $brut / $heuresMois : 0.0;
        $tarifMinute      = $tarifHoraire / 60;
        $tarifJournalier  = $tarifHoraire * $heuresParJour;

        // --- Pointages du mois → retards + départs anticipés
        $pointages = Pointage::where('employe_id', $emp->id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->get();

        $minutesRetard = 0;
        $minutesAnticipe = 0;
        foreach ($pointages as $p) {
            if ($this->retardService->isRetard($p->type, $p->created_at)) {
                $minutesRetard += $this->retardService->ecartEnMinutes($p->type, $p->created_at);
            } elseif ($this->retardService->isDepartAnticipe($p->type, $p->created_at)) {
                $minutesAnticipe += abs($this->retardService->ecartEnMinutes($p->type, $p->created_at));
            }
        }

        // --- Absences non justifiées
        $absencesValidees = DemandeAbsence::where('employe_id', $emp->id)
            ->where('statut', DemandeAbsence::STATUT_VALIDEE)
            ->get();

        $datesPointes = $pointages->map(fn ($p) => $p->created_at->format('Y-m-d'))->unique();
        $today = Carbon::today();

        $joursAbsenceDetail = [];
        foreach ($joursOuvrables as $jour) {
            // Pas d'absence dans le futur
            if ($jour->greaterThan($today)) {
                continue;
            }

            $key = $jour->format('Y-m-d');
            $aPointe = $datesPointes->contains($key);
            if ($aPointe) {
                continue;
            }

            $estCouvertParAbsence = $absencesValidees->contains(function ($a) use ($jour) {
                $debut = $a->date_debut instanceof Carbon ? $a->date_debut : Carbon::parse($a->date_debut);
                $fin   = $a->date_fin   instanceof Carbon ? $a->date_fin   : Carbon::parse($a->date_fin);
                return $jour->betweenIncluded($debut->startOfDay(), $fin->endOfDay());
            });

            if (!$estCouvertParAbsence) {
                $joursAbsenceDetail[] = $key;
            }
        }
        $joursAbsence = count($joursAbsenceDetail);

        // --- Calcul des montants
        $montantRetards    = round($minutesRetard   * $tarifMinute,    2);
        $montantAnticipes  = round($minutesAnticipe * $tarifMinute,    2);
        $montantAbsences   = round($joursAbsence    * $tarifJournalier, 2);
        $total             = round($montantRetards + $montantAnticipes + $montantAbsences, 2);

        return [
            'retards' => [
                'minutes' => $minutesRetard,
                'montant' => $montantRetards,
            ],
            'departs_anticipes' => [
                'minutes' => $minutesAnticipe,
                'montant' => $montantAnticipes,
            ],
            'absences' => [
                'jours'         => $joursAbsence,
                'jours_detail'  => $joursAbsenceDetail,
                'montant'       => $montantAbsences,
            ],
            'total' => $total,
            'meta'  => [
                'jours_ouvrables_mois' => count($joursOuvrables),
                'heures_par_jour'      => round($heuresParJour, 2),
                'heures_mois'          => round($heuresMois, 2),
                'tarif_horaire'        => round($tarifHoraire, 2),
                'tarif_minute'         => round($tarifMinute, 4),
                'tarif_journalier'     => round($tarifJournalier, 2),
            ],
        ];
    }

    // ========================================================================
    // 3) Net
    // ========================================================================

    public function calculerNet(EmployeProfile $emp, int $year, int $month): float
    {
        $brut = $this->calculerSalaireBrut($emp);
        $deductions = $this->calculerDeductions($emp, $year, $month);
        return round(max(0, $brut - $deductions['total']), 2);
    }

    /**
     * Récapitulatif complet — utilisé par la vue Mon Salaire.
     *
     * @return array{year:int, month:int, brut:float, deductions:array, net:float}
     */
    public function calculerSalaireMensuel(EmployeProfile $emp, int $year, int $month): array
    {
        $brut       = $this->calculerSalaireBrut($emp);
        $deductions = $this->calculerDeductions($emp, $year, $month);
        $net        = round(max(0, $brut - $deductions['total']), 2);

        return compact('year', 'month', 'brut', 'deductions', 'net');
    }

    // ========================================================================
    // Helpers
    // ========================================================================

    /** Heures effectives par jour = (départ - arrivée) - (pause). */
    public function heuresParJourTheoriques(): float
    {
        $secArr = $this->parseToSeconds($this->config->heure_arrivee);
        $secDep = $this->parseToSeconds($this->config->heure_depart);
        $secPDb = $this->parseToSeconds($this->config->heure_debut_pause);
        $secPFn = $this->parseToSeconds($this->config->heure_fin_pause);

        $brut  = max(0, $secDep - $secArr);
        $pause = max(0, $secPFn - $secPDb);
        $net   = max(0, $brut - $pause);

        return $net / 3600;
    }

    /**
     * Liste des jours ouvrables (Carbon) du mois donné, en excluant les jours
     * fériés et les jours hors jours_ouvrables configurés.
     *
     * @return array<int, Carbon>
     */
    public function joursOuvrablesDuMois(int $year, int $month): array
    {
        $noms = $this->config->jours_ouvrables ?? ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi'];
        $dows = array_filter(
            array_map(fn ($n) => self::FRENCH_DAY_TO_DOW[$n] ?? -1, $noms),
            fn ($v) => $v >= 0
        );

        $start = Carbon::create($year, $month, 1);
        $end   = $start->copy()->endOfMonth();

        $feries = JourFerie::query()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get()
            ->map(fn ($jf) => Carbon::parse($jf->date)->format('Y-m-d'))
            ->all();

        $jours = [];
        for ($d = $start->copy(); $d->lessThanOrEqualTo($end); $d = $d->copy()->addDay()) {
            if (in_array($d->dayOfWeek, $dows, true)
                && !in_array($d->format('Y-m-d'), $feries, true)) {
                $jours[] = $d->copy();
            }
        }
        return $jours;
    }

    private function parseToSeconds(?string $time): int
    {
        if (!$time) {
            return 0;
        }
        $time = substr($time, 0, 8);
        $parts = explode(':', $time);
        $h = (int) ($parts[0] ?? 0);
        $m = (int) ($parts[1] ?? 0);
        $s = (int) ($parts[2] ?? 0);
        return $h * 3600 + $m * 60 + $s;
    }
}
