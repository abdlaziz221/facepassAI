<?php

namespace App\Repositories\Contracts;

use App\Models\User;

/**
 * Contrat spécifique au Repository des utilisateurs.
 *
 * Étend BaseRepositoryInterface (CRUD générique) et ajoute les méthodes
 * propres au domaine User (recherche par email, etc.).
 */
interface UserRepositoryInterface extends BaseRepositoryInterface
{
    /** Retourne l'utilisateur ayant cet email, ou null. */
    public function findByEmail(string $email): ?User;
}
