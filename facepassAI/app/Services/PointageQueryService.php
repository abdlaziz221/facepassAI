<?php

namespace App\Services;

use App\Models\Pointage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Sprint 5 carte 1 (US-060) — Construction de la requête d'historique des pointages.
 *
 * Encapsule la logique de filtrage et de tri pour la vue d'historique
 * (utilisée par PointageController::historique() + future API rapports).
 *
 * Filtres supportés :
 *   - employe_id : un employé précis (FK EmployeProfile)
 *   - date       : un jour exact (Y-m-d) — prioritaire sur date_from/date_to
 *   - date_from  : borne basse incluse (Y-m-d)
 *   - date_to    : borne haute incluse (Y-m-d)
 *   - type       : un type parmi Pointage::TYPES
 *   - manuel     : booléen — true = pointages manuels uniquement
 *
 * Tri supporté : created_at | type. Par défaut : created_at desc.
 */
class PointageQueryService
{
    /** Colonnes autorisées pour le tri (whitelist anti-injection). */
    public const SORTABLE_COLUMNS = ['created_at', 'type', 'employe_id'];

    /**
     * Construit le Builder filtré (sans tri, sans pagination).
     *
     * @param array<string, mixed> $filters
     */
    public function query(array $filters = []): Builder
    {
        $q = Pointage::query()->with('employe.user');

        if (!empty($filters['employe_id'])) {
            $q->where('employe_id', (int) $filters['employe_id']);
        }

        if (!empty($filters['date'])) {
            // Jour exact — prioritaire sur date_from/date_to
            $q->whereDate('created_at', $filters['date']);
        } else {
            if (!empty($filters['date_from'])) {
                $q->whereDate('created_at', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $q->whereDate('created_at', '<=', $filters['date_to']);
            }
        }

        if (!empty($filters['type']) && in_array($filters['type'], Pointage::TYPES, true)) {
            $q->where('type', $filters['type']);
        }

        if (array_key_exists('manuel', $filters) && $filters['manuel'] !== null && $filters['manuel'] !== '') {
            $q->where('manuel', (bool) $filters['manuel']);
        }

        return $q;
    }

    /**
     * Applique tri + pagination sur le Builder filtré.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortDir = 'desc',
        int $perPage = 20
    ): LengthAwarePaginator {
        $sortBy  = in_array($sortBy, self::SORTABLE_COLUMNS, true) ? $sortBy : 'created_at';
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        return $this->query($filters)
            ->orderBy($sortBy, $sortDir)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Renvoie les comptes par type pour le jeu filtré.
     * Utile pour afficher des KPI synthétiques au-dessus du tableau.
     *
     * @param  array<string, mixed> $filters
     * @return array<string, int>
     */
    public function countsByType(array $filters = []): array
    {
        $raw = $this->query($filters)
            ->selectRaw('type, COUNT(*) as n')
            ->groupBy('type')
            ->pluck('n', 'type');

        return [
            Pointage::TYPE_ARRIVEE     => (int) ($raw[Pointage::TYPE_ARRIVEE]     ?? 0),
            Pointage::TYPE_DEBUT_PAUSE => (int) ($raw[Pointage::TYPE_DEBUT_PAUSE] ?? 0),
            Pointage::TYPE_FIN_PAUSE   => (int) ($raw[Pointage::TYPE_FIN_PAUSE]   ?? 0),
            Pointage::TYPE_DEPART      => (int) ($raw[Pointage::TYPE_DEPART]      ?? 0),
        ];
    }
}
