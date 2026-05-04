<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use App\Models\Administrateur;
use App\Models\Consultant;
use App\Models\Employe;
use App\Models\EmployeProfile;
use App\Models\Gestionnaire;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder de démo (Sprint 1, US-010 + US-015 + Sprint 2, US-020).
 *
 * Crée 4 comptes nominatifs (un par rôle) + un peu de volume.
 * Pour les Employés, crée aussi un profil métier (matricule, poste...).
 */
class UserHierarchySeeder extends Seeder
{
    public function run(): void
    {
        // ============================================================
        // Comptes de démo
        // ============================================================
        Administrateur::factory()
            ->create([
                'name'     => 'Admin Démo',
                'email'    => 'admin@facepass.test',
                'password' => Hash::make('password'),
            ])
            ->assignRole(RoleEnum::Administrateur->value);

        Gestionnaire::factory()
            ->create([
                'name'     => 'Gestionnaire Démo',
                'email'    => 'gestionnaire@facepass.test',
                'password' => Hash::make('password'),
            ])
            ->assignRole(RoleEnum::Gestionnaire->value);

        Consultant::factory()
            ->create([
                'name'     => 'Consultant Démo',
                'email'    => 'consultant@facepass.test',
                'password' => Hash::make('password'),
            ])
            ->assignRole(RoleEnum::Consultant->value);

        // Employé démo + profil métier
        $employeDemo = Employe::factory()
            ->create([
                'name'     => 'Employé Démo',
                'email'    => 'employe@facepass.test',
                'password' => Hash::make('password'),
            ]);
        $employeDemo->assignRole(RoleEnum::Employe->value);
        EmployeProfile::factory()->create([
            'user_id'      => $employeDemo->id,
            'matricule'    => 'EMP-2026-001',
            'poste'        => 'Développeur',
            'departement'  => 'Informatique',
            'salaire_brut' => 750000,
        ]);

        // ============================================================
        // Volume de démo
        // ============================================================
        // 8 employés avec leur profil métier
        Employe::factory()->count(8)->create()
            ->each(function ($employe) {
                $employe->assignRole(RoleEnum::Employe->value);
                EmployeProfile::factory()->create([
                    'user_id' => $employe->id,
                ]);
            });

        // 2 consultants (pas de profil métier — seuls les employés en ont)
        Consultant::factory()->count(2)->create()
            ->each(fn ($u) => $u->assignRole(RoleEnum::Consultant->value));
    }
}
