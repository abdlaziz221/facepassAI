<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Http\Requests\UpdateHoraireRequest;
use App\Models\Administrateur;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de la cohérence des horaires saisis (Sprint 4 Horaires carte 3, US-041).
 *
 * Vérifie le Form Request UpdateHoraireRequest :
 *   arrivée < début pause < fin pause < départ
 *
 * Avec messages d'erreur explicites en français.
 */
class UpdateHoraireRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $user = Administrateur::factory()->create();
        $user->assignRole(Role::Administrateur->value);
        $this->actingAs($user);
    }

    // ============================================================
    // Existence du Form Request
    // ============================================================

    public function test_le_form_request_existe(): void
    {
        $this->assertTrue(class_exists(UpdateHoraireRequest::class));
        $request = new UpdateHoraireRequest();
        $this->assertTrue($request->authorize());
    }

    public function test_le_form_request_definit_des_rules(): void
    {
        $rules = (new UpdateHoraireRequest())->rules();

        $this->assertArrayHasKey('jours_ouvrables', $rules);
        $this->assertArrayHasKey('heure_arrivee', $rules);
        $this->assertArrayHasKey('heure_debut_pause', $rules);
        $this->assertArrayHasKey('heure_fin_pause', $rules);
        $this->assertArrayHasKey('heure_depart', $rules);
        // jours_feries a été extrait dans une table dédiée (Sprint 4 carte 5),
        // n'est plus dans les rules de UpdateHoraireRequest.
    }

    public function test_le_form_request_definit_des_messages_personnalises(): void
    {
        $messages = (new UpdateHoraireRequest())->messages();

        $this->assertNotEmpty($messages);
        $this->assertArrayHasKey('heure_debut_pause.after', $messages);
        $this->assertArrayHasKey('heure_fin_pause.after', $messages);
        $this->assertArrayHasKey('heure_depart.after', $messages);
    }

    // ============================================================
    // Cohérence : arrivée < début pause
    // ============================================================

    public function test_debut_pause_avant_arrivee_est_refuse(): void
    {
        $response = $this->put('/admin/horaires', [
            'jours_ouvrables'   => ['lundi'],
            'heure_arrivee'     => '09:00',
            'heure_debut_pause' => '08:00', // AVANT arrivée
            'heure_fin_pause'   => '13:00',
            'heure_depart'      => '18:00',
        ]);

        $response->assertSessionHasErrors('heure_debut_pause');
        $errors = session('errors')->get('heure_debut_pause');
        $this->assertStringContainsString("après l'heure d'arrivée", $errors[0]);
    }

    public function test_debut_pause_egal_a_arrivee_est_refuse(): void
    {
        // 'after' impose strictement supérieur, pas >=
        $response = $this->put('/admin/horaires', [
            'jours_ouvrables'   => ['lundi'],
            'heure_arrivee'     => '09:00',
            'heure_debut_pause' => '09:00', // EGAL
            'heure_fin_pause'   => '13:00',
            'heure_depart'      => '18:00',
        ]);

        $response->assertSessionHasErrors('heure_debut_pause');
    }

    // ============================================================
    // Cohérence : début pause < fin pause
    // ============================================================

    public function test_fin_pause_avant_debut_pause_est_refusee(): void
    {
        $response = $this->put('/admin/horaires', [
            'jours_ouvrables'   => ['lundi'],
            'heure_arrivee'     => '08:00',
            'heure_debut_pause' => '13:00',
            'heure_fin_pause'   => '12:00', // AVANT début
            'heure_depart'      => '18:00',
        ]);

        $response->assertSessionHasErrors('heure_fin_pause');
        $errors = session('errors')->get('heure_fin_pause');
        $this->assertStringContainsString("après l'heure de début de pause", $errors[0]);
    }

    public function test_fin_pause_egal_a_debut_pause_est_refusee(): void
    {
        $response = $this->put('/admin/horaires', [
            'jours_ouvrables'   => ['lundi'],
            'heure_arrivee'     => '08:00',
            'heure_debut_pause' => '12:00',
            'heure_fin_pause'   => '12:00', // EGAL
            'heure_depart'      => '18:00',
        ]);

        $response->assertSessionHasErrors('heure_fin_pause');
    }

    // ============================================================
    // Cohérence : fin pause < départ
    // ============================================================

    public function test_depart_avant_fin_pause_est_refuse(): void
    {
        $response = $this->put('/admin/horaires', [
            'jours_ouvrables'   => ['lundi'],
            'heure_arrivee'     => '08:00',
            'heure_debut_pause' => '12:00',
            'heure_fin_pause'   => '13:00',
            'heure_depart'      => '12:30', // AVANT fin pause
        ]);

        $response->assertSessionHasErrors('heure_depart');
        $errors = session('errors')->get('heure_depart');
        $this->assertStringContainsString("après l'heure de fin de pause", $errors[0]);
    }

    public function test_depart_egal_a_fin_pause_est_refuse(): void
    {
        $response = $this->put('/admin/horaires', [
            'jours_ouvrables'   => ['lundi'],
            'heure_arrivee'     => '08:00',
            'heure_debut_pause' => '12:00',
            'heure_fin_pause'   => '13:00',
            'heure_depart'      => '13:00', // EGAL
        ]);

        $response->assertSessionHasErrors('heure_depart');
    }

    // ============================================================
    // Cohérence : ordre complet inversé
    // ============================================================

    public function test_ordre_inverse_complet_est_refuse(): void
    {
        $response = $this->put('/admin/horaires', [
            'jours_ouvrables'   => ['lundi'],
            'heure_arrivee'     => '18:00',
            'heure_debut_pause' => '13:00',
            'heure_fin_pause'   => '12:00',
            'heure_depart'      => '08:00',
        ]);

        $response->assertSessionHasErrors([
            'heure_debut_pause',
            'heure_fin_pause',
            'heure_depart',
        ]);
    }

    // ============================================================
    // Cas nominal — succès
    // ============================================================

    public function test_horaires_coherents_sont_acceptes(): void
    {
        $this->put('/admin/horaires', [
            'jours_ouvrables'   => ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi'],
            'heure_arrivee'     => '08:00',
            'heure_debut_pause' => '12:00',
            'heure_fin_pause'   => '13:00',
            'heure_depart'      => '17:00',
        ])->assertSessionHasNoErrors()
          ->assertRedirect(route('admin.horaires.edit'));
    }

    // ============================================================
    // Messages explicites
    // ============================================================

    public function test_message_arrivee_au_mauvais_format(): void
    {
        $response = $this->put('/admin/horaires', [
            'jours_ouvrables'   => ['lundi'],
            'heure_arrivee'     => 'abc',
            'heure_debut_pause' => '12:00',
            'heure_fin_pause'   => '13:00',
            'heure_depart'      => '17:00',
        ]);

        $errors = session('errors')->get('heure_arrivee');
        $this->assertStringContainsString('HH:MM', $errors[0]);
    }

    public function test_message_jour_invalide_explicite(): void
    {
        $response = $this->put('/admin/horaires', [
            'jours_ouvrables'   => ['vendredi_fou'],
            'heure_arrivee'     => '08:00',
            'heure_debut_pause' => '12:00',
            'heure_fin_pause'   => '13:00',
            'heure_depart'      => '17:00',
        ]);

        $response->assertSessionHasErrors('jours_ouvrables.0');
    }
}
