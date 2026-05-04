<?php

namespace Tests\Feature;

use App\Models\Administrateur;
use App\Models\Consultant;
use App\Models\Employe;
use App\Models\EmployeProfile;
use App\Models\Gestionnaire;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint 2, US-020 — Tests de la policy EmployeProfilePolicy.
 *
 * Couvre les 4 rôles × 5 actions (viewAny, view, create, update, delete)
 * + le cas spécial "Employé peut voir son propre profil mais pas celui
 * des autres".
 */
class EmployeProfilePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeEmploye(): Employe
    {
        $u = Employe::factory()->create();
        $u->assignRole('employe');
        return $u;
    }

    private function makeConsultant(): Consultant
    {
        $u = Consultant::factory()->create();
        $u->assignRole('consultant');
        return $u;
    }

    private function makeGestionnaire(): Gestionnaire
    {
        $u = Gestionnaire::factory()->create();
        $u->assignRole('gestionnaire');
        return $u;
    }

    private function makeAdministrateur(): Administrateur
    {
        $u = Administrateur::factory()->create();
        $u->assignRole('administrateur');
        return $u;
    }

    /* ============================================================
       viewAny (liste de tous les profils)
       ============================================================ */

    public function test_administrateur_can_view_any(): void
    {
        $this->assertTrue($this->makeAdministrateur()->can('viewAny', EmployeProfile::class));
    }

    public function test_gestionnaire_can_view_any(): void
    {
        $this->assertTrue($this->makeGestionnaire()->can('viewAny', EmployeProfile::class));
    }

    public function test_consultant_can_view_any(): void
    {
        $this->assertTrue($this->makeConsultant()->can('viewAny', EmployeProfile::class));
    }

    public function test_employe_cannot_view_any(): void
    {
        $this->assertFalse($this->makeEmploye()->can('viewAny', EmployeProfile::class));
    }

    /* ============================================================
       view (profil spécifique) — cas spécial Employé sur son profil
       ============================================================ */

    public function test_employe_can_view_own_profile(): void
    {
        $emp = $this->makeEmploye();
        $profile = EmployeProfile::factory()->create(['user_id' => $emp->id]);

        $this->assertTrue($emp->can('view', $profile));
    }

    public function test_employe_cannot_view_other_employe_profile(): void
    {
        $emp1 = $this->makeEmploye();
        $emp2 = $this->makeEmploye();
        $profileOfEmp2 = EmployeProfile::factory()->create(['user_id' => $emp2->id]);

        $this->assertFalse($emp1->can('view', $profileOfEmp2));
    }

    public function test_consultant_can_view_any_profile(): void
    {
        $cons = $this->makeConsultant();
        $emp  = $this->makeEmploye();
        $profile = EmployeProfile::factory()->create(['user_id' => $emp->id]);

        $this->assertTrue($cons->can('view', $profile));
    }

    /* ============================================================
       create
       ============================================================ */

    public function test_administrateur_can_create(): void
    {
        $this->assertTrue($this->makeAdministrateur()->can('create', EmployeProfile::class));
    }

    public function test_gestionnaire_can_create(): void
    {
        $this->assertTrue($this->makeGestionnaire()->can('create', EmployeProfile::class));
    }

    public function test_consultant_cannot_create(): void
    {
        $this->assertFalse($this->makeConsultant()->can('create', EmployeProfile::class));
    }

    public function test_employe_cannot_create(): void
    {
        $this->assertFalse($this->makeEmploye()->can('create', EmployeProfile::class));
    }

    /* ============================================================
       update
       ============================================================ */

    public function test_gestionnaire_can_update_any_profile(): void
    {
        $gest = $this->makeGestionnaire();
        $profile = EmployeProfile::factory()->create();

        $this->assertTrue($gest->can('update', $profile));
    }

    public function test_employe_cannot_update_own_profile(): void
    {
        $emp = $this->makeEmploye();
        $profile = EmployeProfile::factory()->create(['user_id' => $emp->id]);

        // Note : un employé ne peut pas modifier son profil métier
        // (matricule, salaire, poste). Pour ses infos perso (name, email)
        // il passe par la page /profile classique.
        $this->assertFalse($emp->can('update', $profile));
    }

    /* ============================================================
       delete
       ============================================================ */

    public function test_administrateur_can_delete(): void
    {
        $admin = $this->makeAdministrateur();
        $profile = EmployeProfile::factory()->create();

        $this->assertTrue($admin->can('delete', $profile));
    }

    public function test_gestionnaire_can_delete(): void
    {
        $gest = $this->makeGestionnaire();
        $profile = EmployeProfile::factory()->create();

        $this->assertTrue($gest->can('delete', $profile));
    }

    public function test_consultant_cannot_delete(): void
    {
        $cons = $this->makeConsultant();
        $profile = EmployeProfile::factory()->create();

        $this->assertFalse($cons->can('delete', $profile));
    }

    public function test_employe_cannot_delete(): void
    {
        $emp = $this->makeEmploye();
        $profile = EmployeProfile::factory()->create();

        $this->assertFalse($emp->can('delete', $profile));
    }
}
