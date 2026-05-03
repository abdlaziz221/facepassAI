<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\ConsultantFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Sous-type STI : un Consultant est un User dont users.role = 'consultant'.
 */
class Consultant extends User
{
    /** @use HasFactory<ConsultantFactory> */
    use HasFactory;

    protected $table = 'users';

    protected static function booted(): void
    {
        static::addGlobalScope('only_consultants', function (Builder $query) {
            $query->where('role', Role::Consultant->value);
        });

        static::creating(function (self $user) {
            $user->role = Role::Consultant;
        });
    }

    protected static function newFactory(): ConsultantFactory
    {
        return ConsultantFactory::new();
    }
}
