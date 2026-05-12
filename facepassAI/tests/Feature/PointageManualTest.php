<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Administrateur;
use App\Models\Consultant;
use App\Models\Employe;
use App\Models\EmployeProfile;
use App\Models\Gestionnaire;
use App\Models\Pointage;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Tests du pointage manuel (Sprint 4 US-036).
 *
 * Mode dégradé : un gestionnaire/admin peut enregistrer un pointage
 * pour un employé en cas de panne caméra, avec motif obligatoire.
 */
class PointageManualTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function asGestionnaire(): self
    {
        $user = Gestionnaire::factory()->create();
        $user->assignRole(Role::Gestionnaire->value);
        return $this->actingAs($user);
    }

    protected function asAdmin(): self
    {
        $user = Administrateur::factory()->create();
        $user->assignRole(Role::Administrateur->value);
        return $this->actingAs($user);
    }

    protected function asEmploye(): self
    {
        $user = Employe::factory()->create();
        $user->assignRole(Role::Employe->value);
        return $this->actingAs($user);
    }

    protected function asConsultant(): self
    {
        $user = Consultant::factory()->create();
        $user->assignRole(Role::Consultant->value);
        return $this->actingAs($user);
    }

    // ============================================================
    // GET /pointages/manuel — Autorisations
    // ============================================================

    public function test_gestionnaire_peut_voir_le_formulaire(): void
    {
        $this->asGestionnaire()
            ->get('/pointages/manuel')
            ->assertStatus(200)
            ->assertSee('Pointage manuel');
    }

    public function test_administrateur_peut_voir_le_formulaire(): void
    {
        $this->asAdmin()
            ->get('/pointages/manuel')
            ->assertStatus(200);
    }

    public function test_employe_ne_peut_pas_voir_le_formulaire(): void
    {
        $this->asEmploye()
            ->get('/pointages/manuel')
            ->assertStatus(403);
    }

    public function test_consultant_ne_peut_pas_voir_le_formulaire(): void
    {
        $this->asConsultant()
            ->get('/pointages/manuel')
            ->assertStatus(403);
    }

    public function test_guest_est_redirige_vers_login(): void
    {
        $this->get('/pointages/manuel')
            ->assertRedirect('/login');
    }

    // ============================================================
    // GET /pointages/manuel — Contenu
    // ============================================================

    public function test_le_formulaire_liste_tous_les_employes(): void
    {
        $p1 = EmployeProfile::factory()->create(['matricule' => 'EMP-001']);
        $p2 = EmployeProfile::factory()->create(['matricule' => 'EMP-002']);

        $this->asGestionnaire()
            ->get('/pointages/manuel')
            ->assertSee('EMP-001')
            ->assertSee('EMP-002');
    }

    public function test_le_formulaire_contient_les_4_types(): void
    {
        $this->asGestionnaire()
            ->get('/pointages/manuel')
            ->assertSee('Arrivée')
            ->assertSee('Début de pause')
            ->assertSee('Fin de pause')
            ->assertSee('Départ');
    }

    // ============================================================
    // POST /pointages/manuel — Validation
    // ============================================================

    public function test_employe_id_obligatoire(): void
    {
        $this->asGestionnaire()
            ->post('/pointages/manuel', [
                'type'  => 'arrivee',
                'motif' => 'Caméra HS',
            ])
            ->assertSessionHasErrors('employe_id');
    }

    public function test_employe_id_doit_exister(): void
    {
        $this->asGestionnaire()
            ->post('/pointages/manuel', [
                'employe_id' => 9999,
                'type'       => 'arrivee',
                'motif'      => 'Caméra HS',
            ])
            ->assertSessionHasErrors('employe_id');
    }

    public function test_type_obligatoire(): void
    {
        $profile = EmployeProfile::factory()->create();

        $this->asGestionnaire()
            ->post('/pointages/manuel', [
                'employe_id' => $profile->id,
                'motif'      => 'Caméra HS',
            ])
            ->assertSessionHasErrors('type');
    }

    public function test_type_doit_etre_valide(): void
    {
        $profile = EmployeProfile::factory()->create();

        $this->asGestionnaire()
            ->post('/pointages/manuel', [
                'employe_id' => $profile->id,
                'type'       => 'absurde',
                'motif'      => 'Caméra HS',
            ])
            ->assertSessionHasErrors('type');
    }

    public function test_motif_obligatoire(): void
    {
        $profile = EmployeProfile::factory()->create();

        $this->asGestionnaire()
            ->post('/pointages/manuel', [
                'employe_id' => $profile->id,
                'type'       => 'arrivee',
            ])
            ->assertSessionHasErrors('motif');
    }

    public function test_motif_doit_faire_au_moins_5_caracteres(): void
    {
        $profile = EmployeProfile::factory()->create();

        $this->asGestionnaire()
            ->post('/pointages/manuel', [
                'employe_id' => $profile->id,
                'type'       => 'arrivee',
                'motif'      => 'NO',
            ])
            ->assertSessionHasErrors('motif');
    }

    // ============================================================
    // POST /pointages/manuel — Création
    // ============================================================

    public function test_cree_un_pointage_avec_flag_manuel_true(): void
    {
        $profile = EmployeProfile::factory()->create();

        $this->asGestionnaire()
            ->post('/pointages/manuel', [
                'employe_id' => $profile->id,
                'type'       => 'arrivee',
                'motif'      => 'Caméra HS au hall principal',
            ])
            ->assertRedirect(route('pointages.manual.create'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pointages', [
            'employe_id'   => $profile->id,
            'type'         => 'arrivee',
            'manuel'       => true,
            'motif_manuel' => 'Caméra HS au hall principal',
        ]);
    }

    public function test_admin_peut_aussi_creer_un_pointage_manuel(): void
    {
        $profile = EmployeProfile::factory()->create();

        $this->asAdmin()
            ->post('/pointages/manuel', [
                'employe_id' => $profile->id,
                'type'       => 'depart',
                'motif'      => 'Pointage rétroactif sur demande',
            ])
            ->assertRedirect(route('pointages.manual.create'));

        $this->assertDatabaseHas('pointages', [
            'employe_id'   => $profile->id,
            'type'         => 'depart',
            'manuel'       => true,
        ]);
    }

    public function test_employe_ne_peut_pas_creer_de_pointage_manuel(): void
    {
        $profile = EmployeProfile::factory()->create();

        $this->asEmploye()
            ->post('/pointages/manuel', [
                'employe_id' => $profile->id,
                'type'       => 'arrivee',
                'motif'      => 'Tentative de fraude',
            ])
            ->assertStatus(403);

        $this->assertEquals(0, Pointage::count());
    }

    public function test_respecte_la_limite_4_par_jour(): void
    {
        $profile = EmployeProfile::factory()->create();

        Pointage::factory()->for($profile, 'employe')->arrivee()->create();
        Pointage::factory()->for($profile, 'employe')->debutPause()->create();
        Pointage::factory()->for($profile, 'employe')->finPause()->create();
        Pointage::factory()->for($profile, 'employe')->depart()->create();

        $this->asGestionnaire()
            ->post('/pointages/manuel', [
                'employe_id' => $profile->id,
                'type'       => 'arrivee',
                'motif'      => 'Tentative de 5ème pointage',
            ])
            ->assertSessionHasErrors('employe_id');

        $this->assertEquals(4, Pointage::count()); // pas de 5ème
    }

    public function test_log_info_emis_lors_de_la_creation(): void
    {
        Log::spy();
        $profile = EmployeProfile::factory()->create();

        $this->asGestionnaire()
            ->post('/pointages/manuel', [
                'employe_id' => $profile->id,
                'type'       => 'arrivee',
                'motif'      => 'Caméra HS test',
            ]);

        Log::shouldHaveReceived('info')->once();
    }
}
