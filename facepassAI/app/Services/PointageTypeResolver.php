<?php

namespace App\Services;

use App\Models\EmployeProfile;
use App\Models\Pointage;

/**
 * Détermine le type de pointage attendu pour un employé donné, basé sur
 * la séquence de ses pointages déjà enregistrés aujourd'hui.
 *
 * Séquence normale d'une journée de travail :
 *   arrivee → debut_pause → fin_pause → depart
 *
 * Sprint 4, US-033.
 *
 * TODO Sprint 5 (JoursTravail) :
 *   - Vérifier que le pointage est dans la plage horaire prévue (par ex.
 *     pas d'arrivée avant 06:00, pas de départ avant 12:00, etc.)
 *   - Vérifier que l'employé est censé travailler ce jour-là
 *   - Calculer les retards et départs anticipés
 */
class PointageTypeResolver
{
    /**
     * Retourne le prochain type de pointage attendu pour cet employé,
     * ou null si la journée est déjà terminée (4 pointages faits).
     */
    public function nextExpectedType(EmployeProfile $employe): ?string
    {
        $todayTypes = $this->getTodayTypes($employe);

        if (empty($todayTypes)) {
            return Pointage::TYPE_ARRIVEE;
        }

        $last = end($todayTypes);

        return match ($last) {
            Pointage::TYPE_ARRIVEE     => Pointage::TYPE_DEBUT_PAUSE,
            Pointage::TYPE_DEBUT_PAUSE => Pointage::TYPE_FIN_PAUSE,
            Pointage::TYPE_FIN_PAUSE   => Pointage::TYPE_DEPART,
            Pointage::TYPE_DEPART      => null,   // journée terminée
            default                    => null,
        };
    }

    /**
     * Vérifie si un type donné est valide pour cet employé maintenant.
     * Par exemple : si l'employé a déjà fait son arrivée, isValidNext()
     * retournera false pour 'arrivee' mais true pour 'debut_pause'.
     */
    public function isValidNext(EmployeProfile $employe, string $type): bool
    {
        return $this->nextExpectedType($employe) === $type;
    }

    /**
     * Indique si la journée de l'employé est terminée (4 pointages faits).
     */
    public function dayCompleted(EmployeProfile $employe): bool
    {
        return $this->nextExpectedType($employe) === null;
    }

    /**
     * Récupère la liste des types de pointage déjà faits aujourd'hui,
     * dans l'ordre chronologique d'enregistrement.
     *
     * @return array<int, string>
     */
    protected function getTodayTypes(EmployeProfile $employe): array
    {
        return Pointage::query()
            ->where('employe_id', $employe->id)
            ->whereDate('created_at', today())
            ->orderBy('created_at')
            ->pluck('type')
            ->toArray();
    }
}
