<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
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
     * Doit matcher le guard des rôles & permissions créés.
     */
    protected string $guard_name = 'web';

    /**
     * Mapping role → classe enfant (STI).
     * Utilisé par newFromBuilder() pour retourner le bon sous-type
     * quand on lit depuis la base.
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
     * Polymorphisme STI : quand Eloquent reconstruit un modèle depuis
     * la base, on retourne directement la sous-classe correspondante.
     *
     * Exemple : User::find(3) → renvoie une instance de Gestionnaire
     * si users.role = 'gestionnaire'.
     *
     * On délègue à parent::newFromBuilder pour s'assurer que $exists
     * et tous les flags Eloquent internes sont correctement positionnés
     * (sinon save() ferait INSERT au lieu d'UPDATE → conflit clé primaire).
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        $role = is_object($attributes) ? ($attributes->role ?? null)
                                       : ($attributes['role']  ?? null);

        $class = static::$childClasses[$role] ?? static::class;

        // Si la classe attendue diffère de la classe actuelle (cas
        // typique : User::find() qui doit retourner Administrateur),
        // on délègue à cette sous-classe.
        if ($class !== static::class) {
            return (new $class)->newFromBuilder($attributes, $connection);
        }

        // Sinon, comportement Eloquent standard (set exists = true, etc.)
        return parent::newFromBuilder($attributes, $connection);
    }
}
