<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Employe;
use App\Models\EmployeProfile;
use App\Models\Pointage;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint 6 carte 2 (US-080) — Tests de la vue Mon Salaire (employé).
 */
class MonSalaireTest extends TestCase
{
    use RefreshDatabase;

    protected Employe $employe;
    protected EmployeProfile $profile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->employe = Employe::factory()->create();
        $this->employe->assignRole(Role::Employe->value);

        $this->profile = EmployeProfile::factory()->create([
            'user_id'      => $this->employe->id,
            'salaire_brut' => 440000,
        ]);
    }

    // ========================================================================
    // Accès
    // ========================================================================

    public function test_page_accessible_a_l_employe(): void
    {
        $this->actingAs($this->employe)
            ->get(route('mon-salaire.index'))
            ->assertOk()
            ->assertViewIs('mon-salaire.index')
            ->assertSee('Mon salaire', false);
    }

    public function test_page_redirige_si_non_connecte(): void
    {
        $this->get(route('mon-salaire.index'))
            ->assertRedirect(route('login'));
    }

    public function test_page_refuse_au_user_sans_permission(): void
    {
        // Note : consultant/gestionnaire/admin heritent de salaire.view-own
        // (perm employe propagee dans le RolePermissionSeeder). Donc le seul
        // cas de refus c'est un user sans aucun role.
        $user = \App\Models\User::factory()->create();

        $this->actingAs($user)
            ->get(route('mon-salaire.index'))
            ->assertForbidden();
    }

    // ========================================================================
    // Sélecteur de mois
    // ========================================================================

    public function test_mois_par_defaut_est_le_mois_courant(): void
    {
        $response = $this->actingAs($this->employe)
            ->get(route('mon-salaire.index'));

        $response->assertOk();
        $this->assertEquals(now()->format('Y-m'), $response->viewData('moisInput'));
        $this->assertEquals((int) now()->format('Y'), $response->viewData('year'));
        $this->assertEquals((int) now()->format('m'), $response->viewData('month'));
    }

    public function test_mois_via_query_string(): void
    {
        $response = $this->actingAs($this->employe)
            ->get(route('mon-salaire.index', ['mois' => '2026-03']));

        $this->assertEquals('2026-03', $response->viewData('moisInput'));
        $this->assertEquals(2026, $response->viewData('year'));
        $this->assertEquals(3, $response->viewData('month'));
    }

    public function test_mois_invalide_fallback_sur_mois_courant(): void
    {
        $response = $this->actingAs($this->employe)
            ->get(route('mon-salaire.index', ['mois' => 'pwned']));

        $this->assertEquals(now()->format('Y-m'), $response->viewData('moisInput'));
    }

    // ========================================================================
    // Contenu (brut / déductions / net)
    // ========================================================================

    public function test_vue_affiche_le_salaire_brut(): void
    {
        $this->actingAs($this->employe)
            ->get(route('mon-salaire.index'))
            ->assertSee('440 000', false)
            ->assertSee('Salaire brut', false);
    }

    public function test_vue_affiche_les_trois_kpis_brut_deductions_net(): void
    {
        $response = $this->actingAs($this->employe)
            ->get(route('mon-salaire.index'));

        $response->assertSee('Salaire brut', false);
        $response->assertSee('Total déductions', false);
        $response->assertSee('Salaire net', false);
    }

    public function test_vue_affiche_le_tableau_detail_avec_les_trois_postes(): void
    {
        $response = $this->actingAs($this->employe)
            ->get(route('mon-salaire.index'));

        $response->assertSee('Retards', false);
        $response->assertSee('Départs anticipés', false);
        $response->assertSee('Absences non justifiées', false);
    }

    public function test_pointages_du_mois_listes_si_present(): void
    {
        $p = Pointage::factory()->for($this->profile, 'employe')->create(['type' => 'arrivee']);
        $p->forceFill(['created_at' => now()->startOfMonth()->addDays(5)->setTime(8, 15)])->save();

        $response = $this->actingAs($this->employe)
            ->get(route('mon-salaire.index', ['mois' => now()->format('Y-m')]));

        $response->assertSee('Mes pointages du mois', false);
        $response->assertSee('Arrivee', false);
    }

    // ========================================================================
    // Profil métier absent
    // ========================================================================

    public function test_employe_sans_profile_voit_un_message(): void
    {
        $sansProfile = Employe::factory()->create();
        $sansProfile->assignRole(Role::Employe->value);

        $this->actingAs($sansProfile)
            ->get(route('mon-salaire.index'))
            ->assertOk()
            ->assertSee('Profil métier introuvable', false);
    }
}
