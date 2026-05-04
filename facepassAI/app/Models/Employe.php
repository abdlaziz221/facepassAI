<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\EmployeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Sous-type STI : un Employé est un User dont users.role = 'employe'.
 *
 * - Pointe vers la table 'users' (même que User)
 * - Filtre auto via global scope
 * - Force role = 'employe' à la création
 * - Sprint 2 : relation hasOne vers EmployeProfile (matricule, poste, salaire)
 */
class Employe extends User
{
    /** @use HasFactory<EmployeFactory> */
    use HasFactory;

    protected $table = 'users';

    protected static function booted(): void
    {
        // Filtre automatique : Employe::all() ne renvoie que les employés.
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

    /**
     * Profil métier de l'employé (matricule, poste, département,
     * salaire brut, photo faciale). Sprint 2, US-020.
     */
    public function profile(): HasOne
    {
        return $this->hasOne(EmployeProfile::class, 'user_id');
    }
}
