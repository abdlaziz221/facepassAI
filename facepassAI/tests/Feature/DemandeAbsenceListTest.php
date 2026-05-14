<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\DemandeAbsence;
use App\Models\Employe;
use App\Models\EmployeProfile;
use App\Models\Gestionnaire;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint 4 Horaires carte 10 (US-052)
 * Liste des demandes en attente + actions Voir / Valider / Refuser.
 */
class DemandeAbsenceListTest extends TestCase
{
    use RefreshDatabase;

    protected Gestionnaire $gestionnaire;
    protected Employe $employe;
    protected EmployeProfile $profile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->gestionnaire = Gestionnaire::factory()->create();
        $this->gestionnaire->assignRole(Role::Gestionnaire->value);

        $this->employe = Employe::factory()->create();
        $this->employe->assignRole(Role::Employe->value);
        $this->profile = EmployeProfile::factory()->create(['user_id' => $this->employe->id]);
    }

    protected function makeDemande(array $attrs = []): DemandeAbsence
    {
        return DemandeAbsence::factory()->for($this->profile, 'employe')->create(array_merge([
            'date_debut' => '2026-06-10',
            'date_fin'   => '2026-06-15',
            'motif'      => 'Congés annuels',
            'statut'     => DemandeAbsence::STATUT_EN_ATTENTE,
        ], $attrs));
    }

    // ========================================================================
    // INDEX — accès et filtres
    // ========================================================================

    public function test_index_accessible_au_gestionnaire(): void
    {
        $this->makeDemande();

        $this->actingAs($this->gestionnaire)
            ->get(route('demandes-absence.index'))
            ->assertOk()
            ->assertViewIs('demandes-absence.index')
            ->assertSee('Demandes d\'absence en attente', false);
    }

    public function test_index_refuse_a_l_employe(): void
    {
        $this->actingAs($this->employe)
            ->get(route('demandes-absence.index'))
            ->assertForbidden();
    }

    public function test_index_redirige_si_non_connecte(): void
    {
        $this->get(route('demandes-absence.index'))
            ->assertRedirect(route('login'));
    }

    public function test_index_ne_liste_que_les_demandes_en_attente(): void
    {
        $this->makeDemande(['statut' => DemandeAbsence::STATUT_EN_ATTENTE, 'motif' => 'AAAAA en attente']);
        $this->makeDemande(['statut' => DemandeAbsence::STATUT_VALIDEE,    'motif' => 'BBBBB validee']);
        $this->makeDemande(['statut' => DemandeAbsence::STATUT_REFUSEE,    'motif' => 'CCCCC refusee']);

        $response = $this->actingAs($this->gestionnaire)
            ->get(route('demandes-absence.index'));

        $demandes = $response->viewData('demandes');
        $this->assertEquals(1, $demandes->count());
        $this->assertEquals('AAAAA en attente', $demandes->first()->motif);
    }

    public function test_index_filtre_par_employe(): void
    {
        $this->makeDemande();

        $autreProfile = EmployeProfile::factory()->create();
        DemandeAbsence::factory()->for($autreProfile, 'employe')->create([
            'date_debut' => '2026-07-01',
            'date_fin'   => '2026-07-05',
            'statut'     => DemandeAbsence::STATUT_EN_ATTENTE,
        ]);

        $response = $this->actingAs($this->gestionnaire)
            ->get(route('demandes-absence.index', ['employe_id' => $this->profile->id]));

        $demandes = $response->viewData('demandes');
        $this->assertEquals(1, $demandes->count());
        $this->assertEquals($this->profile->id, $demandes->first()->employe_id);
    }

    public function test_index_filtre_par_date_couverte(): void
    {
        // Demande qui couvre le 12 juin
        $this->makeDemande(['date_debut' => '2026-06-10', 'date_fin' => '2026-06-15']);
        // Demande qui NE couvre PAS le 12 juin
        $this->makeDemande(['date_debut' => '2026-08-01', 'date_fin' => '2026-08-05']);

        $response = $this->actingAs($this->gestionnaire)
            ->get(route('demandes-absence.index', ['date' => '2026-06-12']));

        $demandes = $response->viewData('demandes');
        $this->assertEquals(1, $demandes->count());
    }

    public function test_index_pagine_par_15(): void
    {
        DemandeAbsence::factory()->for($this->profile, 'employe')->count(20)->create([
            'statut' => DemandeAbsence::STATUT_EN_ATTENTE,
        ]);

        $response = $this->actingAs($this->gestionnaire)
            ->get(route('demandes-absence.index'));

        $demandes = $response->viewData('demandes');
        $this->assertEquals(15, $demandes->count());
        $this->assertEquals(20, $demandes->total());
    }

    // ========================================================================
    // SHOW
    // ========================================================================

    public function test_show_affiche_le_detail(): void
    {
        $demande = $this->makeDemande(['motif' => 'Mariage de mon frère']);

        $this->actingAs($this->gestionnaire)
            ->get(route('demandes-absence.show', $demande))
            ->assertOk()
            ->assertSee('Mariage de mon frère')
            ->assertSee($this->employe->name);
    }

    public function test_show_refuse_a_l_employe(): void
    {
        $demande = $this->makeDemande();

        $this->actingAs($this->employe)
            ->get(route('demandes-absence.show', $demande))
            ->assertForbidden();
    }

    // ========================================================================
    // VALIDER
    // ========================================================================

    public function test_valider_change_le_statut_et_enregistre_le_gestionnaire(): void
    {
        $demande = $this->makeDemande();

        $this->actingAs($this->gestionnaire)
            ->post(route('demandes-absence.valider', $demande), ['commentaire' => 'OK pour moi'])
            ->assertRedirect(route('demandes-absence.index'))
            ->assertSessionHas('success');

        $demande->refresh();
        $this->assertEquals(DemandeAbsence::STATUT_VALIDEE, $demande->statut);
        $this->assertEquals($this->gestionnaire->id, $demande->gestionnaire_id);
        $this->assertEquals('OK pour moi', $demande->commentaire_gestionnaire);
    }

    public function test_valider_accepte_un_commentaire_vide(): void
    {
        $demande = $this->makeDemande();

        $this->actingAs($this->gestionnaire)
            ->post(route('demandes-absence.valider', $demande), [])
            ->assertRedirect(route('demandes-absence.index'));

        $demande->refresh();
        $this->assertEquals(DemandeAbsence::STATUT_VALIDEE, $demande->statut);
        $this->assertNull($demande->commentaire_gestionnaire);
    }

    public function test_valider_refuse_une_demande_deja_traitee(): void
    {
        $demande = $this->makeDemande(['statut' => DemandeAbsence::STATUT_REFUSEE]);

        $this->actingAs($this->gestionnaire)
            ->post(route('demandes-absence.valider', $demande))
            ->assertRedirect(route('demandes-absence.index'))
            ->assertSessionHasErrors('statut');

        $demande->refresh();
        $this->assertEquals(DemandeAbsence::STATUT_REFUSEE, $demande->statut);
    }

    public function test_valider_interdit_a_l_employe(): void
    {
        $demande = $this->makeDemande();

        $this->actingAs($this->employe)
            ->post(route('demandes-absence.valider', $demande))
            ->assertForbidden();

        $demande->refresh();
        $this->assertEquals(DemandeAbsence::STATUT_EN_ATTENTE, $demande->statut);
    }

    // ========================================================================
    // REFUSER
    // ========================================================================

    public function test_refuser_change_le_statut_avec_justification(): void
    {
        $demande = $this->makeDemande();

        $this->actingAs($this->gestionnaire)
            ->post(route('demandes-absence.refuser', $demande), [
                'commentaire' => 'Période non disponible, équipe en sous-effectif',
            ])
            ->assertRedirect(route('demandes-absence.index'))
            ->assertSessionHas('success');

        $demande->refresh();
        $this->assertEquals(DemandeAbsence::STATUT_REFUSEE, $demande->statut);
        $this->assertEquals($this->gestionnaire->id, $demande->gestionnaire_id);
        $this->assertStringContainsString('sous-effectif', $demande->commentaire_gestionnaire);
    }

    public function test_refuser_exige_une_justification(): void
    {
        $demande = $this->makeDemande();

        $this->actingAs($this->gestionnaire)
            ->post(route('demandes-absence.refuser', $demande), ['commentaire' => ''])
            ->assertSessionHasErrors('commentaire');

        $demande->refresh();
        $this->assertEquals(DemandeAbsence::STATUT_EN_ATTENTE, $demande->statut);
    }

    public function test_refuser_exige_justification_min_5_caracteres(): void
    {
        $demande = $this->makeDemande();

        $this->actingAs($this->gestionnaire)
            ->post(route('demandes-absence.refuser', $demande), ['commentaire' => 'ok'])
            ->assertSessionHasErrors('commentaire');

        $demande->refresh();
        $this->assertEquals(DemandeAbsence::STATUT_EN_ATTENTE, $demande->statut);
    }

    public function test_refuser_refuse_une_demande_deja_traitee(): void
    {
        $demande = $this->makeDemande(['statut' => DemandeAbsence::STATUT_VALIDEE]);

        $this->actingAs($this->gestionnaire)
            ->post(route('demandes-absence.refuser', $demande), [
                'commentaire' => 'Trop tard, déjà traitée',
            ])
            ->assertRedirect(route('demandes-absence.index'))
            ->assertSessionHasErrors('statut');

        $demande->refresh();
        $this->assertEquals(DemandeAbsence::STATUT_VALIDEE, $demande->statut);
    }

    public function test_refuser_interdit_a_l_employe(): void
    {
        $demande = $this->makeDemande();

        $this->actingAs($this->employe)
            ->post(route('demandes-absence.refuser', $demande), [
                'commentaire' => 'Je refuse ma propre demande !',
            ])
            ->assertForbidden();

        $demande->refresh();
        $this->assertEquals(DemandeAbsence::STATUT_EN_ATTENTE, $demande->statut);
    }
}
