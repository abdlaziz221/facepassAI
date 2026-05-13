<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Exports\RapportPresenceExport;
use App\Models\Consultant;
use App\Models\Employe;
use App\Models\EmployeProfile;
use App\Models\Gestionnaire;
use App\Models\Pointage;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

/**
 * Sprint 5 cartes 8, 9, 10 (US-070 / US-071 / US-072)
 * Tests du module de génération de rapports (PDF + Excel + UI).
 */
class RapportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Consultant $consultant;
    protected Gestionnaire $gestionnaire;
    protected Employe $employe;
    protected EmployeProfile $emp1;
    protected EmployeProfile $emp2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->consultant = Consultant::factory()->create();
        $this->consultant->assignRole(Role::Consultant->value);

        $this->gestionnaire = Gestionnaire::factory()->create();
        $this->gestionnaire->assignRole(Role::Gestionnaire->value);

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

    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'date_debut' => '2026-06-01',
            'date_fin'   => '2026-06-30',
            'type'       => 'presences',
            'format'     => 'pdf',
        ], $overrides);
    }

    // ========================================================================
    // GET /rapports — Formulaire
    // ========================================================================

    public function test_index_accessible_au_consultant(): void
    {
        $this->actingAs($this->consultant)
            ->get(route('rapports.index'))
            ->assertOk()
            ->assertViewIs('rapports.index')
            ->assertSee('Générer un rapport', false);
    }

    public function test_index_accessible_au_gestionnaire(): void
    {
        $this->actingAs($this->gestionnaire)
            ->get(route('rapports.index'))
            ->assertOk();
    }

    public function test_index_refuse_a_l_employe(): void
    {
        $this->actingAs($this->employe)
            ->get(route('rapports.index'))
            ->assertForbidden();
    }

    public function test_index_redirige_si_non_connecte(): void
    {
        $this->get(route('rapports.index'))
            ->assertRedirect(route('login'));
    }

    public function test_index_contient_les_4_sections_du_formulaire(): void
    {
        $this->actingAs($this->consultant)
            ->get(route('rapports.index'))
            ->assertSee('Type de rapport', false)
            ->assertSee('Période', false)
            ->assertSee('Périmètre', false)
            ->assertSee('Format', false);
    }

    // ========================================================================
    // POST /rapports/generer — Téléchargement PDF
    // ========================================================================

    public function test_generation_pdf_telecharge_avec_bon_content_type(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00');

        $response = $this->actingAs($this->consultant)
            ->post(route('rapports.generer'), $this->validPayload(['format' => 'pdf']));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/pdf',
            $response->headers->get('Content-Type')
        );
    }

    public function test_generation_pdf_nom_fichier_contient_la_periode(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00');

        $response = $this->actingAs($this->consultant)
            ->post(route('rapports.generer'), $this->validPayload([
                'date_debut' => '2026-06-01',
                'date_fin'   => '2026-06-30',
                'format'     => 'pdf',
            ]));

        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('2026-06-01', $disposition);
        $this->assertStringContainsString('2026-06-30', $disposition);
        $this->assertStringContainsString('.pdf', $disposition);
    }

    // ========================================================================
    // POST /rapports/generer — Téléchargement Excel
    // ========================================================================

    public function test_generation_excel_telecharge_avec_bon_content_type(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00');

        $response = $this->actingAs($this->consultant)
            ->post(route('rapports.generer'), $this->validPayload(['format' => 'excel']));

        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheet',
            $response->headers->get('Content-Type')
        );
    }

    public function test_generation_excel_nom_fichier_xlsx(): void
    {
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00');

        $response = $this->actingAs($this->consultant)
            ->post(route('rapports.generer'), $this->validPayload(['format' => 'excel']));

        $this->assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'));
    }

    // ========================================================================
    // Validation Form Request
    // ========================================================================

    public function test_validation_date_debut_obligatoire(): void
    {
        $this->actingAs($this->consultant)
            ->post(route('rapports.generer'), $this->validPayload(['date_debut' => '']))
            ->assertSessionHasErrors('date_debut');
    }

    public function test_validation_date_fin_apres_date_debut(): void
    {
        $this->actingAs($this->consultant)
            ->post(route('rapports.generer'), $this->validPayload([
                'date_debut' => '2026-06-30',
                'date_fin'   => '2026-06-01',
            ]))
            ->assertSessionHasErrors('date_fin');
    }

    public function test_validation_format_doit_etre_pdf_ou_excel(): void
    {
        $this->actingAs($this->consultant)
            ->post(route('rapports.generer'), $this->validPayload(['format' => 'word']))
            ->assertSessionHasErrors('format');
    }

    public function test_validation_type_doit_etre_presences(): void
    {
        $this->actingAs($this->consultant)
            ->post(route('rapports.generer'), $this->validPayload(['type' => 'inconnu']))
            ->assertSessionHasErrors('type');
    }

    public function test_validation_employe_inexistant_rejete(): void
    {
        $this->actingAs($this->consultant)
            ->post(route('rapports.generer'), $this->validPayload(['employe_id' => 999_999]))
            ->assertSessionHasErrors('employe_id');
    }

    // ========================================================================
    // Filtres par date / employé
    // ========================================================================

    public function test_filtre_par_periode_exclu_les_pointages_hors_intervalle(): void
    {
        Excel::fake();

        // Hors intervalle (avant)
        $this->makePointage($this->emp1, 'arrivee', '2026-05-15 08:00:00');
        // Dans intervalle
        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00');
        // Hors intervalle (après)
        $this->makePointage($this->emp1, 'arrivee', '2026-07-15 08:00:00');

        $this->actingAs($this->consultant)
            ->post(route('rapports.generer'), $this->validPayload([
                'date_debut' => '2026-06-01',
                'date_fin'   => '2026-06-30',
                'format'     => 'excel',
            ]))
            ->assertOk();

        Excel::assertDownloaded(
            'rapport-presences-2026-06-01-au-2026-06-30.xlsx',
            function (RapportPresenceExport $export) {
                return $export->pointages->count() === 1
                    && $export->pointages->first()->created_at->format('Y-m-d') === '2026-06-10';
            }
        );
    }

    public function test_filtre_par_employe(): void
    {
        Excel::fake();

        $this->makePointage($this->emp1, 'arrivee', '2026-06-10 08:00:00');
        $this->makePointage($this->emp2, 'arrivee', '2026-06-10 08:30:00');

        $this->actingAs($this->consultant)
            ->post(route('rapports.generer'), $this->validPayload([
                'employe_id' => $this->emp1->id,
                'format'     => 'excel',
            ]))
            ->assertOk();

        Excel::assertDownloaded(
            'rapport-presences-2026-06-01-au-2026-06-30.xlsx',
            function (RapportPresenceExport $export) {
                return $export->pointages->count() === 1
                    && (int) $export->pointages->first()->employe_id === $this->emp1->id;
            }
        );
    }

    // ========================================================================
    // Permission
    // ========================================================================

    public function test_generation_refuse_a_l_employe(): void
    {
        $this->actingAs($this->employe)
            ->post(route('rapports.generer'), $this->validPayload())
            ->assertForbidden();
    }

    public function test_generation_redirige_si_non_connecte(): void
    {
        $this->post(route('rapports.generer'), $this->validPayload())
            ->assertRedirect(route('login'));
    }
}
