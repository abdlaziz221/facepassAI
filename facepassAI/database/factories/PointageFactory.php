<?php

namespace Database\Factories;

use App\Models\Employe;
use App\Models\Pointage;
use Illuminate\Database\Eloquent\Factories\Factory;

class PointageFactory extends Factory
{
    protected $model = Pointage::class;

    public function definition(): array
    {
        $types = ['arrivee', 'debut_pause', 'fin_pause', 'depart'];
        $statuts = ['valide', 'en_retard', 'depart_anticipe'];
        
        return [
            'employe_id' => Employe::factory(),
            'jours_travail_id' => null,  // Peut être null au début
            'date_heure' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'type' => $this->faker->randomElement($types),
            'statut' => $this->faker->randomElement($statuts),
        ];
    }

    // Pointage en retard
    public function enRetard(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'en_retard',
        ]);
    }

    // Pointage départ anticipé
    public function departAnticipe(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'depart_anticipe',
        ]);
    }

    // Pointage validé (normal)
    public function valide(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'valide',
        ]);
    }

    // Pointage pour un type spécifique
    public function deType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }
}
