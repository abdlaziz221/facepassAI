<?php

namespace Database\Factories;

use App\Models\EmployeProfile;
use App\Models\Pointage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory Pointage — alignée sur la migration 2026_05_09_005114.
 *
 * employe_id pointe sur la table employes (EmployeProfile),
 * pas directement sur users — on utilise donc EmployeProfile::factory().
 */
class PointageFactory extends Factory
{
    protected $model = Pointage::class;

    public function definition(): array
    {
        return [
            'employe_id'    => EmployeProfile::factory(),
            'type'          => $this->faker->randomElement(Pointage::TYPES),
            'photo_capture' => null,
            'manuel'        => false,
            'motif_manuel'  => null,
        ];
    }

    /** State : pointage d'arrivée matinale. */
    public function arrivee(): static
    {
        return $this->state(fn () => ['type' => Pointage::TYPE_ARRIVEE]);
    }

    /** State : départ en pause déjeuner. */
    public function debutPause(): static
    {
        return $this->state(fn () => ['type' => Pointage::TYPE_DEBUT_PAUSE]);
    }

    /** State : retour de pause déjeuner. */
    public function finPause(): static
    {
        return $this->state(fn () => ['type' => Pointage::TYPE_FIN_PAUSE]);
    }

    /** State : départ du soir. */
    public function depart(): static
    {
        return $this->state(fn () => ['type' => Pointage::TYPE_DEPART]);
    }

    /** State : pointage saisi manuellement par un gestionnaire. */
    public function manuel(string $motif = 'Oubli de badgeage'): static
    {
        return $this->state(fn () => [
            'manuel'       => true,
            'motif_manuel' => $motif,
        ]);
    }
}
