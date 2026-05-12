<?php

namespace Tests\Feature;

use App\Models\EmployeProfile;
use App\Models\Pointage;
use App\Services\FaceRecognitionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Tests du PointageController (US-032).
 *
 * On mocke FaceRecognitionService pour ne pas dépendre du microservice
 * Python en train de tourner. Tous les chemins sont couverts :
 * page kiosque, validation, encode null, aucun match, match unique,
 * meilleur match parmi plusieurs candidats.
 */
class PointageControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    // ============================================================
    // Page kiosque GET /pointer
    // ============================================================

    public function test_la_page_kiosque_est_accessible_publiquement(): void
    {
        $response = $this->get('/pointer');

        $response->assertStatus(200);
        $response->assertSee('FacePass');
        $response->assertSee('Pointage biométrique');
    }

    public function test_la_page_kiosque_contient_les_4_boutons_de_type(): void
    {
        $response = $this->get('/pointer');

        $response->assertSee('Arrivée');
        $response->assertSee('Début pause');
        $response->assertSee('Fin pause');
        $response->assertSee('Départ');
    }

    // ============================================================
    // Validation POST /pointages
    // ============================================================

    public function test_photo_obligatoire(): void
    {
        $response = $this->postJson('/pointages', [
            'type' => 'arrivee',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('photo');
    }

    public function test_photo_doit_etre_une_image(): void
    {
        $response = $this->postJson('/pointages', [
            'photo' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            'type'  => 'arrivee',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('photo');
    }

    public function test_type_obligatoire(): void
    {
        $response = $this->postJson('/pointages', [
            'photo' => UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('type');
    }

    public function test_type_invalide_rejete(): void
    {
        $response = $this->postJson('/pointages', [
            'photo' => UploadedFile::fake()->image('selfie.jpg'),
            'type'  => 'absurde',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('type');
    }

    // ============================================================
    // Flow nominal et erreurs
    // ============================================================

    public function test_retourne_422_si_aucun_visage_detecte(): void
    {
        $this->mock(FaceRecognitionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('encode')->once()->andReturn(null);
        });

        $response = $this->postJson('/pointages', [
            'photo' => UploadedFile::fake()->image('vide.jpg'),
            'type'  => 'arrivee',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_retourne_404_si_aucun_employe_reconnu(): void
    {
        EmployeProfile::factory()->create([
            'encodage_facial' => array_fill(0, 128, 0.5),
        ]);

        $this->mock(FaceRecognitionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('encode')->once()
                ->andReturn(array_fill(0, 128, 0.1));
            $mock->shouldReceive('match')
                ->andReturn(['match' => false, 'distance' => 1.0, 'threshold' => 0.6, 'confidence' => 0.0]);
        });

        $response = $this->postJson('/pointages', [
            'photo' => UploadedFile::fake()->image('inconnu.jpg'),
            'type'  => 'arrivee',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
        $this->assertEquals(0, Pointage::count());
    }

    public function test_cree_un_pointage_quand_match_trouve(): void
    {
        $profile = EmployeProfile::factory()->create([
            'encodage_facial' => array_fill(0, 128, 0.1),
        ]);

        $this->mock(FaceRecognitionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('encode')->once()
                ->andReturn(array_fill(0, 128, 0.1));
            $mock->shouldReceive('match')->once()
                ->andReturn(['match' => true, 'distance' => 0.2, 'threshold' => 0.6, 'confidence' => 0.8]);
        });

        $response = $this->postJson('/pointages', [
            'photo' => UploadedFile::fake()->image('selfie.jpg'),
            'type'  => 'arrivee',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('employe.id', $profile->id)
            ->assertJsonPath('pointage.type', 'arrivee')
            ->assertJsonPath('distance', 0.2)
            ->assertJsonPath('confidence', 0.8);

        $this->assertDatabaseHas('pointages', [
            'employe_id' => $profile->id,
            'type'       => 'arrivee',
            'manuel'     => false,
        ]);
    }

    public function test_choisit_le_meilleur_match_parmi_plusieurs_candidats(): void
    {
        $loin   = EmployeProfile::factory()->create(['encodage_facial' => array_fill(0, 128, 0.9)]);
        $proche = EmployeProfile::factory()->create(['encodage_facial' => array_fill(0, 128, 0.1)]);
        $moyen  = EmployeProfile::factory()->create(['encodage_facial' => array_fill(0, 128, 0.5)]);

        $this->mock(FaceRecognitionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('encode')->once()
                ->andReturn(array_fill(0, 128, 0.1));

            $mock->shouldReceive('match')
                ->andReturnUsing(function ($e1, $e2) {
                    $first = $e2[0];
                    return [
                        'match'      => true,
                        'distance'   => abs($first - 0.1),
                        'threshold'  => 0.6,
                        'confidence' => 1.0 - abs($first - 0.1),
                    ];
                });
        });

        $response = $this->postJson('/pointages', [
            'photo' => UploadedFile::fake()->image('selfie.jpg'),
            'type'  => 'depart',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('employe.id', $proche->id);
    }

    public function test_ignore_employes_sans_encodage_facial(): void
    {
        EmployeProfile::factory()->create(['encodage_facial' => null]);

        $this->mock(FaceRecognitionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('encode')->once()
                ->andReturn(array_fill(0, 128, 0.1));
            $mock->shouldNotReceive('match');
        });

        $response = $this->postJson('/pointages', [
            'photo' => UploadedFile::fake()->image('selfie.jpg'),
            'type'  => 'arrivee',
        ]);

        $response->assertStatus(404);
    }

    public function test_le_pointage_est_marque_non_manuel(): void
    {
        $profile = EmployeProfile::factory()->create([
            'encodage_facial' => array_fill(0, 128, 0.1),
        ]);

        $this->mock(FaceRecognitionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('encode')->andReturn(array_fill(0, 128, 0.1));
            $mock->shouldReceive('match')
                ->andReturn(['match' => true, 'distance' => 0.1, 'threshold' => 0.6, 'confidence' => 0.9]);
        });

        $this->postJson('/pointages', [
            'photo' => UploadedFile::fake()->image('selfie.jpg'),
            'type'  => 'arrivee',
        ]);

        $pointage = Pointage::first();
        $this->assertFalse($pointage->manuel);
        $this->assertNull($pointage->motif_manuel);
    }
}
