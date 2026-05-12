<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Administrateur;
use App\Models\Consultant;
use App\Models\Employe;
use App\Models\Gestionnaire;
use App\Models\HoraireConfig;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de la configuration des horaires (Sprint 4 US-040).
 */
class HoraireConfigTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function asAdmin(): self
    {
        $user = Administrateur::factory()->create();
        $user->assignRole(Role::Administrateur->value);
        return $this->actingAs($user);
    }

    // ============================================================
    // Modèle
    // ============================================================

    public function test_current_cree_la_config_par_defaut_si_inexistante(): void
    {
        $this->assertEquals(0, HoraireConfig::count());

        $config = HoraireConfig::current();

        $this->assertNotNull($config->id);
        $this->assertEquals(['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi'], $config->jours_ouvrables);
        $this->assertEquals('09:00', substr($config->heure_arrivee, 0, 5));
        $this->assertEquals([], $config->jours_feries);
    }

    public function test_current_retourne_le_singleton_existant(): void
    {
        $first  = HoraireConfig::current();
        $second = HoraireConfig::current();

        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(1, HoraireConfig::count());
    }

    // ============================================================
    // Autorisations
    // ============================================================

    public function test_admin_peut_acceder_au_formulaire(): void
    {
        $this->asAdmin()
            ->get('/admin/horaires')
            ->assertStatus(200)
            ->assertSee('Configuration des horaires');
    }

    public function test_gestionnaire_ne_peut_pas_acceder(): void
    {
        $user = Gestionnaire::factory()->create();
        $user->assignRole(Role::Gestionnaire->value);

        $this->actingAs($user)
            ->get('/admin/horaires')
            ->assertStatus(403);
    }

    public function test_employe_ne_peut_pas_acceder(): void
    {
        $user = Employe::factory()->create();
        $user->assignRole(Role::Employe->value);

        $this->actingAs($user)
            ->get('/admin/horaires')
            ->assertStatus(403);
    }

    public function test_consultant_ne_peut_pas_acceder(): void
    {
        $user = Consultant::factory()->create();
        $user->assignRole(Role::Consultant->value);

        $this->actingAs($user)
            ->get('/admin/horaires')
            ->assertStatus(403);
    }

    public function test_guest_redirige_vers_login(): void
    {
        $this->get('/admin/horaires')->assertRedirect('/login');
    }

    // ============================================================
    // Affichage du formulaire
    // ============================================================

    public function test_le_formulaire_contient_les_7_jours(): void
    {
        $response = $this->asAdmin()->get('/admin/horaires');

        foreach (['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'] as $j) {
            $response->assertSee($j);
        }
    }

    public function test_le_formulaire_contient_les_4_heures(): void
    {
        $this->asAdmin()
            ->get('/admin/horaires')
            ->assertSee('Arrivée')
            ->assertSee('Début de pause')
            ->assertSee('Fin de pause')
            ->assertSee('Départ');
    }

    public function test_le_formulaire_a_les_boutons_enregistrer_et_annuler(): void
    {
        $this->asAdmin()
            ->get('/admin/horaires')
            ->assertSee('Enregistrer')
            ->assertSee('Annuler');
    }

    // ============================================================
    // Validation
    // ============================================================

    public function test_jours_ouvrables_obligatoires(): void
    {
        $this->asAdmin()
            ->put('/admin/horaires', [
                'heure_arrivee'     => '09:00',
                'heure_debut_pause' => '12:00',
                'heure_fin_pause'   => '13:00',
                'heure_depart'      => '18:00',
            ])
            ->assertSessionHasErrors('jours_ouvrables');
    }

    public function test_jours_ouvrables_doivent_etre_valides(): void
    {
        $this->asAdmin()
            ->put('/admin/horaires', [
                'jours_ouvrables'   => ['fakeday'],
                'heure_arrivee'     => '09:00',
                'heure_debut_pause' => '12:00',
                'heure_fin_pause'   => '13:00',
                'heure_depart'      => '18:00',
            ])
            ->assertSessionHasErrors('jours_ouvrables.0');
    }

    public function test_ordre_des_heures_respecte(): void
    {
        // Début de pause AVANT arrivée → KO
        $this->asAdmin()
            ->put('/admin/horaires', [
                'jours_ouvrables'   => ['lundi'],
                'heure_arrivee'     => '09:00',
                'heure_debut_pause' => '08:00', // avant arrivée
                'heure_fin_pause'   => '13:00',
                'heure_depart'      => '18:00',
            ])
            ->assertSessionHasErrors('heure_debut_pause');
    }

    public function test_format_heure_doit_etre_hh_mm(): void
    {
        $this->asAdmin()
            ->put('/admin/horaires', [
                'jours_ouvrables'   => ['lundi'],
                'heure_arrivee'     => 'abc',
                'heure_debut_pause' => '12:00',
                'heure_fin_pause'   => '13:00',
                'heure_depart'      => '18:00',
            ])
            ->assertSessionHasErrors('heure_arrivee');
    }

    // ============================================================
    // Update
    // ============================================================

    public function test_update_enregistre_la_configuration(): void
    {
        $this->asAdmin()
            ->put('/admin/horaires', [
                'jours_ouvrables'   => ['lundi', 'mardi', 'mercredi'],
                'heure_arrivee'     => '08:30',
                'heure_debut_pause' => '12:30',
                'heure_fin_pause'   => '13:30',
                'heure_depart'      => '17:30',
                'jours_feries'      => ['2026-01-01', '2026-05-01'],
            ])
            ->assertRedirect(route('admin.horaires.edit'))
            ->assertSessionHas('success');

        $config = HoraireConfig::current()->fresh();
        $this->assertEquals(['lundi', 'mardi', 'mercredi'], $config->jours_ouvrables);
        $this->assertEquals('08:30', substr($config->heure_arrivee, 0, 5));
        $this->assertEquals(['2026-01-01', '2026-05-01'], $config->jours_feries);
    }

    public function test_update_accepte_jours_feries_vides(): void
    {
        $this->asAdmin()
            ->put('/admin/horaires', [
                'jours_ouvrables'   => ['lundi'],
                'heure_arrivee'     => '09:00',
                'heure_debut_pause' => '12:00',
                'heure_fin_pause'   => '13:00',
                'heure_depart'      => '18:00',
            ])
            ->assertSessionHasNoErrors();

        $this->assertEquals([], HoraireConfig::current()->fresh()->jours_feries);
    }

    public function test_update_filtre_les_dates_vides_dans_jours_feries(): void
    {
        $this->asAdmin()
            ->put('/admin/horaires', [
                'jours_ouvrables'   => ['lundi'],
                'heure_arrivee'     => '09:00',
                'heure_debut_pause' => '12:00',
                'heure_fin_pause'   => '13:00',
                'heure_depart'      => '18:00',
                'jours_feries'      => ['2026-01-01', '', '2026-05-01', null],
            ])
            ->assertSessionHasNoErrors();

        $feries = HoraireConfig::current()->fresh()->jours_feries;
        $this->assertEquals(['2026-01-01', '2026-05-01'], $feries);
    }
}
