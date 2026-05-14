<?php

namespace Database\Factories;

use App\Models\JourFerie;
use Illuminate\Database\Eloquent\Factories\Factory;

class JourFerieFactory extends Factory
{
    protected $model = JourFerie::class;

    public function definition(): array
    {
        return [
            'date'    => $this->faker->unique()->dateTimeBetween('-1 year', '+1 year')->format('Y-m-d'),
            'libelle' => $this->faker->randomElement([
                'Nouvel An', 'Fête du Travail', 'Fête nationale',
                'Noël', 'Toussaint', 'Pâques', 'Aïd el-Fitr', 'Pont',
                'Fermeture annuelle',
            ]),
        ];
    }
}
