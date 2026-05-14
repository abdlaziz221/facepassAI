<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Administrateur;
use App\Models\Consultant;
use App\Models\DemandeAbsence;
use App\Models\Employe;
use App\Models\EmployeProfile;
use App\Models\Gestionnaire;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests du formulaire de demande d'absence côté employé
 * (Sprint 4 Horaires carte 7, US-050).
 */
class StoreDemandeAbsenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Crée un employé avec son profil et le retourne pour acting.
     */
    protected function makeEmployeWithProfile(): Employe
    {
        $employe = Employe::factory()->create();
        $employe->assignRole(Role::Employe->value);
        EmployeProfile::factory()->create(['user_id' => $employe->id]);
        return $employe;
    }

    /** Helper : payload valide. */
    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'date_debut' => now()->addDays(5)->format('Y-m-d'),
            'date_fin'   => now()->addDays(10)->format('Y-m-d'),
            'motif'      => 'Congé annuel pour vacances familiales',
        ], $overrides);
    }

    // ============================================================
    // Autorisations
    // ============================================================

    public function test_employe_peut_voir_le_formulaire(): void
    {
        $this->actingAs($this->makeEmployeWithProfile())
            ->get('/demandes-absence/create')
            ->assertStatus(200)
            ->assertSee('Nouvelle demande d\'absence', false);
    }

    public function test_gestionnaire_ne_peut_pas_acceder(): void
    {
        $user = Gestionnaire::factory()->create();
        $user->assignRole(Role::Gestionnaire->value);

        $this->actingAs($user)
            ->get('/demandes-absence/create')
            ->assertStatus(403);
    }

    public function test_administrateur_ne_peut_pas_acceder(): void
    {
        $user = Administrateur::factory()->create();
        $user->assignRole(Role::Administrateur->value);

        $this->actingAs($user)
            ->get('/demandes-absence/create')
            ->assertStatus(403);
    }

    public function test_consultant_ne_peut_pas_acceder(): void
    {
        $user = Consultant::factory()->create();
        $user->assignRole(Role::Consultant->value);

        $this->actingAs($user)
            ->get('/demandes-absence/create')
            ->assertStatus(403);
    }

    public function test_guest_redirige_vers_login(): void
    {
        $this->get('/demandes-absence/create')->assertRedirect('/login');
    }

    // ============================================================
    // Contenu du formulaire
    // ============================================================

    public function test_le_formulaire_contient_les_champs_attendus(): void
    {
        $this->actingAs($this->makeEmployeWithProfile())
            ->get('/demandes-absence/create')
            ->assertSee('Date de début')
            ->assertSee('Date de fin')
            ->assertSee('Motif')
            ->assertSee('Envoyer la demande');
    }

    // ============================================================
    // Validation
    // ============================================================

    public function test_date_debut_obligatoire(): void
    {
        $employe = $this->makeEmployeWithProfile();

        $this->actingAs($employe)
            ->post('/demandes-absence', $this->validPayload(['date_debut' => null]))
            ->assertSessionHasErrors('date_debut');
    }

    public function test_date_debut_ne_peut_pas_etre_dans_le_passe(): void
    {
        $employe = $this->makeEmployeWithProfile();

        $this->actingAs($employe)
            ->post('/demandes-absence', $this->validPayload([
                'date_debut' => now()->subDays(2)->format('Y-m-d'),
            ]))
            ->assertSessionHasErrors('date_debut');
    }

    public function test_date_fin_doit_etre_apres_ou_egal_date_debut(): void
    {
        $employe = $this->makeEmployeWithProfile();

        $this->actingAs($employe)
            ->post('/demandes-absence', $this->validPayload([
                'date_debut' => now()->addDays(10)->format('Y-m-d'),
                'date_fin'   => now()->addDays(5)->format('Y-m-d'),
            ]))
            ->assertSessionHasErrors('date_fin');
    }

    public function test_date_fin_egale_a_date_debut_est_acceptee(): void
    {
        $employe = $this->makeEmployeWithProfile();
        $date    = now()->addDays(5)->format('Y-m-d');

        $this->actingAs($employe)
            ->post('/demandes-absence', $this->validPayload([
                'date_debut' => $date,
                'date_fin'   => $date,
            ]))
            ->assertSessionHasNoErrors();
    }

    public function test_motif_obligatoire(): void
    {
        $employe = $this->makeEmployeWithProfile();

        $this->actingAs($employe)
            ->post('/demandes-absence', $this->validPayload(['motif' => null]))
            ->assertSessionHasErrors('motif');
    }

    public function test_motif_doit_faire_au_moins_5_caracteres(): void
    {
        $employe = $this->makeEmployeWithProfile();

        $this->actingAs($employe)
            ->post('/demandes-absence', $this->validPayload(['motif' => 'OK']))
            ->assertSessionHasErrors('motif');
    }

    public function test_motif_max_500_caracteres(): void
    {
        $employe = $this->makeEmployeWithProfile();

        $this->actingAs($employe)
            ->post('/demandes-absence', $this->validPayload([
                'motif' => str_repeat('a', 501),
            ]))
            ->assertSessionHasErrors('motif');
    }

    // ============================================================
    // Création réussie
    // ============================================================

    public function test_demande_est_creee_avec_employe_id_de_l_utilisateur_connecte(): void
    {
        $employe = $this->makeEmployeWithProfile();
        $profile = EmployeProfile::where('user_id', $employe->id)->first();

        $this->actingAs($employe)
            ->post('/demandes-absence', $this->validPayload([
                'motif' => 'Congé de mariage de mon frère',
            ]))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('demandes_absence', [
            'employe_id' => $profile->id,
            'motif'      => 'Congé de mariage de mon frère',
            'statut'     => 'en_attente',
        ]);
    }

    public function test_statut_initial_est_en_attente(): void
    {
        $employe = $this->makeEmployeWithProfile();

        $this->actingAs($employe)
            ->post('/demandes-absence', $this->validPayload());

        $demande = DemandeAbsence::first();
        $this->assertEquals(DemandeAbsence::STATUT_EN_ATTENTE, $demande->statut);
        $this->assertNull($demande->gestionnaire_id);
        $this->assertNull($demande->commentaire_gestionnaire);
    }

    public function test_message_flash_apres_creation_reussie(): void
    {
        $employe = $this->makeEmployeWithProfile();

        $response = $this->actingAs($employe)
            ->post('/demandes-absence', $this->validPayload());

        $this->assertNotNull(session('success'));
        $this->assertStringContainsString('demande', session('success'));
        $this->assertStringContainsString('attente', session('success'));
    }

    public function test_redirige_vers_dashboard_apres_succes(): void
    {
        $employe = $this->makeEmployeWithProfile();

        $this->actingAs($employe)
            ->post('/demandes-absence', $this->validPayload())
            ->assertRedirect(route('dashboard'));
    }

    public function test_employe_sans_profil_recoit_une_erreur(): void
    {
        // Employé sans profil métier créé
        $employe = Employe::factory()->create();
        $employe->assignRole(Role::Employe->value);

        $this->actingAs($employe)
            ->post('/demandes-absence', $this->validPayload())
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('profile');

        $this->assertEquals(0, DemandeAbsence::count());
    }
}
