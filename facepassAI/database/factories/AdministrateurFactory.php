<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Administrateur;
use Database\Factories\Concerns\SenegaleseFaker;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Administrateur>
 */
class AdministrateurFactory extends Factory
{
    use SenegaleseFaker;

    protected $model = Administrateur::class;

    protected static ?string $password;

    public function definition(): array
    {
        $name = $this->senegaleseName();

        return [
            'name'              => $name,
            'email'             => $this->senegaleseEmail($name),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'role'              => Role::Administrateur,
            'est_actif'         => true,
            'remember_token'    => Str::random(10),
        ];
    }
}
