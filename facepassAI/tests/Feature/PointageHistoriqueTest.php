<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Consultant;
use App\Models\Employe;
use App\Models\EmployeProfile;
use App\Models\Gestionnaire;
use App\Models\Pointage;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint 5 carte 1 (US-060) — Tests du contrôleur d'historique des pointages.
 */
class PointageHistoriqueTest extends TestCase
{
    use RefreshDatabase;

    protected Gestionnaire $gestionnaire;
    protected Consultant $consultant;
    protected Employe $employe;
    protected EmployeProfile $emp1;
    protected EmployeProfile $emp2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->gestionnaire = Gestionnaire::factory()->create();
        $this->gestionnaire->assignRole(Role::Gestionnaire->value);

        $this->consultant = Consultant::factory()->create();
        $this->consultant->assignRole(Role::Consultant->value);

        $this->employe = Employe::factory()->create();
        $this->employe->assignRole(Role::Employe->value);

        $this->emp1 = EmployeProfile::factory()->create(['user_id' => $this->employe->id]);
        $this->emp2 = EmployeProfile::factory()->create();
    }

    protected function makePointage(EmployeProfile $emp, string $type, string $datetime, bool $manuel = false): Pointage
    {
        $p = Pointage::factory()->for($emp, 'employe')->create([
            'type'   => $type,
            'manuel' => $manuel,
        ]);
        $p->forceFill(['created_at' => $datetime])->save();
        return $p;
    }

    // ========================================================================
    // Accès
    // ========================================================================

    public function test_page_accessible_au_gestionnaire(): void
    {
        $this->actingAs($this->gestionnaire)
            ->get(route('pointages.historique'))
            ->assertOk()
            ->assertViewIs('pointer.historique')
            ->assertSee('Historique des pointages', false);
    }

    public function test_page_accessible_au_consultant(): void
    {
        $this->actingAs($this->consultant)
            ->get(route('pointages.historique'))
            ->assertOk();
    }

    public function test_page_refuse_a_l_employe(): void
    {
        $this->actingAs($this->employe)
            ->get(route('pointages.historique'))
            ->assertForbidden();
    }

    public function test_page_redirige_si_non_connecte(): void
    {
        $this->get(route('pointages.historique'))
            ->assertRedirect(route('login'));
    }

    // ========================================================================
    // Affichage et filtres
    // ========================================================================

    public function test_liste_tous_les_pointages_par_defaut(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00');
        $this->makePointage($this->emp1, 'depart',  '2026-06-10 17:00:00');
        $this->makePointage($this->emp2, 'arrivee', '2026-06-10 08:30:00');

        $response = $this->actingAs($this->gestionnaire)
            ->get(route('pointages.historique'));

        $this->assertEquals(3, $response->viewData('pointages')->total());
    }

    public function test_filtre_par_employe(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00');
        $this->makePointage($this->emp1, 'depart',  '2026-06-10 17:00:00');
        $this->makePointage($this->emp2, 'arrivee', '2026-06-10 08:30:00');

        $response = $this->actingAs($this->gestionnaire)
            ->get(route('pointages.historique', ['employe_id' => $this->emp1->id]));

        $this->assertEquals(2, $response->viewData('pointages')->total());
    }

    public function test_filtre_par_intervalle_de_dates(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-09 08:00:00');
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00');
        $this->makePointage($this->emp1, 'arrivee', '2026-06-11 08:00:00');
        $this->makePointage($this->emp1, 'arrivee', '2026-06-12 08:00:00');

        $response = $this->actingAs($this->gestionnaire)
            ->get(route('pointages.historique', [
                'date_from' => '2026-06-10',
                'date_to'   => '2026-06-11',
            ]));

        $this->assertEquals(2, $response->viewData('pointages')->total());
    }

    public function test_filtre_par_type(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00');
        $this->makePointage($this->emp1, 'arrivee', '2026-06-11 08:00:00');
        $this->makePointage($this->emp1, 'depart',  '2026-06-10 17:00:00');

        $response = $this->actingAs($this->gestionnaire)
            ->get(route('pointages.historique', ['type' => 'arrivee']));

        $this->assertEquals(2, $response->viewData('pointages')->total());
    }

    public function test_filtres_combines(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00'); // ✓
        $this->makePointage($this->emp1, 'depart',  '2026-06-10 17:00:00'); // ✗ type
        $this->makePointage($this->emp1, 'arrivee', '2026-06-11 08:00:00'); // ✗ date
        $this->makePointage($this->emp2, 'arrivee', '2026-06-10 08:30:00'); // ✗ emp

        $response = $this->actingAs($this->gestionnaire)
            ->get(route('pointages.historique', [
                'employe_id' => $this->emp1->id,
                'date_from'  => '2026-06-10',
                'date_to'    => '2026-06-10',
                'type'       => 'arrivee',
            ]));

        $this->assertEquals(1, $response->viewData('pointages')->total());
    }

    // ========================================================================
    // Tri
    // ========================================================================

    public function test_tri_par_date_decroissante_par_defaut(): void
    {
        $vieux  = $this->makePointage($this->emp1, 'arrivee', '2026-06-01 08:00:00');
        $recent = $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00');

        $response = $this->actingAs($this->gestionnaire)
            ->get(route('pointages.historique'));

        $ids = $response->viewData('pointages')->getCollection()->pluck('id')->all();
        $this->assertEquals([$recent->id, $vieux->id], $ids);
    }

    public function test_tri_par_date_croissante_via_dir_asc(): void
    {
        $vieux  = $this->makePointage($this->emp1, 'arrivee', '2026-06-01 08:00:00');
        $recent = $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00');

        $response = $this->actingAs($this->gestionnaire)
            ->get(route('pointages.historique', ['sort' => 'created_at', 'dir' => 'asc']));

        $ids = $response->viewData('pointages')->getCollection()->pluck('id')->all();
        $this->assertEquals([$vieux->id, $recent->id], $ids);
    }

    // ========================================================================
    // Compteurs et liste des employés
    // ========================================================================

    public function test_compteurs_par_type_dans_la_vue(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00');
        $this->makePointage($this->emp1, 'arrivee', '2026-06-11 08:00:00');
        $this->makePointage($this->emp1, 'depart',  '2026-06-10 17:00:00');

        $response = $this->actingAs($this->gestionnaire)
            ->get(route('pointages.historique'));

        $counts = $response->viewData('counts');
        $this->assertEquals(2, $counts['arrivee']);
        $this->assertEquals(1, $counts['depart']);
        $this->assertEquals(0, $counts['debut_pause']);
    }

    public function test_liste_des_employes_ne_contient_que_ceux_avec_pointages(): void
    {
        $emp3 = EmployeProfile::factory()->create(); // pas de pointage

        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00');

        $response = $this->actingAs($this->gestionnaire)
            ->get(route('pointages.historique'));

        $employes = $response->viewData('employes');
        $ids = $employes->pluck('id')->all();

        $this->assertContains($this->emp1->id, $ids);
        $this->assertNotContains($emp3->id, $ids);
    }

    // ========================================================================
    // Pagination
    // ========================================================================

    public function test_pagine_par_20(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->makePointage($this->emp1, 'arrivee',
                '2026-06-' . str_pad($i + 1, 2, '0', STR_PAD_LEFT) . ' 08:00:00');
        }

        $response = $this->actingAs($this->gestionnaire)
            ->get(route('pointages.historique'));

        $pointages = $response->viewData('pointages');
        $this->assertEquals(20, $pointages->count());
        $this->assertEquals(25, $pointages->total());
    }
}
