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
 * Sprint 2 T12 — US-020/021/022.
 *
 * Tests feature HTTP du module Employé. Couvre :
 *  - CRUD (ajout, modification, suppression) en succès et échec
 *  - Validation (champs requis, unicité email/matricule)
 *  - Permissions par rôle (qui peut faire quoi)
 */
class EmployeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /** Helper : crée un user avec son rôle attaché. */
    private function userWithRole(string $modelClass, string $role)
    {
        $u = $modelClass::factory()->create();
        $u->assignRole($role);
        return $u;
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name'         => 'Aïssatou Diop',
            'email'        => 'aissatou.diop@facepass.test',
            'matricule'    => 'EMP-2026-999',
            'poste'        => 'Développeuse',
            'departement'  => 'Informatique',
            'salaire_brut' => 750000,
        ], $overrides);
    }

    /* ============================================================
       INDEX — liste des employés
       ============================================================ */

    public function test_administrateur_can_view_index(): void
    {
        $admin = $this->userWithRole(Administrateur::class, 'administrateur');

        $this->actingAs($admin)
             ->get('/employes')
             ->assertOk()
             ->assertViewIs('employes.index');
    }

    public function test_gestionnaire_can_view_index(): void
    {
        $gest = $this->userWithRole(Gestionnaire::class, 'gestionnaire');

        $this->actingAs($gest)
             ->get('/employes')
             ->assertOk();
    }

    public function test_consultant_can_view_index(): void
    {
        $cons = $this->userWithRole(Consultant::class, 'consultant');

        $this->actingAs($cons)
             ->get('/employes')
             ->assertOk();
    }

    public function test_employe_cannot_view_index(): void
    {
        $emp = $this->userWithRole(Employe::class, 'employe');

        $this->actingAs($emp)
             ->get('/employes')
             ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/employes')->assertRedirect('/login');
    }

    /* ============================================================
       STORE — Test ajout OK / KO
       ============================================================ */

    public function test_gestionnaire_can_create_employe_ok(): void
    {
        $gest = $this->userWithRole(Gestionnaire::class, 'gestionnaire');

        $response = $this->actingAs($gest)
             ->post('/employes', $this->validPayload());

        $response->assertRedirect('/employes');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'aissatou.diop@facepass.test',
            'name'  => 'Aïssatou Diop',
            'role'  => 'employe',
        ]);

        $this->assertDatabaseHas('employes', [
            'matricule'   => 'EMP-2026-999',
            'poste'       => 'Développeuse',
            'departement' => 'Informatique',
        ]);
    }

    public function test_administrateur_can_create_employe_ok(): void
    {
        $admin = $this->userWithRole(Administrateur::class, 'administrateur');

        $this->actingAs($admin)
             ->post('/employes', $this->validPayload())
             ->assertRedirect('/employes');

        $this->assertDatabaseCount('employes', 1);
    }

    public function test_consultant_cannot_create_employe(): void
    {
        $cons = $this->userWithRole(Consultant::class, 'consultant');

        $this->actingAs($cons)
             ->post('/employes', $this->validPayload())
             ->assertForbidden();

        $this->assertDatabaseCount('employes', 0);
    }

    public function test_employe_cannot_create_employe(): void
    {
        $emp = $this->userWithRole(Employe::class, 'employe');

        $this->actingAs($emp)
             ->post('/employes', $this->validPayload())
             ->assertForbidden();

        $this->assertDatabaseCount('employes', 0);
    }

    public function test_store_ko_if_name_missing(): void
    {
        $gest = $this->userWithRole(Gestionnaire::class, 'gestionnaire');

        $this->actingAs($gest)
             ->post('/employes', $this->validPayload(['name' => '']))
             ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('employes', 0);
    }

    public function test_store_ko_if_email_invalid(): void
    {
        $gest = $this->userWithRole(Gestionnaire::class, 'gestionnaire');

        $this->actingAs($gest)
             ->post('/employes', $this->validPayload(['email' => 'pas-un-email']))
             ->assertSessionHasErrors('email');
    }

    public function test_store_ko_if_email_already_used(): void
    {
        $gest    = $this->userWithRole(Gestionnaire::class, 'gestionnaire');
        $existing = Employe::factory()->create(['email' => 'doublon@facepass.test']);

        $this->actingAs($gest)
             ->post('/employes', $this->validPayload(['email' => 'doublon@facepass.test']))
             ->assertSessionHasErrors('email');
    }

    public function test_store_ko_if_matricule_already_used(): void
    {
        $gest    = $this->userWithRole(Gestionnaire::class, 'gestionnaire');
        $existing = EmployeProfile::factory()->create(['matricule' => 'EMP-2026-DBL']);

        $this->actingAs($gest)
             ->post('/employes', $this->validPayload(['matricule' => 'EMP-2026-DBL']))
             ->assertSessionHasErrors('matricule');
    }

    public function test_store_ko_if_salaire_negative(): void
    {
        $gest = $this->userWithRole(Gestionnaire::class, 'gestionnaire');

        $this->actingAs($gest)
             ->post('/employes', $this->validPayload(['salaire_brut' => -1000]))
             ->assertSessionHasErrors('salaire_brut');
    }

    /* ============================================================
       SHOW — voir un profil
       ============================================================ */

    public function test_employe_can_view_own_profile(): void
    {
        $emp     = $this->userWithRole(Employe::class, 'employe');
        $profile = EmployeProfile::factory()->create(['user_id' => $emp->id]);

        $this->actingAs($emp)
             ->get("/employes/{$profile->id}")
             ->assertOk();
    }

    public function test_employe_cannot_view_other_profile(): void
    {
        $emp1    = $this->userWithRole(Employe::class, 'employe');
        $emp2    = $this->userWithRole(Employe::class, 'employe');
        $profile = EmployeProfile::factory()->create(['user_id' => $emp2->id]);

        $this->actingAs($emp1)
             ->get("/employes/{$profile->id}")
             ->assertForbidden();
    }

    /* ============================================================
       UPDATE — modification
       ============================================================ */

    public function test_gestionnaire_can_update_employe(): void
    {
        $gest    = $this->userWithRole(Gestionnaire::class, 'gestionnaire');
        $profile = EmployeProfile::factory()->create(['poste' => 'Ancien poste']);

        $response = $this->actingAs($gest)
             ->patch("/employes/{$profile->id}", $this->validPayload([
                 'poste' => 'Nouveau poste',
             ]));

        $response->assertRedirect("/employes/{$profile->id}");
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('employes', [
            'id'    => $profile->id,
            'poste' => 'Nouveau poste',
        ]);
    }

    public function test_update_ko_if_email_taken_by_another_user(): void
    {
        $gest    = $this->userWithRole(Gestionnaire::class, 'gestionnaire');
        $other   = Employe::factory()->create(['email' => 'autre@facepass.test']);
        $profile = EmployeProfile::factory()->create();

        $this->actingAs($gest)
             ->patch("/employes/{$profile->id}", $this->validPayload([
                 'email' => 'autre@facepass.test',
             ]))
             ->assertSessionHasErrors('email');
    }

    public function test_update_ok_with_same_email_as_current(): void
    {
        $gest    = $this->userWithRole(Gestionnaire::class, 'gestionnaire');
        $emp     = Employe::factory()->create(['email' => 'jegarde@facepass.test']);
        $profile = EmployeProfile::factory()->create(['user_id' => $emp->id]);

        // Garder le même email doit fonctionner (ignore unique sur soi-même)
        $this->actingAs($gest)
             ->patch("/employes/{$profile->id}", $this->validPayload([
                 'email' => 'jegarde@facepass.test',
             ]))
             ->assertRedirect("/employes/{$profile->id}");
    }

    public function test_employe_cannot_update_any_profile(): void
    {
        $emp     = $this->userWithRole(Employe::class, 'employe');
        $profile = EmployeProfile::factory()->create();

        $this->actingAs($emp)
             ->patch("/employes/{$profile->id}", $this->validPayload())
             ->assertForbidden();
    }

    /* ============================================================
       DESTROY — suppression
       ============================================================ */

    public function test_gestionnaire_can_delete_employe(): void
    {
        $gest    = $this->userWithRole(Gestionnaire::class, 'gestionnaire');
        $emp     = Employe::factory()->create();
        $profile = EmployeProfile::factory()->create(['user_id' => $emp->id]);

        $response = $this->actingAs($gest)
             ->delete("/employes/{$profile->id}");

        $response->assertRedirect('/employes');
        $response->assertSessionHas('success');

        // Le profil est supprimé...
        $this->assertDatabaseMissing('employes', ['id' => $profile->id]);

        // ...et le compte user est désactivé (pas supprimé — sera SoftDelete au T8)
        $this->assertDatabaseHas('users', [
            'id'        => $emp->id,
            'est_actif' => false,
        ]);
    }

    public function test_administrateur_can_delete_employe(): void
    {
        $admin   = $this->userWithRole(Administrateur::class, 'administrateur');
        $profile = EmployeProfile::factory()->create();

        $this->actingAs($admin)
             ->delete("/employes/{$profile->id}")
             ->assertRedirect('/employes');

        $this->assertDatabaseMissing('employes', ['id' => $profile->id]);
    }

    public function test_consultant_cannot_delete_employe(): void
    {
        $cons    = $this->userWithRole(Consultant::class, 'consultant');
        $profile = EmployeProfile::factory()->create();

        $this->actingAs($cons)
             ->delete("/employes/{$profile->id}")
             ->assertForbidden();

        $this->assertDatabaseHas('employes', ['id' => $profile->id]);
    }

    public function test_employe_cannot_delete_employe(): void
    {
        $emp     = $this->userWithRole(Employe::class, 'employe');
        $profile = EmployeProfile::factory()->create();

        $this->actingAs($emp)
             ->delete("/employes/{$profile->id}")
             ->assertForbidden();

        $this->assertDatabaseHas('employes', ['id' => $profile->id]);
    }

    /* ============================================================
       SUPPRESSION + intégrité référentielle
       ============================================================ */

    public function test_delete_user_cascades_to_profile(): void
    {
        $emp     = Employe::factory()->create();
        $profile = EmployeProfile::factory()->create(['user_id' => $emp->id]);

        // Supprimer le user supprime automatiquement le profil
        // (FK cascadeOnDelete)
        $emp->delete();

        $this->assertDatabaseMissing('employes', ['id' => $profile->id]);
    }
}
