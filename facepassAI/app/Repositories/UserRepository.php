<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

/**
 * Implémentation Eloquent du UserRepositoryInterface.
 *
 * Hérite du CRUD générique de BaseRepository et ajoute les méthodes
 * spécifiques aux utilisateurs.
 */
class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?User
    {
        /** @var User|null $user */
        $user = $this->model->newQuery()->where('email', $email)->first();

        return $user;
    }
}
