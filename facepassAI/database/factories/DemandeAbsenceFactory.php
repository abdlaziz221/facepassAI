<?php

namespace Database\Factories;

use App\Models\DemandeAbsence;
use App\Models\EmployeProfile;
use App\Models\Gestionnaire;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory pour les demandes d'absence (Sprint 4 Horaires carte 6, US-050).
 */
class DemandeAbsenceFactory extends Factory
{
    protected $model = DemandeAbsence::class;

    public function definition(): array
    {
        $debut = $this->faker->dateTimeBetween('+1 day', '+30 days');
        $fin   = (clone $debut)->modify('+' . rand(1, 7) . ' days');

        return [
            'employe_id'              => EmployeProfile::factory(),
            'gestionnaire_id'         => null,
            'date_debut'              => $debut->format('Y-m-d'),
            'date_fin'                => $fin->format('Y-m-d'),
            'motif'                   => $this->faker->randomElement([
                'Congé annuel',
                'Mariage',
                'Naissance',
                'Maladie',
                'Décès dans la famille',
                'Formation externe',
                'Convenance personnelle',
            ]),
            'statut'                  => DemandeAbsence::STATUT_EN_ATTENTE,
            'commentaire_gestionnaire' => null,
        ];
    }

    /** State : demande validée par un gestionnaire. */
    public function validee(?Gestionnaire $gestionnaire = null, string $commentaire = 'Accord donné'): static
    {
        return $this->state(fn () => [
            'statut'                  => DemandeAbsence::STATUT_VALIDEE,
            'gestionnaire_id'         => $gestionnaire?->id ?? Gestionnaire::factory(),
            'commentaire_gestionnaire' => $commentaire,
        ]);
    }

    /** State : demande refusée par un gestionnaire. */
    public function refusee(?Gestionnaire $gestionnaire = null, string $commentaire = 'Effectif insuffisant'): static
    {
        return $this->state(fn () => [
            'statut'                  => DemandeAbsence::STATUT_REFUSEE,
            'gestionnaire_id'         => $gestionnaire?->id ?? Gestionnaire::factory(),
            'commentaire_gestionnaire' => $commentaire,
        ]);
    }

    /** State : demande encore en attente (défaut, mais explicite). */
    public function enAttente(): static
    {
        return $this->state(fn () => [
            'statut'                  => DemandeAbsence::STATUT_EN_ATTENTE,
            'gestionnaire_id'         => null,
            'commentaire_gestionnaire' => null,
        ]);
    }
}
