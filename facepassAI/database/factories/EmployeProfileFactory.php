<?php

namespace Database\Factories;

use App\Models\Employe;
use App\Models\EmployeProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeProfile>
 */
class EmployeProfileFactory extends Factory
{
    protected $model = EmployeProfile::class;

    public function definition(): array
    {
        $departements = [
            'Ressources Humaines',
            'Informatique',
            'Comptabilité',
            'Commercial',
            'Administration',
            'Sécurité',
            'Logistique',
        ];

        $postes = [
            'Développeur',
            'Chargé de communication',
            'Comptable',
            'Commercial',
            'Assistant administratif',
            'Agent de sécurité',
            'Responsable logistique',
            'Analyste',
            'Designer UX',
        ];

        return [
            'user_id'       => Employe::factory(),
            'matricule'     => 'EMP-' . fake()->unique()->numerify('######'),
            'poste'         => fake()->randomElement($postes),
            'departement'   => fake()->randomElement($departements),
            'salaire_brut'  => fake()->randomFloat(2, 350000, 1500000), // FCFA
            'photo_faciale' => null, // sera renseigné quand le microservice tournera (Sprint 3)
        ];
    }
}
