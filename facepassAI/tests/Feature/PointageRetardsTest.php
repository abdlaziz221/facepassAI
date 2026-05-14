<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Consultant;
use App\Models\Employe;
use App\Models\EmployeProfile;
use App\Models\Gestionnaire;
use App\Models\JoursTravail;
use App\Models\Pointage;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint 5 carte 4 (US-062) — Vue retards & départs anticipés + export CSV.
 */
class PointageRetardsTest extends TestCase
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

        // Configuration par défaut : 08:00 / 12:00 / 13:00 / 17:00
        JoursTravail::current();

        $this->gestionnaire = Gestionnaire::factory()->create();
        $this->gestionnaire->assignRole(Role::Gestionnaire->value);

        $this->consultant = Consultant::factory()->create();
        $this->consultant->assignRole(Role::Consultant->value);

        $this->employe = Employe::factory()->create();
        $this->employe->assignRole(Role::Employe->value);

        $this->emp1 = EmployeProfile::factory()->create(['user_id' => $this->employe->id]);
        $this->emp2 = EmployeProfile::factory()->create();
    }

    protected function makePointage(EmployeProfile $emp, string $type, string $datetime): Pointage
    {
        $p = Pointage::factory()->for($emp, 'employe')->create(['type' => $type]);
        $p->forceFill(['created_at' => $datetime])->save();
        return $p;
    }

    // ========================================================================
    // Accès
    // ========================================================================

    public function test_page_accessible_au_gestionnaire(): void
    {
        $this->actingAs($this->gestionnaire)
            ->get(route('pointages.retards'))
            ->assertOk()
            ->assertViewIs('pointer.retards')
            ->assertSee('Retards & départs anticipés', false);
    }

    public function test_page_accessible_au_consultant(): void
    {
        $this->actingAs($this->consultant)
            ->get(route('pointages.retards'))
            ->assertOk();
    }

    public function test_page_refuse_a_l_employe(): void
    {
        $this->actingAs($this->employe)
            ->get(route('pointages.retards'))
            ->assertForbidden();
    }

    public function test_page_redirige_si_non_connecte(): void
    {
        $this->get(route('pointages.retards'))
            ->assertRedirect(route('login'));
    }

    // ========================================================================
    // Filtrage : ne montre que retards et départs anticipés
    // ========================================================================

    public function test_n_affiche_que_les_anomalies(): void
    {
        // À l'heure : pas listé
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00');
        // En avance : pas listé
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 07:50:00');
        // En retard : listé
        $this->makePointage($this->emp1, 'arrivee', '2026-06-11 08:30:00');
        // Départ anticipé : listé
        $this->makePointage($this->emp1, 'depart',  '2026-06-10 16:30:00');
        // Heures sup : pas listé
        $this->makePointage($this->emp1, 'depart',  '2026-06-11 18:00:00');

        $response = $this->actingAs($this->gestionnaire)
            ->get(route('pointages.retards'));

        $this->assertEquals(2, $response->viewData('pointages')->total());
    }

    public function test_compteurs_kpi_corrects(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:30:00'); // retard
        $this->makePointage($this->emp1, 'arrivee', '2026-06-11 08:15:00'); // retard
        $this->makePointage($this->emp1, 'fin_pause', '2026-06-10 13:20:00'); // retard
        $this->makePointage($this->emp1, 'depart', '2026-06-10 16:30:00'); // depart anticipé

        $response = $this->actingAs($this->gestionnaire)
            ->get(route('pointages.retards'));

        $this->assertEquals(3, $response->viewData('countRetards'));
        $this->assertEquals(1, $response->viewData('countDeparts'));
    }

    // ========================================================================
    // Filtres employé / date / catégorie
    // ========================================================================

    public function test_filtre_par_employe(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:30:00');
        $this->makePointage($this->emp2, 'arrivee', '2026-06-10 08:45:00');

        $response = $this->actingAs($this->gestionnaire)
            ->get(route('pointages.retards', ['employe_id' => $this->emp1->id]));

        $this->assertEquals(1, $response->viewData('pointages')->total());
    }

    public function test_filtre_par_intervalle_de_dates(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-05 08:30:00');
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:30:00');
        $this->makePointage($this->emp1, 'arrivee', '2026-06-15 08:30:00');

        $response = $this->actingAs($this->gestionnaire)
            ->get(route('pointages.retards', [
                'date_from' => '2026-06-08',
                'date_to'   => '2026-06-12',
            ]));

        $this->assertEquals(1, $response->viewData('pointages')->total());
    }

    public function test_filtre_categorie_retard_seulement(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:30:00'); // retard
        $this->makePointage($this->emp1, 'depart',  '2026-06-10 16:30:00'); // depart anticipé

        $response = $this->actingAs($this->gestionnaire)
            ->get(route('pointages.retards', ['categorie' => 'retard']));

        $this->assertEquals(1, $response->viewData('pointages')->total());
    }

    public function test_filtre_categorie_depart_anticipe_seulement(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:30:00'); // retard
        $this->makePointage($this->emp1, 'depart',  '2026-06-10 16:30:00'); // depart anticipé

        $response = $this->actingAs($this->gestionnaire)
            ->get(route('pointages.retards', ['categorie' => 'depart_anticipe']));

        $this->assertEquals(1, $response->viewData('pointages')->total());
    }

    // ========================================================================
    // Pagination
    // ========================================================================

    public function test_pagine_par_20(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            $this->makePointage(
                $this->emp1,
                'arrivee',
                '2026-06-' . str_pad($i, 2, '0', STR_PAD_LEFT) . ' 08:30:00'
            );
        }

        $response = $this->actingAs($this->gestionnaire)
            ->get(route('pointages.retards'));

        $pointages = $response->viewData('pointages');
        $this->assertEquals(20, $pointages->count());
        $this->assertEquals(25, $pointages->total());
    }

    // ========================================================================
    // Empty state
    // ========================================================================

    public function test_message_vide_si_aucune_anomalie(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00');

        $this->actingAs($this->gestionnaire)
            ->get(route('pointages.retards'))
            ->assertOk()
            ->assertSee('Aucun retard');
    }

    // ========================================================================
    // Export CSV
    // ========================================================================

    public function test_export_csv_disponible(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:30:00');

        $response = $this->actingAs($this->gestionnaire)
            ->get(route('pointages.retards.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_export_csv_contient_les_anomalies(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:30:00');
        $this->makePointage($this->emp1, 'depart',  '2026-06-10 16:30:00');

        $response = $this->actingAs($this->gestionnaire)
            ->get(route('pointages.retards.export'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Employé', $content);
        $this->assertStringContainsString('Retard', $content);
        $this->assertStringContainsString('Départ anticipé', $content);
        $this->assertStringContainsString('30', $content); // écart en minutes
    }

    public function test_export_csv_n_inclut_pas_les_pointages_a_l_heure(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00'); // à l'heure
        $this->makePointage($this->emp1, 'arrivee', '2026-06-11 08:30:00'); // retard

        $response = $this->actingAs($this->gestionnaire)
            ->get(route('pointages.retards.export'));

        $content = $response->streamedContent();
        $lines = array_filter(explode("\n", trim($content)));
        // 1 header + 1 ligne de données seulement (le pointage à l'heure exclu)
        $this->assertCount(2, $lines);
    }

    public function test_export_csv_respecte_les_filtres(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:30:00');
        $this->makePointage($this->emp2, 'arrivee', '2026-06-10 08:45:00');

        $response = $this->actingAs($this->gestionnaire)
            ->get(route('pointages.retards.export', ['employe_id' => $this->emp1->id]));

        $content = $response->streamedContent();
        $lines = array_filter(explode("\n", trim($content)));
        $this->assertCount(2, $lines); // 1 header + 1 data
    }

    public function test_export_refuse_a_l_employe(): void
    {
        $this->actingAs($this->employe)
            ->get(route('pointages.retards.export'))
            ->assertForbidden();
    }
}
