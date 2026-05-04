<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Gestionnaire;
use Database\Factories\Concerns\SenegaleseFaker;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Gestionnaire>
 */
class GestionnaireFactory extends Factory
{
    use SenegaleseFaker;

    protected $model = Gestionnaire::class;

    protected static ?string $password;

    public function definition(): array
    {
        $name = $this->senegaleseName();

        return [
            'name'              => $name,
            'email'             => $this->senegaleseEmail($name),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'role'              => Role::Gestionnaire,
            'est_actif'         => true,
            'remember_token'    => Str::random(10),
        ];
    }
}
