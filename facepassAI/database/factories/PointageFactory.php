<?php

namespace Database\Factories;

use App\Models\Employe;
use App\Models\JoursTravail;
use App\Models\Pointage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pointage>
 */
class PointageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employe_id' => Employe::factory(),
            'jours_travail_id' => JoursTravail::factory(),
            'date_heure' => fake()->dateTimeBetween('-30 days', 'now'),
            'type' => fake()->randomElement(['arrivee', 'debut_pause', 'fin_pause', 'depart']),
            'statut' => fake()->randomElement(['valide', 'en_retard', 'depart_anticipe']),
        ];
    }

    public function arrivee(): static
    {
        return $this->state(fn () => [
            'type' => 'arrivee',
            'statut' => fake()->randomElement(['valide', 'en_retard']),
        ]);
    }

    public function depart(): static
    {
        return $this->state(fn () => [
            'type' => 'depart',
            'statut' => fake()->randomElement(['valide', 'depart_anticipe']),
        ]);
    }
}
