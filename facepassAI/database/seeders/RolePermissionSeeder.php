<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeder des rôles et permissions (Sprint 1 — US-015).
 *
 * Crée les 4 rôles spatie (employe / consultant / gestionnaire / administrateur)
 * et toutes les permissions liées aux 14 UC du backlog.
 *
 * Hiérarchie d'héritage des permissions :
 *   Administrateur ⊃ Gestionnaire ⊃ Consultant ⊃ Employé
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Vider le cache spatie pour repartir propre
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ============================================================
        // 1) PERMISSIONS — groupées par UC du backlog
        // ============================================================
        $permissions = [
            // UC-02 Gestion des employés (CRUD)
            'employes.view',
            'employes.create',
            'employes.update',
            'employes.delete',

            // UC-03 Pointage biométrique
            'pointages.create-own',         // L'employé peut pointer
            'pointages.view-own',           // Voir son propre historique
            'pointages.view-all',           // Voir tous les pointages
            'pointages.manual-validate',    // Validation manuelle (panne caméra)

            // UC-04 Configuration des horaires
            'horaires.configure',

            // UC-05/06 Demandes d'absence
            'absences.create-own',          // L'employé fait sa demande
            'absences.view-own',            // Voir ses demandes
            'absences.view-all',            // Voir toutes les demandes
            'absences.validate',            // Valider/refuser les demandes

            // UC-08 Rapports & exports
            'rapports.view',
            'rapports.export',

            // UC-09 Salaires
            'salaire.view-own',             // Mon salaire
            'salaire.view-all',             // Tous les salaires (admin)

            // UC-10 Administration des gestionnaires
            'gestionnaires.manage',

            // UC-11 Logs / audit
            'logs.view',
            'logs.export',

            // UC-12 Tableau de bord
            'dashboard.view',
            'dashboard.kpi.view',

            // UC-14 Profil utilisateur
            'profile.edit',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate([
                'name'       => $name,
                'guard_name' => 'web',
            ]);
        }

        // ============================================================
        // 2) RÔLE : EMPLOYÉ (permissions de base)
        // ============================================================
        $employe = Role::firstOrCreate([
            'name'       => RoleEnum::Employe->value,
            'guard_name' => 'web',
        ]);
        $employePerms = [
            'pointages.create-own',
            'pointages.view-own',
            'absences.create-own',
            'absences.view-own',
            'salaire.view-own',
            'dashboard.view',
            'profile.edit',
        ];
        $employe->syncPermissions($employePerms);

        // ============================================================
        // 3) RÔLE : CONSULTANT (Employé + lecture étendue)
        // ============================================================
        $consultant = Role::firstOrCreate([
            'name'       => RoleEnum::Consultant->value,
            'guard_name' => 'web',
        ]);
        $consultantPerms = array_merge($employePerms, [
            'employes.view',
            'pointages.view-all',
            'absences.view-all',
            'rapports.view',
            'rapports.export',
        ]);
        $consultant->syncPermissions($consultantPerms);

        // ============================================================
        // 4) RÔLE : GESTIONNAIRE (Consultant + actions de gestion)
        // ============================================================
        $gestionnaire = Role::firstOrCreate([
            'name'       => RoleEnum::Gestionnaire->value,
            'guard_name' => 'web',
        ]);
        $gestionnairePerms = array_merge($consultantPerms, [
            'employes.create',
            'employes.update',
            'employes.delete',
            'absences.validate',
            'horaires.configure',
            'dashboard.kpi.view',
            'pointages.manual-validate',
        ]);
        $gestionnaire->syncPermissions($gestionnairePerms);

        // ============================================================
        // 5) RÔLE : ADMINISTRATEUR (toutes les permissions)
        // ============================================================
        $admin = Role::firstOrCreate([
            'name'       => RoleEnum::Administrateur->value,
            'guard_name' => 'web',
        ]);
        $admin->syncPermissions(Permission::all());
    }
}
