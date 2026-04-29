<?php

namespace Database\Factories;

use App\Models\Employe;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class EmployeFactory extends Factory
{
    protected $model = Employe::class;

    public function definition(): array
    {
        return [
            'matricule' => $this->faker->unique()->numerify('EMP-#####'),
            'nom' => $this->faker->lastName(),
            'prenom' => $this->faker->firstName(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password'),  // Mot de passe par défaut
            'role' => $this->faker->randomElement(['employe', 'consultant', 'gestionnaire', 'administrateur']),
            'poste' => $this->faker->jobTitle(),
            'departement' => $this->faker->randomElement(['IT', 'RH', 'Commercial', 'Comptabilité', 'Direction']),
            'salaire_brut' => $this->faker->randomFloat(2, 300000, 3000000),
            'photo_faciale' => null,
            'est_actif' => true,
        ];
    }

    public function actif(): static
    {
        return $this->state(fn (array $attributes) => [
            'est_actif' => true,
        ]);
    }

    public function inactif(): static
    {
        return $this->state(fn (array $attributes) => [
            'est_actif' => false,
        ]);
    }

    public function employe(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'employe',
        ]);
    }

    public function gestionnaire(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'gestionnaire',
        ]);
    }

    public function administrateur(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'administrateur',
        ]);
    }
}