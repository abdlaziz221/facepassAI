<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\DemandeAbsence;
use App\Models\Employe;
use App\Models\EmployeProfile;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de détection de chevauchement d'absences (Sprint 4 Horaires carte 8, US-051).
 *
 * Quand un employé soumet une nouvelle demande, on refuse si elle
 * chevauche une autre demande en_attente ou validée du même employé.
 * Les demandes refusées n'empêchent PAS une nouvelle demande.
 */
class DemandeAbsenceOverlapTest extends TestCase
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
        $this->profile = EmployeProfile::factory()->create(['user_id' => $this->employe->id]);
    }

    protected function payload(string $debut, string $fin, string $motif = 'Test absence'): array
    {
        return [
            'date_debut' => $debut,
            'date_fin'   => $fin,
            'motif'      => $motif,
        ];
    }

    // ============================================================
    // Helper modèle hasOverlap()
    // ============================================================

    public function test_has_overlap_detecte_chevauchement_exact(): void
    {
        DemandeAbsence::factory()->for($this->profile, 'employe')->create([
            'date_debut' => '2026-06-10',
            'date_fin'   => '2026-06-15',
            'statut'     => DemandeAbsence::STATUT_EN_ATTENTE,
        ]);

        $this->assertTrue(
            DemandeAbsence::hasOverlap($this->profile->id, '2026-06-10', '2026-06-15')
        );
    }

    public function test_has_overlap_detecte_chevauchement_partiel_au_debut(): void
    {
        DemandeAbsence::factory()->for($this->profile, 'employe')->create([
            'date_debut' => '2026-06-10',
            'date_fin'   => '2026-06-15',
            'statut'     => DemandeAbsence::STATUT_EN_ATTENTE,
        ]);

        // Nouvelle demande qui chevauche par le début
        $this->assertTrue(
            DemandeAbsence::hasOverlap($this->profile->id, '2026-06-13', '2026-06-20')
        );
    }

    public function test_has_overlap_detecte_chevauchement_partiel_a_la_fin(): void
    {
        DemandeAbsence::factory()->for($this->profile, 'employe')->create([
            'date_debut' => '2026-06-10',
            'date_fin'   => '2026-06-15',
            'statut'     => DemandeAbsence::STATUT_EN_ATTENTE,
        ]);

        $this->assertTrue(
            DemandeAbsence::hasOverlap($this->profile->id, '2026-06-05', '2026-06-12')
        );
    }

    public function test_has_overlap_detecte_demande_englobante(): void
    {
        // Demande existante de 1 jour
        DemandeAbsence::factory()->for($this->profile, 'employe')->create([
            'date_debut' => '2026-06-12',
            'date_fin'   => '2026-06-12',
            'statut'     => DemandeAbsence::STATUT_EN_ATTENTE,
        ]);

        // Nouvelle demande englobante
        $this->assertTrue(
            DemandeAbsence::hasOverlap($this->profile->id, '2026-06-10', '2026-06-20')
        );
    }

    public function test_has_overlap_pas_de_chevauchement_si_dates_separees(): void
    {
        DemandeAbsence::factory()->for($this->profile, 'employe')->create([
            'date_debut' => '2026-06-10',
            'date_fin'   => '2026-06-15',
            'statut'     => DemandeAbsence::STATUT_EN_ATTENTE,
        ]);

        // Nouvelle demande complètement après
        $this->assertFalse(
            DemandeAbsence::hasOverlap($this->profile->id, '2026-06-16', '2026-06-20')
        );

        // Nouvelle demande complètement avant
        $this->assertFalse(
            DemandeAbsence::hasOverlap($this->profile->id, '2026-06-01', '2026-06-09')
        );
    }

    public function test_has_overlap_ignore_les_demandes_refusees(): void
    {
        DemandeAbsence::factory()->for($this->profile, 'employe')->create([
            'date_debut' => '2026-06-10',
            'date_fin'   => '2026-06-15',
            'statut'     => DemandeAbsence::STATUT_REFUSEE,
        ]);

        $this->assertFalse(
            DemandeAbsence::hasOverlap($this->profile->id, '2026-06-10', '2026-06-15')
        );
    }

    public function test_has_overlap_inclut_les_demandes_validees(): void
    {
        DemandeAbsence::factory()->for($this->profile, 'employe')->create([
            'date_debut' => '2026-06-10',
            'date_fin'   => '2026-06-15',
            'statut'     => DemandeAbsence::STATUT_VALIDEE,
        ]);

        $this->assertTrue(
            DemandeAbsence::hasOverlap($this->profile->id, '2026-06-12', '2026-06-18')
        );
    }

    public function test_has_overlap_ignore_les_demandes_d_autres_employes(): void
    {
        $autreProfile = EmployeProfile::factory()->create();
        DemandeAbsence::factory()->for($autreProfile, 'employe')->create([
            'date_debut' => '2026-06-10',
            'date_fin'   => '2026-06-15',
            'statut'     => DemandeAbsence::STATUT_EN_ATTENTE,
        ]);

        $this->assertFalse(
            DemandeAbsence::hasOverlap($this->profile->id, '2026-06-10', '2026-06-15')
        );
    }

    public function test_has_overlap_exclude_id_permet_l_update(): void
    {
        $demande = DemandeAbsence::factory()->for($this->profile, 'employe')->create([
            'date_debut' => '2026-06-10',
            'date_fin'   => '2026-06-15',
            'statut'     => DemandeAbsence::STATUT_EN_ATTENTE,
        ]);

        // Sans exclusion → chevauche (avec elle-même)
        $this->assertTrue(
            DemandeAbsence::hasOverlap($this->profile->id, '2026-06-10', '2026-06-15')
        );

        // Avec exclusion → ne chevauche pas
        $this->assertFalse(
            DemandeAbsence::hasOverlap($this->profile->id, '2026-06-10', '2026-06-15', $demande->id)
        );
    }

    // ============================================================
    // Intégration : POST /demandes-absence
    // ============================================================

    public function test_post_refuse_si_chevauchement_avec_demande_en_attente(): void
    {
        DemandeAbsence::factory()->for($this->profile, 'employe')->create([
            'date_debut' => now()->addDays(10)->format('Y-m-d'),
            'date_fin'   => now()->addDays(15)->format('Y-m-d'),
            'statut'     => DemandeAbsence::STATUT_EN_ATTENTE,
        ]);

        $response = $this->actingAs($this->employe)
            ->post('/demandes-absence', $this->payload(
                now()->addDays(12)->format('Y-m-d'),
                now()->addDays(18)->format('Y-m-d')
            ));

        $response->assertSessionHasErrors('date_debut');
        $error = session('errors')->get('date_debut')[0];
        $this->assertStringContainsString('chevauche', $error);
        $this->assertStringContainsString('en attente', $error);

        // Une seule demande en BD, pas deux
        $this->assertEquals(1, DemandeAbsence::count());
    }

    public function test_post_refuse_si_chevauchement_avec_demande_validee(): void
    {
        DemandeAbsence::factory()->for($this->profile, 'employe')->create([
            'date_debut' => now()->addDays(10)->format('Y-m-d'),
            'date_fin'   => now()->addDays(15)->format('Y-m-d'),
            'statut'     => DemandeAbsence::STATUT_VALIDEE,
        ]);

        $response = $this->actingAs($this->employe)
            ->post('/demandes-absence', $this->payload(
                now()->addDays(13)->format('Y-m-d'),
                now()->addDays(20)->format('Y-m-d')
            ));

        $response->assertSessionHasErrors('date_debut');
        $error = session('errors')->get('date_debut')[0];
        $this->assertStringContainsString('validée', $error);
        $this->assertEquals(1, DemandeAbsence::count());
    }

    public function test_post_accepte_si_chevauchement_avec_demande_refusee(): void
    {
        DemandeAbsence::factory()->for($this->profile, 'employe')->create([
            'date_debut' => now()->addDays(10)->format('Y-m-d'),
            'date_fin'   => now()->addDays(15)->format('Y-m-d'),
            'statut'     => DemandeAbsence::STATUT_REFUSEE,
        ]);

        $this->actingAs($this->employe)
            ->post('/demandes-absence', $this->payload(
                now()->addDays(12)->format('Y-m-d'),
                now()->addDays(18)->format('Y-m-d')
            ))
            ->assertSessionHasNoErrors();

        $this->assertEquals(2, DemandeAbsence::count());
    }

    public function test_post_accepte_dates_consecutives(): void
    {
        DemandeAbsence::factory()->for($this->profile, 'employe')->create([
            'date_debut' => now()->addDays(10)->format('Y-m-d'),
            'date_fin'   => now()->addDays(15)->format('Y-m-d'),
            'statut'     => DemandeAbsence::STATUT_EN_ATTENTE,
        ]);

        // Nouvelle demande qui commence le LENDEMAIN de la fin précédente
        $this->actingAs($this->employe)
            ->post('/demandes-absence', $this->payload(
                now()->addDays(16)->format('Y-m-d'),
                now()->addDays(20)->format('Y-m-d')
            ))
            ->assertSessionHasNoErrors();

        $this->assertEquals(2, DemandeAbsence::count());
    }
}
