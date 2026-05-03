<?php

namespace Database\Seeders;

use App\Models\Administrateur;
use App\Models\Consultant;
use App\Models\Employe;
use App\Models\Gestionnaire;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder de démo pour la hiérarchie utilisateurs (Sprint 1, US-010).
 *
 * Crée 4 comptes nominatifs (un par rôle) avec mot de passe = "password",
 * puis quelques comptes générés aléatoirement pour avoir un peu de volume.
 */
class UserHierarchySeeder extends Seeder
{
    public function run(): void
    {
        // ---- Comptes de démo (un par rôle) ----
        Administrateur::factory()->create([
            'name'     => 'Admin Démo',
            'email'    => 'admin@facepass.test',
            'password' => Hash::make('password'),
        ]);

        Gestionnaire::factory()->create([
            'name'     => 'Gestionnaire Démo',
            'email'    => 'gestionnaire@facepass.test',
            'password' => Hash::make('password'),
        ]);

        Consultant::factory()->create([
            'name'     => 'Consultant Démo',
            'email'    => 'consultant@facepass.test',
            'password' => Hash::make('password'),
        ]);

        Employe::factory()->create([
            'name'     => 'Employé Démo',
            'email'    => 'employe@facepass.test',
            'password' => Hash::make('password'),
        ]);

        // ---- Volume de démo ----
        Employe::factory()->count(8)->create();
        Consultant::factory()->count(2)->create();
    }
}
