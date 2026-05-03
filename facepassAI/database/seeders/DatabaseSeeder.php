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
        $this->call([
            UserHierarchySeeder::class,

            // À réactiver au Sprint 2 (après refonte de la table employes) :
            // EmployeSeeder::class,
            // PointageSeeder::class,
        ]);
    }
}
