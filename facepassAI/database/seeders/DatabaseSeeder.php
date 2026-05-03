<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seeders du projet facepassAI.
     *
     * NOTE Sprint 1 :
     *   - On démarre avec UserHierarchySeeder (hiérarchie auth + STI)
     *   - EmployeSeeder / PointageSeeder sont mis en pause :
     *     ils seront repris au Sprint 2 (Gestion des Employés) une fois
     *     que la table 'employes' aura été refondue en table de PROFIL
     *     (matricule, poste, departement, salaire_brut, photo_faciale)
     *     liée à 'users' par user_id.
     */
    public function run(): void
    {
        // Créer un utilisateur de test (optionnel, pour Breeze plus tard)
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Appeler nos seeders personnalisés
        $this->call([
            EmployeSeeder::class,
            PointageSeeder::class,
        ]);
    }
}