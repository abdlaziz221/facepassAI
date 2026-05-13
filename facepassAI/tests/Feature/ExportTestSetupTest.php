<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Consultant;
use App\Models\Employe;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint 5 cartes 6 & 7 (US-070 / US-071) — Vérifie que les paquets
 * barryvdh/laravel-dompdf et maatwebsite/excel sont bien installés
 * et utilisables via les routes de test.
 *
 * NB : ces tests échouent tant que les paquets ne sont pas installés
 * via composer require.
 */
class ExportTestSetupTest extends TestCase
{
    use RefreshDatabase;

    protected Consultant $consultant;
    protected Employe $employe;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->consultant = Consultant::factory()->create();
        $this->consultant->assignRole(Role::Consultant->value);

        $this->employe = Employe::factory()->create();
        $this->employe->assignRole(Role::Employe->value);
    }

    // ========================================================================
    // PDF — carte 6 (US-070)
    // ========================================================================

    public function test_pdf_telecharge_avec_bon_content_type(): void
    {
        $response = $this->actingAs($this->consultant)
            ->get(route('test.export.pdf'));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/pdf',
            $response->headers->get('Content-Type')
        );
    }

    public function test_pdf_telecharge_avec_bon_nom_fichier(): void
    {
        $response = $this->actingAs($this->consultant)
            ->get(route('test.export.pdf'));

        $response->assertOk();
        $this->assertStringContainsString(
            'test-facepass.pdf',
            $response->headers->get('Content-Disposition')
        );
    }

    public function test_pdf_refuse_sans_permission_export(): void
    {
        // L'employé n'a pas la permission rapports.export
        $this->actingAs($this->employe)
            ->get(route('test.export.pdf'))
            ->assertForbidden();
    }

    public function test_pdf_redirige_si_non_connecte(): void
    {
        $this->get(route('test.export.pdf'))
            ->assertRedirect(route('login'));
    }

    // ========================================================================
    // Excel — carte 7 (US-071)
    // ========================================================================

    public function test_excel_telecharge_avec_bon_content_type(): void
    {
        $response = $this->actingAs($this->consultant)
            ->get(route('test.export.excel'));

        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheet',
            $response->headers->get('Content-Type')
        );
    }

    public function test_excel_telecharge_avec_bon_nom_fichier(): void
    {
        $response = $this->actingAs($this->consultant)
            ->get(route('test.export.excel'));

        $response->assertOk();
        $this->assertStringContainsString(
            'test-facepass.xlsx',
            $response->headers->get('Content-Disposition')
        );
    }

    public function test_excel_refuse_sans_permission_export(): void
    {
        $this->actingAs($this->employe)
            ->get(route('test.export.excel'))
            ->assertForbidden();
    }

    public function test_excel_redirige_si_non_connecte(): void
    {
        $this->get(route('test.export.excel'))
            ->assertRedirect(route('login'));
    }
}
