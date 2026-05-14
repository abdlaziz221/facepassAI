<?php

namespace Tests\Feature\Models;

use App\Enums\Role;
use App\Models\DemandeAbsence;
use App\Models\EmployeProfile;
use App\Models\Gestionnaire;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests du modèle DemandeAbsence (Sprint 4 Horaires carte 6, US-050).
 */
class DemandeAbsenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    // ============================================================
    // Constantes & valeurs par défaut
    // ============================================================

    public function test_les_constantes_de_statut_sont_exposees(): void
    {
        $this->assertEquals('en_attente', DemandeAbsence::STATUT_EN_ATTENTE);
        $this->assertEquals('validee',    DemandeAbsence::STATUT_VALIDEE);
        $this->assertEquals('refusee',    DemandeAbsence::STATUT_REFUSEE);
        $this->assertCount(3, DemandeAbsence::STATUTS);
    }

    public function test_le_modele_utilise_la_bonne_table(): void
    {
        $this->assertEquals('demandes_absence', (new DemandeAbsence())->getTable());
    }

    public function test_statut_par_defaut_est_en_attente(): void
    {
        $demande = DemandeAbsence::factory()->create();
        $this->assertEquals(DemandeAbsence::STATUT_EN_ATTENTE, $demande->statut);
    }

    // ============================================================
    // Casts
    // ============================================================

    public function test_dates_castees_en_carbon(): void
    {
        $demande = DemandeAbsence::factory()->create([
            'date_debut' => '2026-06-15',
            'date_fin'   => '2026-06-20',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $demande->date_debut);
        $this->assertInstanceOf(\Carbon\Carbon::class, $demande->date_fin);
        $this->assertEquals('2026-06-15', $demande->date_debut->format('Y-m-d'));
        $this->assertEquals('2026-06-20', $demande->date_fin->format('Y-m-d'));
    }

    // ============================================================
    // Relations
    // ============================================================

    public function test_demande_appartient_a_un_employe(): void
    {
        $profile = EmployeProfile::factory()->create();
        $demande = DemandeAbsence::factory()->for($profile, 'employe')->create();

        $this->assertInstanceOf(EmployeProfile::class, $demande->employe);
        $this->assertEquals($profile->id, $demande->employe->id);
    }

    public function test_demande_appartient_a_un_gestionnaire_quand_validee(): void
    {
        $profile      = EmployeProfile::factory()->create();
        $gestionnaire = Gestionnaire::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $demande = DemandeAbsence::factory()
            ->for($profile, 'employe')
            ->validee($gestionnaire)
            ->create();

        $this->assertInstanceOf(User::class, $demande->gestionnaire);
        $this->assertEquals($gestionnaire->id, $demande->gestionnaire->id);
    }

    public function test_gestionnaire_est_null_par_defaut(): void
    {
        $demande = DemandeAbsence::factory()->create();
        $this->assertNull($demande->gestionnaire);
        $this->assertNull($demande->gestionnaire_id);
    }

    // ============================================================
    // Factory states
    // ============================================================

    public function test_state_validee(): void
    {
        $demande = DemandeAbsence::factory()->validee()->create();

        $this->assertEquals(DemandeAbsence::STATUT_VALIDEE, $demande->statut);
        $this->assertNotNull($demande->gestionnaire_id);
        $this->assertNotNull($demande->commentaire_gestionnaire);
    }

    public function test_state_refusee(): void
    {
        $demande = DemandeAbsence::factory()->refusee()->create();

        $this->assertEquals(DemandeAbsence::STATUT_REFUSEE, $demande->statut);
        $this->assertNotNull($demande->gestionnaire_id);
    }

    public function test_state_en_attente(): void
    {
        $demande = DemandeAbsence::factory()->enAttente()->create();

        $this->assertEquals(DemandeAbsence::STATUT_EN_ATTENTE, $demande->statut);
        $this->assertNull($demande->gestionnaire_id);
        $this->assertNull($demande->commentaire_gestionnaire);
    }

    // ============================================================
    // Helpers d'état
    // ============================================================

    public function test_helper_est_en_attente(): void
    {
        $demande = DemandeAbsence::factory()->enAttente()->create();
        $this->assertTrue($demande->estEnAttente());
        $this->assertFalse($demande->estValidee());
        $this->assertFalse($demande->estRefusee());
    }

    public function test_helper_est_validee(): void
    {
        $demande = DemandeAbsence::factory()->validee()->create();
        $this->assertTrue($demande->estValidee());
        $this->assertFalse($demande->estEnAttente());
        $this->assertFalse($demande->estRefusee());
    }

    public function test_helper_est_refusee(): void
    {
        $demande = DemandeAbsence::factory()->refusee()->create();
        $this->assertTrue($demande->estRefusee());
        $this->assertFalse($demande->estEnAttente());
        $this->assertFalse($demande->estValidee());
    }

    // ============================================================
    // Contraintes BDD
    // ============================================================

    public function test_statut_invalide_rejete_par_la_db(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        DemandeAbsence::factory()->create(['statut' => 'inconnu']);
    }

    public function test_cascade_delete_supprime_les_demandes_quand_employe_supprime(): void
    {
        $profile = EmployeProfile::factory()->create();
        DemandeAbsence::factory()->count(3)->for($profile, 'employe')->create();

        $this->assertEquals(3, DemandeAbsence::count());

        $profile->delete();

        $this->assertEquals(0, DemandeAbsence::count());
    }

    public function test_gestionnaire_set_null_si_supprime(): void
    {
        $gestionnaire = Gestionnaire::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        $demande = DemandeAbsence::factory()
            ->validee($gestionnaire)
            ->create();

        $this->assertEquals($gestionnaire->id, $demande->fresh()->gestionnaire_id);

        $gestionnaire->delete();

        $this->assertNull($demande->fresh()->gestionnaire_id);
        // La demande elle-même existe toujours
        $this->assertNotNull($demande->fresh());
    }

    // ============================================================
    // Multiplicité
    // ============================================================

    public function test_un_employe_peut_avoir_plusieurs_demandes(): void
    {
        $profile = EmployeProfile::factory()->create();
        DemandeAbsence::factory()->count(5)->for($profile, 'employe')->create();

        $this->assertEquals(5, DemandeAbsence::where('employe_id', $profile->id)->count());
    }
}
