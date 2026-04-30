<?php

namespace Database\Seeders;

use App\Models\Employe;
use Illuminate\Database\Seeder;

class EmployeSeeder extends Seeder
{
    public function run(): void
    {
        // Créer 50 employés aléatoires
        Employe::factory()
            ->count(50)
            ->create();

        // Employé démo - Employé normal
        Employe::create([
            'matricule' => 'EMP-001',
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'email' => 'jean.dupont@example.com',
            'password' => 'password123',
            'role' => 'employe',
            'poste' => 'Développeur Full Stack',
            'departement' => 'IT',
            'salaire_brut' => 1200000,
            'est_actif' => true,
        ]);

        // Administrateur démo
        Employe::create([
            'matricule' => 'ADMIN-001',
            'nom' => 'Admin',
            'prenom' => 'System',
            'email' => 'admin@facepassai.com',
            'password' => 'admin123',
            'role' => 'administrateur',
            'poste' => 'Administrateur Système',
            'departement' => 'IT',
            'salaire_brut' => 2000000,
            'est_actif' => true,
        ]);

        // Gestionnaire démo
        Employe::create([
            'matricule' => 'GEST-001',
            'nom' => 'Diop',
            'prenom' => 'Aminata',
            'email' => 'gestionnaire@facepassai.com',
            'password' => 'gest123',
            'role' => 'gestionnaire',
            'poste' => 'Chef de département',
            'departement' => 'RH',
            'salaire_brut' => 1500000,
            'est_actif' => true,
        ]);

        $this->command->info('✅ Employés créés : 53 employés (dont admin et gestionnaire)');
    }
}