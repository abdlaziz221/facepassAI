<?php

namespace App\Models;

use App\Enums\Role;
use App\Notifications\ResetPasswordFr;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * Guard utilisé par spatie/laravel-permission.
     */
    protected string $guard_name = 'web';

    /**
     * Mapping role => classe enfant (STI).
     *
     * @var array<string, class-string<User>>
     */
    protected static array $childClasses = [
        'employe'        => Employe::class,
        'consultant'     => Consultant::class,
        'gestionnaire'   => Gestionnaire::class,
        'administrateur' => Administrateur::class,
    ];

    /**
     * Attributs assignables en masse.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'est_actif',
    ];

    /**
     * Attributs cachés à la sérialisation.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'role'              => Role::class,
            'est_actif'         => 'boolean',
        ];
    }

    /**
     * Polymorphisme STI.
     * Quand Eloquent reconstruit un modèle depuis la base, on retourne
     * directement la sous-classe correspondante au rôle.
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        $role = is_object($attributes)
            ? ($attributes->role ?? null)
            : ($attributes['role'] ?? null);

        $class = static::$childClasses[$role] ?? static::class;

        if ($class !== static::class) {
            return (new $class)->newFromBuilder($attributes, $connection);
        }

        return parent::newFromBuilder($attributes, $connection);
    }

    /**
     * Override de la notification de reset (Sprint 1, US-013).
     * Utilise notre notification française ResetPasswordFr.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordFr($token));
    }
}
