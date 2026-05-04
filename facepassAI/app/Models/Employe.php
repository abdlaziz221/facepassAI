<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\EmployeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Sous-type STI : un Employé est un User dont users.role = 'employe'.
 * - Pointe vers la table 'users' (même table que User)
 * - Filtre automatiquement les requêtes via un global scope
 * - Force role = 'employe' à la création
 */
class Employe extends User
{
    /** @use HasFactory<EmployeFactory> */
    use HasFactory;

    protected $table = 'users';

    protected static function booted(): void
    {
        // Filtre toutes les requêtes : Employe::all() ne renvoie que les employés.
        static::addGlobalScope('only_employes', function (Builder $query) {
            $query->where('role', Role::Employe->value);
        });

        // À la création, on force le rôle.
        static::creating(function (self $user) {
            $user->role = Role::Employe;
        });
    }

    protected static function newFactory(): EmployeFactory
    {
        return EmployeFactory::new();
    }
}
