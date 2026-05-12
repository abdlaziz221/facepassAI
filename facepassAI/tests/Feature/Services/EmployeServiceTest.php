<?php

namespace Tests\Feature\Services;

use App\Models\EmployeProfile;
use App\Services\EmployeService;
use App\Services\FaceRecognitionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Tests du EmployeService — focus sur l'intégration de l'encodage facial.
 *
 * Sprint 2 T5 — Upload photo → /encode → encodage_facial stocké comme array
 * récupérable par le PointageController.
 */
class EmployeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');
        Notification::fake();
    }

    public function test_create_employe_sans_photo_aucun_encodage(): void
    {
        $service = app(EmployeService::class);

        $profile = $service->createEmploye([
            'name'         => 'Aïssatou Diop',
            'email'        => 'aissatou@exemple.com',
            'matricule'    => 'EMP-001',
            'poste'        => 'Développeuse',
            'departement'  => 'Informatique',
            'salaire_brut' => 500000,
        ], null);

        $this->assertNull($profile->photo_faciale);
        $this->assertNull($profile->encodage_facial);
    }

    public function test_create_employe_avec_photo_stocke_embedding_comme_array(): void
    {
        $fakeEmbedding = array_fill(0, 128, 0.123);

        $this->mock(FaceRecognitionService::class, function (MockInterface $mock) use ($fakeEmbedding) {
            $mock->shouldReceive('encode')->once()->andReturn($fakeEmbedding);
        });

        $service = app(EmployeService::class);
        $photo   = UploadedFile::fake()->image('selfie.jpg');

        $profile = $service->createEmploye([
            'name'         => 'Mamadou Sow',
            'email'        => 'mamadou@exemple.com',
            'matricule'    => 'EMP-002',
            'poste'        => 'Comptable',
            'departement'  => 'Finance',
            'salaire_brut' => 600000,
        ], $photo);

        // Photo stockée
        $this->assertNotNull($profile->photo_faciale);
        Storage::disk('public')->assertExists($profile->photo_faciale);

        // ★ L'encodage est récupéré comme ARRAY (pas une string JSON)
        $reloaded = EmployeProfile::find($profile->id);
        $this->assertIsArray($reloaded->encodage_facial);
        $this->assertCount(128, $reloaded->encodage_facial);
        $this->assertEquals($fakeEmbedding, $reloaded->encodage_facial);
    }

    public function test_create_employe_si_encode_echoue_encodage_reste_null(): void
    {
        $this->mock(FaceRecognitionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('encode')->once()->andReturn(null);
        });

        $service = app(EmployeService::class);
        $photo   = UploadedFile::fake()->image('floue.jpg');

        $profile = $service->createEmploye([
            'name'         => 'Fatou Ndiaye',
            'email'        => 'fatou@exemple.com',
            'matricule'    => 'EMP-003',
            'poste'        => 'Designer',
            'departement'  => 'Marketing',
            'salaire_brut' => 450000,
        ], $photo);

        // Photo stockée quand même (utilisable plus tard)
        $this->assertNotNull($profile->photo_faciale);
        // Mais pas d'encodage
        $this->assertNull($profile->fresh()->encodage_facial);
    }

    public function test_update_employe_avec_nouvelle_photo_remplace_encodage(): void
    {
        $oldEmbedding = array_fill(0, 128, 0.1);
        $newEmbedding = array_fill(0, 128, 0.9);

        // Profil existant avec ancien embedding
        $profile = EmployeProfile::factory()->create([
            'encodage_facial' => $oldEmbedding,
        ]);

        $this->mock(FaceRecognitionService::class, function (MockInterface $mock) use ($newEmbedding) {
            $mock->shouldReceive('encode')->once()->andReturn($newEmbedding);
        });

        $service  = app(EmployeService::class);
        $newPhoto = UploadedFile::fake()->image('nouvelle.jpg');

        $service->updateEmploye($profile, [
            'name'         => $profile->user->name,
            'email'        => $profile->user->email,
            'matricule'    => $profile->matricule,
            'poste'        => $profile->poste,
            'departement'  => $profile->departement,
            'salaire_brut' => $profile->salaire_brut,
        ], $newPhoto);

        $reloaded = $profile->fresh();
        $this->assertIsArray($reloaded->encodage_facial);
        $this->assertEquals($newEmbedding, $reloaded->encodage_facial);
        $this->assertNotEquals($oldEmbedding, $reloaded->encodage_facial);
    }

    public function test_update_employe_sans_nouvelle_photo_conserve_encodage(): void
    {
        $oldEmbedding = array_fill(0, 128, 0.5);

        $profile = EmployeProfile::factory()->create([
            'encodage_facial' => $oldEmbedding,
        ]);

        // FaceRecognitionService NE doit PAS être appelé
        $this->mock(FaceRecognitionService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('encode');
        });

        $service = app(EmployeService::class);

        $service->updateEmploye($profile, [
            'name'         => $profile->user->name,
            'email'        => $profile->user->email,
            'matricule'    => $profile->matricule,
            'poste'        => 'Nouveau poste',
            'departement'  => $profile->departement,
            'salaire_brut' => $profile->salaire_brut,
        ], null);

        $reloaded = $profile->fresh();
        $this->assertEquals('Nouveau poste', $reloaded->poste);
        $this->assertEquals($oldEmbedding, $reloaded->encodage_facial);
    }
}
