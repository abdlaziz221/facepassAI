<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Administrateur;
use App\Models\DemandeAbsence;
use App\Models\Employe;
use App\Models\EmployeProfile;
use App\Models\Gestionnaire;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint 6 carte 5 (US-090) — Tests du CRUD gestionnaires (admin).
 */
class GestionnaireControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Administrateur $admin;
    protected Gestionnaire $gestionnaire;
    protected Employe $employe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = Administrateur::factory()->create();
        $this->admin->assignRole(Role::Administrateur->value);

        $this->gestionnaire = Gestionnaire::factory()->create(['name' => 'Marie Diop']);
        $this->gestionnaire->assignRole(Role::Gestionnaire->value);

        $this->employe = Employe::factory()->create();
        $this->employe->assignRole(Role::Employe->value);
    }

    // ========================================================================
    // Accès
    // ========================================================================

    public function test_index_accessible_a_l_admin(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.gestionnaires.index'))
            ->assertOk()
            ->assertSee('Comptes gestionnaires', false)
            ->assertSee('Marie Diop');
    }

    public function test_index_refuse_au_gestionnaire(): void
    {
        $this->actingAs($this->gestionnaire)
            ->get(route('admin.gestionnaires.index'))
            ->assertForbidden();
    }

    public function test_index_refuse_a_l_employe(): void
    {
        $this->actingAs($this->employe)
            ->get(route('admin.gestionnaires.index'))
            ->assertForbidden();
    }

    public function test_index_redirige_si_non_connecte(): void
    {
        $this->get(route('admin.gestionnaires.index'))
            ->assertRedirect(route('login'));
    }

    // ========================================================================
    // Création
    // ========================================================================

    public function test_form_creation_accessible(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.gestionnaires.create'))
            ->assertOk()
            ->assertSee('Nouveau gestionnaire', false);
    }

    public function test_creation_avec_donnees_valides_cree_le_gestionnaire(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.gestionnaires.store'), [
                'name'  => 'Khady Camara',
                'email' => 'khady@facepass.ai',
            ]);

        $response->assertRedirect(route('admin.gestionnaires.index'));

        $g = Gestionnaire::where('email', 'khady@facepass.ai')->first();
        $this->assertNotNull($g);
        $this->assertEquals('Khady Camara', $g->name);
        $this->assertEquals(Role::Gestionnaire->value, $g->role->value ?? $g->role);
        $this->assertTrue($g->est_actif);
        $this->assertTrue($g->hasRole(Role::Gestionnaire->value));
    }

    public function test_creation_flash_le_mot_de_passe_temporaire(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.gestionnaires.store'), [
                'name'  => 'Test Pwd',
                'email' => 'pwd@facepass.ai',
            ]);

        $response->assertSessionHas('temp_password');
        $tempPwd = session('temp_password');
        $this->assertIsString($tempPwd);
        $this->assertGreaterThanOrEqual(10, strlen($tempPwd));
    }

    public function test_creation_email_obligatoire(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.gestionnaires.store'), ['name' => 'Test', 'email' => ''])
            ->assertSessionHasErrors('email');
    }

    public function test_creation_email_doit_etre_unique(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.gestionnaires.store'), [
                'name'  => 'Doublon',
                'email' => $this->gestionnaire->email, // déjà pris
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_creation_nom_obligatoire(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.gestionnaires.store'), ['name' => '', 'email' => 'a@b.c'])
            ->assertSessionHasErrors('name');
    }

    public function test_creation_refuse_au_non_admin(): void
    {
        $this->actingAs($this->gestionnaire)
            ->post(route('admin.gestionnaires.store'), [
                'name'  => 'Pwned',
                'email' => 'pwn@facepass.ai',
            ])
            ->assertForbidden();
    }

    // ========================================================================
    // Édition
    // ========================================================================

    public function test_form_edition_accessible(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.gestionnaires.edit', $this->gestionnaire))
            ->assertOk()
            ->assertSee($this->gestionnaire->name);
    }

    public function test_update_modifie_le_nom_et_email(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.gestionnaires.update', $this->gestionnaire), [
                'name'      => 'Marie Diop Renommée',
                'email'     => 'marie.new@facepass.ai',
                'est_actif' => 1,
            ])
            ->assertRedirect(route('admin.gestionnaires.index'));

        $this->gestionnaire->refresh();
        $this->assertEquals('Marie Diop Renommée', $this->gestionnaire->name);
        $this->assertEquals('marie.new@facepass.ai', $this->gestionnaire->email);
        $this->assertTrue($this->gestionnaire->est_actif);
    }

    public function test_update_permet_de_garder_le_meme_email(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.gestionnaires.update', $this->gestionnaire), [
                'name'      => 'Nom modifié',
                'email'     => $this->gestionnaire->email, // pas de changement
                'est_actif' => 1,
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_update_refuse_un_email_deja_pris(): void
    {
        $autre = Gestionnaire::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('admin.gestionnaires.update', $this->gestionnaire), [
                'name'      => 'Test',
                'email'     => $autre->email, // celui d'un autre user
                'est_actif' => 1,
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_update_peut_desactiver_le_compte(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.gestionnaires.update', $this->gestionnaire), [
                'name'      => $this->gestionnaire->name,
                'email'     => $this->gestionnaire->email,
                // pas de est_actif → désactivé
            ]);

        $this->assertFalse($this->gestionnaire->fresh()->est_actif);
    }

    // ========================================================================
    // Suppression + garde demandes en cours
    // ========================================================================

    public function test_destroy_supprime_le_gestionnaire(): void
    {
        // Un autre gestionnaire existe → la suppression est OK
        Gestionnaire::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.gestionnaires.destroy', $this->gestionnaire))
            ->assertRedirect(route('admin.gestionnaires.index'));

        $this->assertDatabaseMissing('users', ['id' => $this->gestionnaire->id]);
    }

    public function test_destroy_bloque_si_dernier_gestionnaire_et_demandes_en_attente(): void
    {
        // Une demande en attente
        $profile = EmployeProfile::factory()->create();
        DemandeAbsence::factory()->for($profile, 'employe')->create([
            'statut'     => DemandeAbsence::STATUT_EN_ATTENTE,
            'date_debut' => '2026-07-01',
            'date_fin'   => '2026-07-05',
        ]);

        // Il n'y a que $this->gestionnaire en base
        $this->assertEquals(1, Gestionnaire::count());

        $this->actingAs($this->admin)
            ->delete(route('admin.gestionnaires.destroy', $this->gestionnaire))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('users', ['id' => $this->gestionnaire->id]);
    }

    public function test_destroy_ok_si_demandes_en_attente_mais_autres_gestionnaires(): void
    {
        // Une demande en attente
        $profile = EmployeProfile::factory()->create();
        DemandeAbsence::factory()->for($profile, 'employe')->create([
            'statut'     => DemandeAbsence::STATUT_EN_ATTENTE,
            'date_debut' => '2026-07-01',
            'date_fin'   => '2026-07-05',
        ]);

        // Un autre gestionnaire pourra prendre le relais
        Gestionnaire::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.gestionnaires.destroy', $this->gestionnaire))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('users', ['id' => $this->gestionnaire->id]);
    }

    public function test_destroy_refuse_au_non_admin(): void
    {
        $this->actingAs($this->employe)
            ->delete(route('admin.gestionnaires.destroy', $this->gestionnaire))
            ->assertForbidden();
    }
}
