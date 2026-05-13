<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Administrateur;
use App\Models\Gestionnaire;
use App\Models\JoursTravail;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint 5 carte 5 (US-063) — Avertissement quand les horaires ne sont pas configurés.
 *
 * On considère que les horaires sont "configurés" si l'admin les a
 * modifiés après la création initiale du singleton.
 */
class HorairesWarningTest extends TestCase
{
    use RefreshDatabase;

    protected Gestionnaire $gestionnaire;
    protected Administrateur $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->gestionnaire = Gestionnaire::factory()->create();
        $this->gestionnaire->assignRole(Role::Gestionnaire->value);

        $this->admin = Administrateur::factory()->create();
        $this->admin->assignRole(Role::Administrateur->value);
    }

    // ========================================================================
    // Méthode isConfigured() sur le modèle
    // ========================================================================

    public function test_is_configured_false_sur_les_defaults(): void
    {
        $config = JoursTravail::current();
        // Le singleton vient d'être créé → pas encore touché
        $this->assertFalse($config->isConfigured());
    }

    public function test_is_configured_true_apres_modification(): void
    {
        $config = JoursTravail::current();

        // Simule l'écoulement du temps + un update de l'admin
        sleep(3);
        $config->update(['heure_arrivee' => '09:00']);

        $this->assertTrue($config->fresh()->isConfigured());
    }

    public function test_is_current_configured_raccourci(): void
    {
        $this->assertFalse(JoursTravail::isCurrentConfigured());

        $config = JoursTravail::current();
        sleep(3);
        $config->update(['heure_arrivee' => '09:00']);

        $this->assertTrue(JoursTravail::isCurrentConfigured());
    }

    // ========================================================================
    // Bannière affichée sur la vue retards
    // ========================================================================

    public function test_banniere_visible_sur_retards_si_non_configure(): void
    {
        $this->actingAs($this->gestionnaire)
            ->get(route('pointages.retards'))
            ->assertOk()
            ->assertSee('Horaires de travail non configurés', false);
    }

    public function test_banniere_cachee_sur_retards_si_configure(): void
    {
        $config = JoursTravail::current();
        sleep(3);
        $config->update(['heure_arrivee' => '09:00']);

        $this->actingAs($this->gestionnaire)
            ->get(route('pointages.retards'))
            ->assertOk()
            ->assertDontSee('Horaires de travail non configurés', false);
    }

    public function test_banniere_contient_lien_vers_la_configuration(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('pointages.retards'));

        $response->assertOk();
        $response->assertSee(route('admin.horaires.edit'), false);
        $response->assertSee('Configurer maintenant', false);
    }

    public function test_banniere_visible_mais_sans_bouton_pour_consultant(): void
    {
        // Le consultant n'a pas la permission horaires.configure
        $consultant = \App\Models\Consultant::factory()->create();
        $consultant->assignRole(Role::Consultant->value);

        $response = $this->actingAs($consultant)
            ->get(route('pointages.retards'));

        // La bannière est là (avertissement informatif)
        $response->assertSee('Horaires de travail non configurés', false);
        // Mais pas le bouton (parce que le consultant ne peut pas configurer)
        $response->assertDontSee('Configurer maintenant', false);
    }
}
