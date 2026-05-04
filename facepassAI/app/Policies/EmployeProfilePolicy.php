<?php

namespace App\Policies;

use App\Models\EmployeProfile;
use App\Models\User;

/**
 * Sprint 2, US-020 : règles d'autorisation CRUD pour les profils employés.
 *
 * Mapping :
 *   create / update / delete  →  Administrateur + Gestionnaire
 *   viewAny (liste)           →  Consultant + Gestionnaire + Administrateur
 *   view (profil spécifique)  →  Tous (mais Employé ne voit QUE le sien)
 *
 * Les autorisations s'appuient sur les permissions spatie créées dans le
 * RolePermissionSeeder (employes.view, employes.create, employes.update,
 * employes.delete) — voir Sprint 1, tâche 4.
 *
 * Auto-découverte Laravel 12 : la policy est associée à EmployeProfile par
 * convention de nommage (App\Policies\EmployeProfilePolicy).
 */
class EmployeProfilePolicy
{
    /**
     * Voir la liste de tous les profils employés.
     * Refusé pour les Employés (ils ne voient que leur propre profil).
     */
    public function viewAny(User $user): bool
    {
        return $user->can('employes.view');
    }

    /**
     * Voir un profil employé spécifique.
     *
     * - Un Employé peut TOUJOURS voir son propre profil
     * - Sinon, il faut la permission employes.view (consultant+, gest+, admin)
     */
    public function view(User $user, EmployeProfile $profile): bool
    {
        if ($user->id === $profile->user_id) {
            return true;
        }

        return $user->can('employes.view');
    }

    /**
     * Créer un nouvel employé : Gestionnaire + Administrateur uniquement.
     */
    public function create(User $user): bool
    {
        return $user->can('employes.create');
    }

    /**
     * Modifier un profil employé : Gestionnaire + Administrateur uniquement.
     */
    public function update(User $user, EmployeProfile $profile): bool
    {
        return $user->can('employes.update');
    }

    /**
     * Supprimer un profil employé : Gestionnaire + Administrateur uniquement.
     */
    public function delete(User $user, EmployeProfile $profile): bool
    {
        return $user->can('employes.delete');
    }

    /**
     * Restaurer un profil supprimé (soft delete, à venir Sprint 2 T8).
     */
    public function restore(User $user, EmployeProfile $profile): bool
    {
        return $user->can('employes.delete');
    }

    /**
     * Suppression définitive (admin uniquement).
     */
    public function forceDelete(User $user, EmployeProfile $profile): bool
    {
        return $user->hasRole('administrateur');
    }
}
