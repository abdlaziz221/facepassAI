<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use App\Models\Administrateur;
use App\Models\Consultant;
use App\Models\Employe;
use App\Models\Gestionnaire;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder de démo pour la hiérarchie utilisateurs (Sprint 1, US-010 + US-015).
 *
 * Crée 4 comptes nominatifs (un par rôle) avec mot de passe = "password",
 * + un peu de volume aléatoire. Chaque user reçoit son rôle spatie via
 * assignRole() (le RolePermissionSeeder doit avoir tourné AVANT).
 */
class UserHierarchySeeder extends Seeder
{
    public function run(): void
    {
        // ============================================================
        // Comptes de démo (un par rôle)
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

        Employe::factory()
            ->create([
                'name'     => 'Employé Démo',
                'email'    => 'employe@facepass.test',
                'password' => Hash::make('password'),
            ])
            ->assignRole(RoleEnum::Employe->value);

        // ============================================================
        // Volume de démo (factories aléatoires + assignation des rôles)
        // ============================================================
        Employe::factory()
            ->count(8)
            ->create()
            ->each(fn ($u) => $u->assignRole(RoleEnum::Employe->value));

        Consultant::factory()
            ->count(2)
            ->create()
            ->each(fn ($u) => $u->assignRole(RoleEnum::Consultant->value));
    }
}
