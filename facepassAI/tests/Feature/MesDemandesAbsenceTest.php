<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\DemandeAbsence;
use App\Models\Employe;
use App\Models\EmployeProfile;
use App\Models\Gestionnaire;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sprint 4 Horaires carte 12 (US-054)
 * Historique des demandes côté employé : vue scopée à l'utilisateur,
 * tri par date décroissante, badges de statut coloriés.
 */
class MesDemandesAbsenceTest extends TestCase
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

    protected function makeDemande(array $attrs = [], ?EmployeProfile $profile = null): DemandeAbsence
    {
        return DemandeAbsence::factory()->for($profile ?? $this->profile, 'employe')->create(array_merge([
            'date_debut' => '2026-06-10',
            'date_fin'   => '2026-06-15',
            'motif'      => 'Congés',
            'statut'     => DemandeAbsence::STATUT_EN_ATTENTE,
        ], $attrs));
    }

    // ========================================================================
    // Accès
    // ========================================================================

    public function test_page_accessible_a_l_employe(): void
    {
        $this->actingAs($this->employe)
            ->get(route('mes-demandes-absence.index'))
            ->assertOk()
            ->assertViewIs('demandes-absence.mes-demandes')
            ->assertSee('Historique de mes demandes', false);
    }

    public function test_page_redirige_si_non_connecte(): void
    {
        $this->get(route('mes-demandes-absence.index'))
            ->assertRedirect(route('login'));
    }

    public function test_page_inaccessible_au_user_sans_permission(): void
    {
        // Un user sans le rôle Employe (ni autre rôle avec view-own)
        $user = \App\Models\User::factory()->create();

        $this->actingAs($user)
            ->get(route('mes-demandes-absence.index'))
            ->assertForbidden();
    }

    // ========================================================================
    // Scoping : chacun voit SES demandes, pas celles des autres
    // ========================================================================

    public function test_employe_voit_uniquement_ses_propres_demandes(): void
    {
        // Mes demandes
        $this->makeDemande(['motif' => 'MA DEMANDE 1']);
        $this->makeDemande(['motif' => 'MA DEMANDE 2']);

        // Demande d'un autre employé
        $autreProfile = EmployeProfile::factory()->create();
        $this->makeDemande(['motif' => "PAS A MOI"], $autreProfile);

        $response = $this->actingAs($this->employe)
            ->get(route('mes-demandes-absence.index'));

        $demandes = $response->viewData('demandes');
        $this->assertEquals(2, $demandes->count());
        $this->assertEquals(2, $demandes->total());

        foreach ($demandes as $d) {
            $this->assertEquals($this->profile->id, $d->employe_id);
        }

        $response->assertSee('MA DEMANDE 1');
        $response->assertSee('MA DEMANDE 2');
        $response->assertDontSee('PAS A MOI');
    }

    public function test_employe_sans_profile_ne_voit_aucune_demande(): void
    {
        // Un employé qui n'a pas (encore) de profil métier
        $employeSansProfile = Employe::factory()->create();
        $employeSansProfile->assignRole(Role::Employe->value);

        // Quelques demandes en base, mais pas les siennes
        $this->makeDemande();

        $response = $this->actingAs($employeSansProfile)
            ->get(route('mes-demandes-absence.index'));

        $response->assertOk();
        $demandes = $response->viewData('demandes');
        $this->assertEquals(0, $demandes->total());
    }

    // ========================================================================
    // Tri par date décroissante
    // ========================================================================

    public function test_demandes_triees_par_date_decroissante(): void
    {
        $ancienne = $this->makeDemande(['date_debut' => '2026-01-10', 'date_fin' => '2026-01-15']);
        $recente  = $this->makeDemande(['date_debut' => '2026-08-10', 'date_fin' => '2026-08-15']);
        $milieu   = $this->makeDemande(['date_debut' => '2026-04-10', 'date_fin' => '2026-04-15']);

        $response = $this->actingAs($this->employe)
            ->get(route('mes-demandes-absence.index'));

        $demandes = $response->viewData('demandes');
        $ids = $demandes->pluck('id')->all();

        $this->assertEquals([$recente->id, $milieu->id, $ancienne->id], $ids);
    }

    // ========================================================================
    // Compteurs par statut
    // ========================================================================

    public function test_compteurs_par_statut_corrects(): void
    {
        $this->makeDemande(['statut' => DemandeAbsence::STATUT_EN_ATTENTE]);
        $this->makeDemande(['statut' => DemandeAbsence::STATUT_EN_ATTENTE]);
        $this->makeDemande(['statut' => DemandeAbsence::STATUT_VALIDEE]);
        $this->makeDemande(['statut' => DemandeAbsence::STATUT_REFUSEE]);
        $this->makeDemande(['statut' => DemandeAbsence::STATUT_REFUSEE]);
        $this->makeDemande(['statut' => DemandeAbsence::STATUT_REFUSEE]);

        $response = $this->actingAs($this->employe)
            ->get(route('mes-demandes-absence.index'));

        $counts = $response->viewData('counts');
        $this->assertEquals(6, $counts['total']);
        $this->assertEquals(2, $counts['en_attente']);
        $this->assertEquals(1, $counts['validee']);
        $this->assertEquals(3, $counts['refusee']);
    }

    public function test_compteurs_n_incluent_pas_les_demandes_des_autres(): void
    {
        $this->makeDemande(['statut' => DemandeAbsence::STATUT_VALIDEE]);

        $autreProfile = EmployeProfile::factory()->create();
        $this->makeDemande(['statut' => DemandeAbsence::STATUT_VALIDEE], $autreProfile);
        $this->makeDemande(['statut' => DemandeAbsence::STATUT_REFUSEE], $autreProfile);

        $response = $this->actingAs($this->employe)
            ->get(route('mes-demandes-absence.index'));

        $counts = $response->viewData('counts');
        $this->assertEquals(1, $counts['total']);
        $this->assertEquals(1, $counts['validee']);
        $this->assertEquals(0, $counts['refusee']);
    }

    // ========================================================================
    // Badges colorés (rendu HTML)
    // ========================================================================

    public function test_badge_en_attente_affiche_correctement(): void
    {
        $this->makeDemande(['statut' => DemandeAbsence::STATUT_EN_ATTENTE]);

        $this->actingAs($this->employe)
            ->get(route('mes-demandes-absence.index'))
            ->assertSee('En attente');
    }

    public function test_badge_validee_affiche_correctement(): void
    {
        $this->makeDemande(['statut' => DemandeAbsence::STATUT_VALIDEE]);

        $this->actingAs($this->employe)
            ->get(route('mes-demandes-absence.index'))
            ->assertSee('Validée');
    }

    public function test_badge_refusee_affiche_correctement(): void
    {
        $this->makeDemande(['statut' => DemandeAbsence::STATUT_REFUSEE]);

        $this->actingAs($this->employe)
            ->get(route('mes-demandes-absence.index'))
            ->assertSee('Refusée');
    }

    // ========================================================================
    // Commentaire / justification du gestionnaire
    // ========================================================================

    public function test_commentaire_gestionnaire_visible_si_present(): void
    {
        $this->makeDemande([
            'statut'                   => DemandeAbsence::STATUT_VALIDEE,
            'commentaire_gestionnaire' => 'Bon repos !',
        ]);

        $this->actingAs($this->employe)
            ->get(route('mes-demandes-absence.index'))
            ->assertSee('Bon repos !');
    }

    public function test_justification_refus_visible(): void
    {
        $this->makeDemande([
            'statut'                   => DemandeAbsence::STATUT_REFUSEE,
            'commentaire_gestionnaire' => 'Période non disponible cette semaine',
        ]);

        $this->actingAs($this->employe)
            ->get(route('mes-demandes-absence.index'))
            ->assertSee('Période non disponible cette semaine');
    }

    // ========================================================================
    // Pagination
    // ========================================================================

    public function test_pagine_par_15(): void
    {
        DemandeAbsence::factory()->for($this->profile, 'employe')->count(20)->create();

        $response = $this->actingAs($this->employe)
            ->get(route('mes-demandes-absence.index'));

        $demandes = $response->viewData('demandes');
        $this->assertEquals(15, $demandes->count());
        $this->assertEquals(20, $demandes->total());
    }

    // ========================================================================
    // Vue vide
    // ========================================================================

    public function test_vue_vide_si_aucune_demande(): void
    {
        $this->actingAs($this->employe)
            ->get(route('mes-demandes-absence.index'))
            ->assertOk()
            ->assertSee('aucune demande', false);
    }
}
