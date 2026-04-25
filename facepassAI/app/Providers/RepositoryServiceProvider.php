<?php

namespace App\Providers;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Liaison Interface => Implémentation pour les Repositories.
 *
 * Tous les bindings du pattern Repository sont déclarés ici, ce qui permet
 * d'injecter des interfaces (et non des classes concrètes) dans les Services
 * et de pouvoir les remplacer facilement par des mocks dans les tests.
 *
 * À chaque nouveau Repository, ajouter une ligne dans la propriété $bindings.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        UserRepositoryInterface::class => UserRepository::class,
    ];

    public function register(): void
    {
        // Les bindings sont déclarés via la propriété $bindings ci-dessus.
    }

    public function boot(): void
    {
        //
    }
}
