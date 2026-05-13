<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\Administrateur;
use App\Models\EmployeProfile;
use App\Models\Employe;
use App\Models\Gestionnaire;
use App\Models\JourFerie;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Sprint 6 cartes 6/7/8/9 (US-091/092) — Logs activitylog + consultation + exports.
 */
class LogControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Administrateur $admin;
    protected Employe $employe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = Administrateur::factory()->create();
        $this->admin->assignRole(Role::Administrateur->value);

        $this->employe = Employe::factory()->create();
        $this->employe->assignRole(Role::Employe->value);
    }

    // ========================================================================
    // Carte 6 (US-091) — LogsActivity trait sur les modèles
    // ========================================================================

    public function test_log_genere_a_la_creation_d_un_jour_ferie(): void
    {
        $this->actingAs($this->admin);
        JourFerie::create(['date' => '2026-12-25', 'libelle' => 'Noël']);

        $log = Activity::query()->latest()->first();
        $this->assertNotNull($log);
        $this->assertEquals('jours_feries', $log->log_name);
        $this->assertEquals('Jour férié ajouté', $log->description);
    }

    public function test_log_genere_a_la_suppression_d_un_jour_ferie(): void
    {
        $this->actingAs($this->admin);
        $jf = JourFerie::create(['date' => '2026-12-25', 'libelle' => 'Noël']);
        $jf->delete();

        $logs = Activity::query()->where('log_name', 'jours_feries')->get();
        $this->assertGreaterThanOrEqual(2, $logs->count()); // create + delete
        $this->assertTrue($logs->contains(fn ($l) => $l->description === 'Jour férié supprimé'));
    }

    public function test_log_capture_le_causer_authentifie(): void
    {
        $this->actingAs($this->admin);
        JourFerie::create(['date' => '2026-11-01', 'libelle' => 'Toussaint']);

        $log = Activity::query()->latest()->first();
        $this->assertEquals($this->admin->id, $log->causer_id);
    }

    public function test_log_employe_a_la_modification(): void
    {
        $this->actingAs($this->admin);
        $profile = EmployeProfile::factory()->create(['poste' => 'Dev', 'salaire_brut' => 400000]);
        $profile->update(['poste' => 'Lead Dev', 'salaire_brut' => 500000]);

        $logs = Activity::query()->where('log_name', 'employes')->get();
        $this->assertGreaterThanOrEqual(1, $logs->count());
        $this->assertTrue($logs->contains(fn ($l) => $l->description === 'Modification employé'));
    }

    // ========================================================================
    // Carte 7 (US-091) — Page de consultation
    // ========================================================================

    public function test_index_accessible_a_l_admin(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.logs.index'))
            ->assertOk()
            ->assertSee('Journal d\'activité', false);
    }

    public function test_index_refuse_a_l_employe(): void
    {
        $this->actingAs($this->employe)
            ->get(route('admin.logs.index'))
            ->assertForbidden();
    }

    public function test_index_redirige_si_non_connecte(): void
    {
        $this->get(route('admin.logs.index'))
            ->assertRedirect(route('login'));
    }

    public function test_index_filtre_par_module(): void
    {
        $this->actingAs($this->admin);
        JourFerie::create(['date' => '2026-12-25', 'libelle' => 'Noël']);
        EmployeProfile::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.logs.index', ['log_name' => 'jours_feries']));

        $logs = $response->viewData('logs');
        foreach ($logs as $log) {
            $this->assertEquals('jours_feries', $log->log_name);
        }
    }

    public function test_index_filtre_par_causer(): void
    {
        $this->actingAs($this->admin);
        JourFerie::create(['date' => '2026-12-25', 'libelle' => 'Noël']);

        $autreAdmin = Administrateur::factory()->create();
        $this->actingAs($autreAdmin);
        JourFerie::create(['date' => '2026-11-11', 'libelle' => 'Armistice']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.logs.index', ['causer_id' => $this->admin->id]));

        $logs = $response->viewData('logs');
        foreach ($logs as $log) {
            $this->assertEquals($this->admin->id, $log->causer_id);
        }
    }

    // ========================================================================
    // Carte 8 (US-092) — Export CSV
    // ========================================================================

    public function test_export_csv_telecharge_avec_bon_content_type(): void
    {
        $this->actingAs($this->admin);
        JourFerie::create(['date' => '2026-12-25', 'libelle' => 'Noël']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.logs.export', ['format' => 'csv']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.csv', $response->headers->get('Content-Disposition'));
    }

    public function test_export_csv_contient_l_entete_et_les_lignes(): void
    {
        $this->actingAs($this->admin);
        JourFerie::create(['date' => '2026-12-25', 'libelle' => 'Noël']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.logs.export', ['format' => 'csv']));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Date;Utilisateur;Module;Action;Cible', $content);
        $this->assertStringContainsString('Jour férié ajouté', $content);
    }

    public function test_export_csv_respecte_les_filtres(): void
    {
        $this->actingAs($this->admin);
        JourFerie::create(['date' => '2026-12-25', 'libelle' => 'Noël']);
        EmployeProfile::factory()->create(['matricule' => 'XYZ']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.logs.export', ['format' => 'csv', 'log_name' => 'jours_feries']));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Jour férié ajouté', $content);
        $this->assertStringNotContainsString('Création employé', $content);
    }

    // ========================================================================
    // Carte 9 (US-092) — Export PDF + TXT
    // ========================================================================

    public function test_export_pdf_telecharge_avec_bon_content_type(): void
    {
        $this->actingAs($this->admin);
        JourFerie::create(['date' => '2026-12-25', 'libelle' => 'Noël']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.logs.export', ['format' => 'pdf']));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_export_txt_telecharge_avec_bon_content_type(): void
    {
        $this->actingAs($this->admin);
        JourFerie::create(['date' => '2026-12-25', 'libelle' => 'Noël']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.logs.export', ['format' => 'txt']));

        $response->assertOk();
        $this->assertStringContainsString('text/plain', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.txt', $response->headers->get('Content-Disposition'));
    }

    public function test_export_txt_contient_les_entrees(): void
    {
        $this->actingAs($this->admin);
        JourFerie::create(['date' => '2026-12-25', 'libelle' => 'Noël']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.logs.export', ['format' => 'txt']));

        $content = $response->getContent();
        $this->assertStringContainsString("Journal d'activité FacePass.AI", $content);
        $this->assertStringContainsString('Jour férié ajouté', $content);
        $this->assertStringContainsString($this->admin->name, $content);
    }

    public function test_export_format_inconnu_fallback_sur_csv(): void
    {
        $this->actingAs($this->admin);
        JourFerie::create(['date' => '2026-12-25', 'libelle' => 'Noël']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.logs.export', ['format' => 'docx']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_export_refuse_a_l_employe(): void
    {
        $this->actingAs($this->employe)
            ->get(route('admin.logs.export', ['format' => 'csv']))
            ->assertForbidden();
    }
}
