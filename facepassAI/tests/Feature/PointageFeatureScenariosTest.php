<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\EmployeProfile;
use App\Models\Gestionnaire;
use App\Models\Pointage;
use App\Services\FaceRecognitionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Tests feature complets du pointage biométrique — Sprint 4 carte 13 (US-032).
 *
 * Ce fichier groupe les scénarios métier exigés par la Trello sous des
 * noms qui matchent 1:1 la checklist :
 *   1. Pointage reconnu OK
 *   2. Visage non reconnu (2 cas : aucun visage / employé inconnu)
 *   3. 4 pointages/jour atteints
 *   4. Caméra en panne → fallback manuel par un gestionnaire
 *
 * + bonus : journée complète de l'employé (arrivée → pause → reprise → départ)
 *
 * Les autres fichiers de tests (PointageControllerTest, PointageManualTest)
 * couvrent les détails techniques et la validation ; ici on documente
 * les SCENARIOS UTILISATEUR.
 */
class PointageFeatureScenariosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    /**
     * Mock FaceRecognitionService pour un cas de RECONNAISSANCE RÉUSSIE.
     */
    protected function mockReconnaissanceOK(float $distance = 0.2): void
    {
        $this->mock(FaceRecognitionService::class, function (MockInterface $mock) use ($distance) {
            $mock->shouldReceive('encode')->andReturn(array_fill(0, 128, 0.1));
            $mock->shouldReceive('match')->andReturn([
                'match'      => true,
                'distance'   => $distance,
                'threshold'  => 0.6,
                'confidence' => 1.0 - $distance,
            ]);
        });
    }

    // ============================================================
    // SCÉNARIO 1 — Pointage reconnu OK
    // ============================================================

    public function test_scenario_1_pointage_reconnu_ok(): void
    {
        $profile = EmployeProfile::factory()->create([
            'encodage_facial' => array_fill(0, 128, 0.1),
        ]);
        $this->mockReconnaissanceOK();

        $response = $this->postJson('/pointages', [
            'photo' => UploadedFile::fake()->image('selfie.jpg'),
            'type'  => 'arrivee',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('employe.id', $profile->id)
            ->assertJsonPath('pointage.type', 'arrivee');

        $this->assertDatabaseHas('pointages', [
            'employe_id' => $profile->id,
            'type'       => 'arrivee',
            'manuel'     => false,
        ]);
    }

    // ============================================================
    // SCÉNARIO 2 — Visage non reconnu
    // ============================================================

    public function test_scenario_2a_aucun_visage_sur_la_photo(): void
    {
        $this->mock(FaceRecognitionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('encode')->once()->andReturn(null);
        });

        $response = $this->postJson('/pointages', [
            'photo' => UploadedFile::fake()->image('floue.jpg'),
            'type'  => 'arrivee',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
        $this->assertEquals(0, Pointage::count());
    }

    public function test_scenario_2b_visage_present_mais_employe_inconnu(): void
    {
        EmployeProfile::factory()->create(['encodage_facial' => array_fill(0, 128, 0.5)]);

        $this->mock(FaceRecognitionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('encode')->once()->andReturn(array_fill(0, 128, 0.99));
            $mock->shouldReceive('match')->andReturn([
                'match' => false, 'distance' => 1.0, 'threshold' => 0.6, 'confidence' => 0.0,
            ]);
        });

        $response = $this->postJson('/pointages', [
            'photo' => UploadedFile::fake()->image('intrus.jpg'),
            'type'  => 'arrivee',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
        $this->assertEquals(0, Pointage::count());
    }

    // ============================================================
    // SCÉNARIO 3 — 4 pointages/jour atteints
    // ============================================================

    public function test_scenario_3_quatre_pointages_jour_atteints(): void
    {
        $profile = EmployeProfile::factory()->create([
            'encodage_facial' => array_fill(0, 128, 0.1),
        ]);

        // Les 4 pointages d'aujourd'hui déjà faits
        Pointage::factory()->for($profile, 'employe')->arrivee()->create();
        Pointage::factory()->for($profile, 'employe')->debutPause()->create();
        Pointage::factory()->for($profile, 'employe')->finPause()->create();
        Pointage::factory()->for($profile, 'employe')->depart()->create();

        $this->mockReconnaissanceOK();

        // Tentative de 5ème pointage
        $response = $this->postJson('/pointages', [
            'photo' => UploadedFile::fake()->image('apres-tout.jpg'),
            'type'  => 'arrivee',
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
        $this->assertStringContainsString('4 pointages', $response->json('message'));

        // Toujours 4 pointages en BD, pas 5
        $this->assertEquals(4, Pointage::count());
    }

    // ============================================================
    // SCÉNARIO 4 — Caméra en panne (fallback manuel)
    // ============================================================

    public function test_scenario_4_camera_en_panne_fallback_manuel_par_gestionnaire(): void
    {
        $profile      = EmployeProfile::factory()->create();
        $gestionnaire = Gestionnaire::factory()->create();
        $gestionnaire->assignRole(Role::Gestionnaire->value);

        // Le gestionnaire enregistre un pointage manuel
        $response = $this->actingAs($gestionnaire)->post('/pointages/manuel', [
            'employe_id' => $profile->id,
            'type'       => 'arrivee',
            'motif'      => 'Caméra du hall hors service ce matin',
        ]);

        $response->assertRedirect(route('pointages.manual.create'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('pointages', [
            'employe_id'   => $profile->id,
            'type'         => 'arrivee',
            'manuel'       => true,
            'motif_manuel' => 'Caméra du hall hors service ce matin',
        ]);
    }

    public function test_scenario_4bis_fallback_manuel_inaccessible_aux_non_gestionnaires(): void
    {
        // Un employé lambda ne peut PAS contourner la caméra en panne
        $employeProfile = EmployeProfile::factory()->create();
        $employe        = $employeProfile->user;

        $this->actingAs($employe)
            ->get('/pointages/manuel')
            ->assertStatus(403);
    }

    // ============================================================
    // BONUS — Journée complète end-to-end
    // ============================================================

    public function test_journee_complete_arrivee_pause_reprise_depart(): void
    {
        $profile = EmployeProfile::factory()->create([
            'encodage_facial' => array_fill(0, 128, 0.1),
        ]);
        $this->mockReconnaissanceOK();

        // 1. ARRIVÉE — 8h30
        $r1 = $this->postJson('/pointages', [
            'photo' => UploadedFile::fake()->image('matin.jpg'),
            'type'  => 'arrivee',
        ]);
        $r1->assertStatus(201)->assertJsonPath('pointage.type', 'arrivee');

        // 2. DÉBUT DE PAUSE — 12h00
        $r2 = $this->postJson('/pointages', [
            'photo' => UploadedFile::fake()->image('avant-dejeuner.jpg'),
            'type'  => 'debut_pause',
        ]);
        $r2->assertStatus(201)->assertJsonPath('pointage.type', 'debut_pause');

        // 3. FIN DE PAUSE — 13h00
        $r3 = $this->postJson('/pointages', [
            'photo' => UploadedFile::fake()->image('retour-dejeuner.jpg'),
            'type'  => 'fin_pause',
        ]);
        $r3->assertStatus(201)->assertJsonPath('pointage.type', 'fin_pause');

        // 4. DÉPART — 17h30
        $r4 = $this->postJson('/pointages', [
            'photo' => UploadedFile::fake()->image('soir.jpg'),
            'type'  => 'depart',
        ]);
        $r4->assertStatus(201)->assertJsonPath('pointage.type', 'depart');

        // Vérifs finales
        $this->assertEquals(4, Pointage::where('employe_id', $profile->id)->count());

        // 5ème tentative bloquée par la règle 4/jour
        $r5 = $this->postJson('/pointages', [
            'photo' => UploadedFile::fake()->image('apres.jpg'),
            'type'  => 'arrivee',
        ]);
        $r5->assertStatus(422);
        $this->assertEquals(4, Pointage::count());
    }
}
