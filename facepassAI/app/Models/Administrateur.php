<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\AdministrateurFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Sous-type STI : un Administrateur est un User dont users.role = 'administrateur'.
 */
class Administrateur extends User
{
    /** @use HasFactory<AdministrateurFactory> */
    use HasFactory;

    protected $table = 'users';

    protected static function booted(): void
    {
        static::addGlobalScope('only_administrateurs', function (Builder $query) {
            $query->where('role', Role::Administrateur->value);
        });

        static::creating(function (self $user) {
            $user->role = Role::Administrateur;
        });
    }

    protected static function newFactory(): AdministrateurFactory
    {
        return AdministrateurFactory::new();
    }
}
