<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\GestionnaireFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Sous-type STI : un Gestionnaire est un User dont users.role = 'gestionnaire'.
 */
class Gestionnaire extends User
{
    /** @use HasFactory<GestionnaireFactory> */
    use HasFactory;

    protected $table = 'users';

    protected static function booted(): void
    {
        static::addGlobalScope('only_gestionnaires', function (Builder $query) {
            $query->where('role', Role::Gestionnaire->value);
        });

        static::creating(function (self $user) {
            $user->role = Role::Gestionnaire;
        });
    }

    protected static function newFactory(): GestionnaireFactory
    {
        return GestionnaireFactory::new();
    }
}
