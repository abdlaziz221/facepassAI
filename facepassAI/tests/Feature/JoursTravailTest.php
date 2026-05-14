<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Administrateur;
use App\Models\Consultant;
use App\Models\Employe;
use App\Models\Gestionnaire;
use App\Models\JoursTravail;
use Database\Seeders\JoursTravailSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests du modèle JoursTravail et de sa configuration (Sprint 4 US-040).
 */
class JoursTravailTest extends TestCase
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
    // Modèle JoursTravail
    // ============================================================

    public function test_current_cree_la_config_par_defaut_si_inexistante(): void
    {
        $this->assertEquals(0, JoursTravail::count());

        $config = JoursTravail::current();

        $this->assertNotNull($config->id);
        $this->assertEquals(['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi'], $config->jours_ouvrables);
        $this->assertEquals('08:00', substr($config->heure_arrivee, 0, 5));
        $this->assertEquals('17:00', substr($config->heure_depart, 0, 5));
    }

    public function test_current_retourne_le_singleton_existant(): void
    {
        $first  = JoursTravail::current();
        $second = JoursTravail::current();

        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(1, JoursTravail::count());
    }

    public function test_modele_utilise_la_table_jours_travail(): void
    {
        $this->assertEquals('jours_travail', (new JoursTravail())->getTable());
    }

    public function test_les_constantes_de_jours_sont_exposees(): void
    {
        $this->assertCount(7, JoursTravail::JOURS_VALIDES);
        $this->assertContains('lundi', JoursTravail::JOURS_VALIDES);
        $this->assertContains('dimanche', JoursTravail::JOURS_VALIDES);
    }

    // ============================================================
    // Seeder
    // ============================================================

    public function test_seeder_cree_la_configuration_par_defaut(): void
    {
        $this->seed(JoursTravailSeeder::class);

        $this->assertEquals(1, JoursTravail::count());

        $config = JoursTravail::first();
        $this->assertEquals(['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi'], $config->jours_ouvrables);
        $this->assertEquals('08:00', substr($config->heure_arrivee, 0, 5));
        $this->assertEquals('17:00', substr($config->heure_depart, 0, 5));
    }

    public function test_seeder_est_idempotent(): void
    {
        $this->seed(JoursTravailSeeder::class);
        $this->seed(JoursTravailSeeder::class);
        $this->seed(JoursTravailSeeder::class);

        // Toujours une seule ligne (pas de duplication)
        $this->assertEquals(1, JoursTravail::count());
    }

    // ============================================================
    // Casts
    // ============================================================

    public function test_jours_ouvrables_est_caste_en_tableau(): void
    {
        $config = JoursTravail::current();
        $this->assertIsArray($config->jours_ouvrables);
    }

    // Note : le champ jours_feries du modèle a été extrait dans une table
    // dédiée JourFerie (Sprint 4 carte 5). Les tests sur ce champ sont
    // remplacés par JourFerieTest.

    // ============================================================
    // Autorisations (formulaire admin)
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
    // Validation et update
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

    public function test_ordre_des_heures_respecte(): void
    {
        $this->asAdmin()
            ->put('/admin/horaires', [
                'jours_ouvrables'   => ['lundi'],
                'heure_arrivee'     => '09:00',
                'heure_debut_pause' => '08:00',
                'heure_fin_pause'   => '13:00',
                'heure_depart'      => '18:00',
            ])
            ->assertSessionHasErrors('heure_debut_pause');
    }

    public function test_update_enregistre_la_configuration(): void
    {
        $this->asAdmin()
            ->put('/admin/horaires', [
                'jours_ouvrables'   => ['lundi', 'mardi', 'mercredi'],
                'heure_arrivee'     => '08:30',
                'heure_debut_pause' => '12:30',
                'heure_fin_pause'   => '13:30',
                'heure_depart'      => '17:30',
            ])
            ->assertRedirect(route('admin.horaires.edit'))
            ->assertSessionHas('success');

        $config = JoursTravail::current()->fresh();
        $this->assertEquals(['lundi', 'mardi', 'mercredi'], $config->jours_ouvrables);
        $this->assertEquals('08:30', substr($config->heure_arrivee, 0, 5));
    }
}
