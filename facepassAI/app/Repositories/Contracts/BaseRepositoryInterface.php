<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Contrat de base pour tous les Repositories.
 *
 * Un Repository encapsule l'accès à la couche de persistance (Eloquent).
 * Les Services et Controllers ne doivent JAMAIS appeler directement le
 * modèle Eloquent : ils passent toujours par un Repository qui implémente
 * cette interface.
 */
interface BaseRepositoryInterface
{
    /** Retourne tous les enregistrements. */
    public function all(): Collection;

    /** Retourne une page de résultats. */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /** Retourne un enregistrement par sa clé primaire ou null. */
    public function find(int|string $id): ?Model;

    /** Retourne un enregistrement par sa clé primaire ou lève ModelNotFoundException. */
    public function findOrFail(int|string $id): Model;

    /**
     * Crée un enregistrement.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model;

    /**
     * Met à jour un enregistrement par son id.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int|string $id, array $data): Model;

    /** Supprime un enregistrement par son id. */
    public function delete(int|string $id): bool;
}
