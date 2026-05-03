<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Consultant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Consultant>
 */
class ConsultantFactory extends Factory
{
    protected $model = Consultant::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'role'              => Role::Consultant,
            'est_actif'         => true,
            'remember_token'    => Str::random(10),
        ];
    }
}
