<?php

namespace Tests\Feature;

use App\Models\EmployeProfile;
use App\Models\Pointage;
use App\Services\FaceRecognitionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Tests d'audit BNF-06 (Sprint 4 carte 14, US-037).
 *
 * Vérifie automatiquement les propriétés de confidentialité documentées
 * dans docs/AUDIT_BNF06_CONFIDENTIALITE.md.
 */
class AuditBNF06Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');
    }

    /**
     * BNF-06 §4 : aucune photo ne doit être écrite dans le stockage
     * lors d'un pointage via le kiosque.
     */
    public function test_la_photo_de_pointage_n_est_pas_persistee_sur_disque(): void
    {
        $profile = EmployeProfile::factory()->create([
            'encodage_facial' => array_fill(0, 128, 0.1),
        ]);

        $this->mock(FaceRecognitionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('encode')->andReturn(array_fill(0, 128, 0.1));
            $mock->shouldReceive('match')->andReturn([
                'match' => true, 'distance' => 0.2, 'threshold' => 0.6, 'confidence' => 0.8,
            ]);
        });

        $this->postJson('/pointages', [
            'photo' => UploadedFile::fake()->image('selfie.jpg'),
            'type'  => 'arrivee',
        ])->assertStatus(201);

        // Aucun fichier dans aucun sous-dossier du disque public
        $allFiles = Storage::disk('public')->allFiles();
        $this->assertEmpty($allFiles,
            "BNF-06 violée : des fichiers ont été persistés pendant un pointage : "
            . implode(', ', $allFiles));
    }

    /**
     * BNF-06 §3.4 : l'encodage stocké doit être un tableau de 128 floats,
     * jamais une référence à un fichier image.
     */
    public function test_l_encodage_facial_stocke_est_un_tableau_de_128_floats(): void
    {
        $embedding = array_fill(0, 128, 0.42);
        $profile   = EmployeProfile::factory()->create([
            'encodage_facial' => $embedding,
        ]);

        $reloaded = EmployeProfile::find($profile->id);

        $this->assertIsArray($reloaded->encodage_facial);
        $this->assertCount(128, $reloaded->encodage_facial);
        foreach ($reloaded->encodage_facial as $value) {
            $this->assertIsFloat($value, 'L\'embedding doit contenir uniquement des floats');
        }
    }

    /**
     * BNF-06 §4.3 : la réponse JSON du kiosque ne doit jamais contenir
     * ni l'image ni l'embedding brut, uniquement des métadonnées
     * (distance, confidence, id employé).
     */
    public function test_la_reponse_du_kiosque_ne_contient_ni_photo_ni_embedding(): void
    {
        $profile = EmployeProfile::factory()->create([
            'encodage_facial' => array_fill(0, 128, 0.1),
        ]);

        $this->mock(FaceRecognitionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('encode')->andReturn(array_fill(0, 128, 0.1));
            $mock->shouldReceive('match')->andReturn([
                'match' => true, 'distance' => 0.2, 'threshold' => 0.6, 'confidence' => 0.8,
            ]);
        });

        $response = $this->postJson('/pointages', [
            'photo' => UploadedFile::fake()->image('selfie.jpg'),
            'type'  => 'arrivee',
        ])->assertStatus(201);

        $json = $response->json();

        // Aucun champ qui ressemble à une image ou un embedding
        $this->assertArrayNotHasKey('photo', $json);
        $this->assertArrayNotHasKey('image', $json);
        $this->assertArrayNotHasKey('embedding', $json);

        // Et rien d'image-like dans la sous-réponse employe
        $this->assertArrayNotHasKey('photo_faciale', $json['employe'] ?? []);
        $this->assertArrayNotHasKey('encodage_facial', $json['employe'] ?? []);
    }

    /**
     * BNF-06 §5.3 : les logs serveur ne doivent contenir ni image ni embedding.
     * On vérifie que les warnings d'échec n'incluent pas la photo en base64
     * (qui pourrait fuiter dans les fichiers de log).
     */
    public function test_les_logs_n_incluent_pas_d_image_ou_d_embedding(): void
    {
        $this->mock(FaceRecognitionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('encode')->andReturn(null);
        });

        // On capture les logs émis pendant la requête
        $loggedMessages = [];
        \Illuminate\Support\Facades\Log::listen(function ($message) use (&$loggedMessages) {
            $loggedMessages[] = json_encode($message->context);
        });

        $this->postJson('/pointages', [
            'photo' => UploadedFile::fake()->image('floue.jpg'),
            'type'  => 'arrivee',
        ]);

        $allLogs = implode(' ', $loggedMessages);

        // Aucune chaîne base64 longue (signe potentiel d'une image encodée)
        $this->assertDoesNotMatchRegularExpression(
            '/[A-Za-z0-9+\/]{200,}/',
            $allLogs,
            'Un log semble contenir une donnée encodée en base64 (image ?)'
        );

        // Pas de tableau de 128 floats dans les logs
        $this->assertStringNotContainsString('embedding', strtolower($allLogs));
    }

    /**
     * BNF-06 §4.4 : le pointage manuel n'utilise pas d'image,
     * donc aucune photo ne doit être stockée non plus.
     */
    public function test_le_pointage_manuel_ne_stocke_aucune_image(): void
    {
        $profile      = EmployeProfile::factory()->create();
        $gestionnaire = \App\Models\Gestionnaire::factory()->create();
        $gestionnaire->assignRole(\App\Enums\Role::Gestionnaire->value);

        $this->actingAs($gestionnaire)
            ->post('/pointages/manuel', [
                'employe_id' => $profile->id,
                'type'       => 'arrivee',
                'motif'      => 'Caméra HS — pointage manuel',
            ])->assertRedirect(route('pointages.manual.create'));

        $this->assertEmpty(Storage::disk('public')->allFiles());

        // Le pointage est marqué manuel mais la colonne photo_capture reste null
        $pointage = Pointage::first();
        $this->assertNull($pointage->photo_capture);
        $this->assertTrue($pointage->manuel);
    }
}
