<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

/**
 * Service métier pour les utilisateurs.
 *
 * Convention :
 *   - Les Services contiennent la LOGIQUE MÉTIER (règles, orchestration).
 *   - Les Services NE TOUCHENT PAS directement à Eloquent : ils délèguent
 *     la persistance à un Repository injecté via le constructeur.
 *   - Les Controllers ne contiennent PAS de logique métier : ils délèguent
 *     au Service approprié et se contentent de la réponse HTTP.
 */
class UserService
{
    public function __construct(
        protected UserRepositoryInterface $users,
    ) {
    }

    /**
     * Crée un utilisateur en hashant le mot de passe au passage.
     *
     * @param  array{name:string,email:string,password:string}  $data
     */
    public function register(array $data): User
    {
        $data['password'] = Hash::make($data['password']);

        return $this->users->create($data);
    }

    /** Vérifie qu'un email n'est pas déjà utilisé. */
    public function emailIsAvailable(string $email): bool
    {
        return $this->users->findByEmail($email) === null;
    }
}
