<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Employe;
use App\Models\EmployeProfile;
use App\Services\PayrollService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint 6 cartes 3 + 4 (US-082 / US-083) — Export PDF Mon Salaire +
 * gestion des données incomplètes.
 */
class MonSalairePdfTest extends TestCase
{
    use RefreshDatabase;

    protected Employe $employe;
    protected EmployeProfile $profile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->employe = Employe::factory()->create(['name' => 'Aïssatou Sow']);
        $this->employe->assignRole(Role::Employe->value);

        $this->profile = EmployeProfile::factory()->create([
            'user_id'      => $this->employe->id,
            'salaire_brut' => 440000,
            'matricule'    => 'EMP-001',
        ]);
    }

    // ========================================================================
    // Carte 3 (US-082) — Export PDF
    // ========================================================================

    public function test_pdf_telecharge_avec_bon_content_type(): void
    {
        $response = $this->actingAs($this->employe)
            ->get(route('mon-salaire.pdf', ['mois' => '2026-06']));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/pdf',
            $response->headers->get('Content-Type')
        );
    }

    public function test_pdf_nom_fichier_contient_nom_employe_et_periode(): void
    {
        $response = $this->actingAs($this->employe)
            ->get(route('mon-salaire.pdf', ['mois' => '2026-06']));

        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('salaire-', $disposition);
        $this->assertStringContainsString('aissatou-sow', $disposition);
        $this->assertStringContainsString('2026-06', $disposition);
        $this->assertStringContainsString('.pdf', $disposition);
    }

    public function test_pdf_refuse_si_non_connecte(): void
    {
        $this->get(route('mon-salaire.pdf'))
            ->assertRedirect(route('login'));
    }

    public function test_pdf_refuse_si_pas_de_profile(): void
    {
        $autre = Employe::factory()->create();
        $autre->assignRole(Role::Employe->value);
        // Pas de profil créé

        $this->actingAs($autre)
            ->get(route('mon-salaire.pdf'))
            ->assertNotFound();
    }

    public function test_pdf_mention_personnelle_dans_le_html_rendu(): void
    {
        // On teste la vue Blade directement (avant rendu dompdf binaire)
        $payroll = PayrollService::fromCurrent();
        $salaire = $payroll->calculerSalaireMensuel($this->profile, 2026, 6);

        $rendered = view('pdf.salaire', [
            'profile'    => $this->profile->load('user'),
            'salaire'    => $salaire,
            'manquantes' => [],
            'year'       => 2026,
            'month'      => 6,
        ])->render();

        $this->assertStringContainsString('Aïssatou Sow', $rendered);
        $this->assertStringContainsString('EMP-001', $rendered);
        $this->assertStringContainsString('confidentiel', $rendered);
    }

    public function test_pdf_html_affiche_montants_en_fcfa(): void
    {
        $payroll = PayrollService::fromCurrent();
        $salaire = $payroll->calculerSalaireMensuel($this->profile, 2026, 6);

        $rendered = view('pdf.salaire', [
            'profile'    => $this->profile->load('user'),
            'salaire'    => $salaire,
            'manquantes' => [],
            'year'       => 2026,
            'month'      => 6,
        ])->render();

        $this->assertStringContainsString('F CFA', $rendered);
        $this->assertStringContainsString('440 000', $rendered);
    }

    // ========================================================================
    // Carte 4 (US-083) — Données incomplètes
    // ========================================================================

    public function test_donnees_manquantes_detecte_salaire_brut_zero(): void
    {
        $this->profile->update(['salaire_brut' => 0]);

        $manquantes = PayrollService::donneesManquantes($this->profile->fresh());
        $this->assertContains('salaire_brut', $manquantes);
    }

    public function test_donnees_manquantes_detecte_matricule_vide(): void
    {
        $this->profile->update(['matricule' => '']);

        $manquantes = PayrollService::donneesManquantes($this->profile->fresh());
        $this->assertContains('matricule', $manquantes);
    }

    public function test_donnees_manquantes_vide_si_profil_complet(): void
    {
        $manquantes = PayrollService::donneesManquantes($this->profile);
        $this->assertEmpty($manquantes);
    }

    public function test_donnees_manquantes_detecte_les_deux_a_la_fois(): void
    {
        $this->profile->update(['salaire_brut' => 0, 'matricule' => '']);

        $manquantes = PayrollService::donneesManquantes($this->profile->fresh());
        $this->assertCount(2, $manquantes);
        $this->assertContains('salaire_brut', $manquantes);
        $this->assertContains('matricule', $manquantes);
    }

    public function test_vue_index_affiche_alerte_si_donnees_manquantes(): void
    {
        $this->profile->update(['salaire_brut' => 0]);

        $response = $this->actingAs($this->employe)
            ->get(route('mon-salaire.index'));

        $response->assertOk()
            ->assertSee('Données incomplètes', false)
            ->assertSee('calcul partiel', false)
            ->assertSee('Contacter l\'administrateur', false);
    }

    public function test_vue_index_n_affiche_pas_alerte_si_complet(): void
    {
        $this->actingAs($this->employe)
            ->get(route('mon-salaire.index'))
            ->assertOk()
            ->assertDontSee('Données incomplètes', false);
    }

    public function test_pdf_genere_meme_avec_donnees_incompletes(): void
    {
        $this->profile->update(['salaire_brut' => 0]);

        // Le PDF doit quand même être généré (calcul partiel)
        $response = $this->actingAs($this->employe)
            ->get(route('mon-salaire.pdf', ['mois' => '2026-06']));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_pdf_html_affiche_alerte_si_donnees_manquantes(): void
    {
        $rendered = view('pdf.salaire', [
            'profile'    => $this->profile->load('user'),
            'salaire'    => ['brut' => 0, 'deductions' => ['retards' => ['minutes' => 0, 'montant' => 0], 'departs_anticipes' => ['minutes' => 0, 'montant' => 0], 'absences' => ['jours' => 0, 'jours_detail' => [], 'montant' => 0], 'total' => 0, 'meta' => ['jours_ouvrables_mois' => 22, 'heures_par_jour' => 8, 'heures_mois' => 176, 'tarif_horaire' => 0, 'tarif_minute' => 0, 'tarif_journalier' => 0]], 'net' => 0],
            'manquantes' => ['salaire_brut'],
            'year'       => 2026,
            'month'      => 6,
        ])->render();

        $this->assertStringContainsString('Données incomplètes', $rendered);
        $this->assertStringContainsString('salaire_brut', $rendered);
    }
}
