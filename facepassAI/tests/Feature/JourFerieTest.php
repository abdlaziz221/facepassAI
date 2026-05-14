<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Administrateur;
use App\Models\Consultant;
use App\Models\Employe;
use App\Models\Gestionnaire;
use App\Models\JourFerie;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests CRUD des jours fériés (Sprint 4 Horaires carte 5, US-042).
 */
class JourFerieTest extends TestCase
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
    // Modèle JourFerie
    // ============================================================

    public function test_is_ferie_retourne_true_pour_une_date_enregistree(): void
    {
        JourFerie::factory()->create(['date' => '2026-01-01']);

        $this->assertTrue(JourFerie::isFerie('2026-01-01'));
        $this->assertTrue(JourFerie::isFerie(\Carbon\Carbon::parse('2026-01-01')));
    }

    public function test_is_ferie_retourne_false_pour_une_date_normale(): void
    {
        JourFerie::factory()->create(['date' => '2026-01-01']);

        $this->assertFalse(JourFerie::isFerie('2026-06-15'));
    }

    public function test_libelle_for_retourne_le_libelle_du_jour(): void
    {
        JourFerie::factory()->create([
            'date'    => '2026-05-01',
            'libelle' => 'Fête du Travail',
        ]);

        $this->assertEquals('Fête du Travail', JourFerie::libelleFor('2026-05-01'));
        $this->assertNull(JourFerie::libelleFor('2026-06-15'));
    }

    public function test_date_est_castee_en_carbon(): void
    {
        $jour = JourFerie::factory()->create(['date' => '2026-01-01']);
        $this->assertInstanceOf(\Carbon\Carbon::class, $jour->date);
    }

    // ============================================================
    // Index — Autorisations
    // ============================================================

    public function test_admin_peut_voir_la_liste(): void
    {
        $this->asAdmin()
            ->get('/admin/jours-feries')
            ->assertStatus(200)
            ->assertSee('Jours fériés');
    }

    public function test_gestionnaire_ne_peut_pas_acceder(): void
    {
        $user = Gestionnaire::factory()->create();
        $user->assignRole(Role::Gestionnaire->value);

        $this->actingAs($user)->get('/admin/jours-feries')->assertStatus(403);
    }

    public function test_employe_ne_peut_pas_acceder(): void
    {
        $user = Employe::factory()->create();
        $user->assignRole(Role::Employe->value);

        $this->actingAs($user)->get('/admin/jours-feries')->assertStatus(403);
    }

    public function test_consultant_ne_peut_pas_acceder(): void
    {
        $user = Consultant::factory()->create();
        $user->assignRole(Role::Consultant->value);

        $this->actingAs($user)->get('/admin/jours-feries')->assertStatus(403);
    }

    public function test_guest_redirige_vers_login(): void
    {
        $this->get('/admin/jours-feries')->assertRedirect('/login');
    }

    // ============================================================
    // Index — Contenu
    // ============================================================

    public function test_la_liste_affiche_les_jours_existants(): void
    {
        JourFerie::factory()->create([
            'date'    => '2026-01-01',
            'libelle' => 'Nouvel An',
        ]);
        JourFerie::factory()->create([
            'date'    => '2026-05-01',
            'libelle' => 'Fête du Travail',
        ]);

        $this->asAdmin()
            ->get('/admin/jours-feries')
            ->assertSee('Nouvel An')
            ->assertSee('Fête du Travail')
            ->assertSee('01/01/2026')
            ->assertSee('01/05/2026');
    }

    public function test_la_liste_indique_si_vide(): void
    {
        $this->asAdmin()
            ->get('/admin/jours-feries')
            ->assertSee('Aucun jour férié');
    }

    // ============================================================
    // Store — Création
    // ============================================================

    public function test_store_cree_un_jour_ferie(): void
    {
        $this->asAdmin()
            ->post('/admin/jours-feries', [
                'date'    => '2026-12-25',
                'libelle' => 'Noël',
            ])
            ->assertRedirect(route('admin.jours-feries.index'))
            ->assertSessionHas('success');

        $jour = JourFerie::first();
        $this->assertNotNull($jour);
        $this->assertEquals('2026-12-25', $jour->date->format('Y-m-d'));
        $this->assertEquals('Noël', $jour->libelle);
    }

    public function test_store_accepte_libelle_optionnel(): void
    {
        $this->asAdmin()
            ->post('/admin/jours-feries', ['date' => '2026-07-14'])
            ->assertSessionHasNoErrors();

        $jour = JourFerie::first();
        $this->assertNotNull($jour);
        $this->assertEquals('2026-07-14', $jour->date->format('Y-m-d'));
        $this->assertNull($jour->libelle);
    }

    public function test_store_refuse_date_manquante(): void
    {
        $this->asAdmin()
            ->post('/admin/jours-feries', ['libelle' => 'Pas de date'])
            ->assertSessionHasErrors('date');
    }

    public function test_store_refuse_date_mauvais_format(): void
    {
        $this->asAdmin()
            ->post('/admin/jours-feries', ['date' => '25 décembre 2026'])
            ->assertSessionHasErrors('date');
    }

    public function test_store_refuse_date_en_doublon(): void
    {
        JourFerie::factory()->create(['date' => '2026-01-01']);

        $this->asAdmin()
            ->post('/admin/jours-feries', ['date' => '2026-01-01'])
            ->assertSessionHasErrors('date');
    }

    public function test_employe_ne_peut_pas_creer(): void
    {
        $user = Employe::factory()->create();
        $user->assignRole(Role::Employe->value);

        $this->actingAs($user)
            ->post('/admin/jours-feries', ['date' => '2026-01-01'])
            ->assertStatus(403);

        $this->assertEquals(0, JourFerie::count());
    }

    // ============================================================
    // Destroy — Suppression
    // ============================================================

    public function test_destroy_supprime_le_jour(): void
    {
        $jour = JourFerie::factory()->create(['date' => '2026-01-01']);

        $this->asAdmin()
            ->delete(route('admin.jours-feries.destroy', $jour))
            ->assertRedirect(route('admin.jours-feries.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('jours_feries', ['id' => $jour->id]);
    }

    public function test_employe_ne_peut_pas_supprimer(): void
    {
        $jour = JourFerie::factory()->create();
        $user = Employe::factory()->create();
        $user->assignRole(Role::Employe->value);

        $this->actingAs($user)
            ->delete(route('admin.jours-feries.destroy', $jour))
            ->assertStatus(403);

        $this->assertDatabaseHas('jours_feries', ['id' => $jour->id]);
    }
}
