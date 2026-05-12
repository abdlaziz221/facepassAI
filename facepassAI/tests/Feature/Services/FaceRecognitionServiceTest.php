<?php

namespace Tests\Feature\Services;

use App\Services\FaceRecognitionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests du wrapper Laravel autour du microservice face-service.
 * Utilise Http::fake() — pas besoin que le microservice tourne pour les tests.
 */
class FaceRecognitionServiceTest extends TestCase
{
    protected FaceRecognitionService $service;
    protected string $baseUrl = 'http://face-service-test:8001';

    protected function setUp(): void
    {
        parent::setUp();

        // Force l'URL pour avoir des assertions stables
        config([
            'services.face_recognition.url'     => $this->baseUrl,
            'services.face_recognition.timeout' => 3,
            'services.face_recognition.retries' => 2,
        ]);

        $this->service = new FaceRecognitionService();
    }

    // ============================================================
    // encode()
    // ============================================================

    public function test_encode_retourne_embedding_quand_succes(): void
    {
        $fakeEmbedding = array_fill(0, 128, 0.1);
        Http::fake([
            "{$this->baseUrl}/encode" => Http::response([
                'detected'    => true,
                'embedding'   => $fakeEmbedding,
                'faces_found' => 1,
                'message'     => 'OK',
            ], 200),
        ]);

        $photo  = UploadedFile::fake()->image('selfie.jpg');
        $result = $this->service->encode($photo);

        $this->assertIsArray($result);
        $this->assertCount(128, $result);
        $this->assertEquals($fakeEmbedding, $result);
    }

    public function test_encode_retourne_null_si_aucun_visage(): void
    {
        Http::fake([
            "{$this->baseUrl}/encode" => Http::response([
                'detail' => "Aucun visage détecté sur l'image.",
            ], 400),
        ]);

        $photo  = UploadedFile::fake()->image('vide.jpg');
        $result = $this->service->encode($photo);

        $this->assertNull($result);
    }

    public function test_encode_retourne_null_si_erreur_serveur(): void
    {
        Http::fake([
            "{$this->baseUrl}/encode" => Http::response('Internal Server Error', 500),
        ]);

        $photo  = UploadedFile::fake()->image('selfie.jpg');
        $result = $this->service->encode($photo);

        $this->assertNull($result);
    }

    public function test_encode_retourne_null_si_connexion_impossible(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Network unreachable');
        });

        $photo  = UploadedFile::fake()->image('selfie.jpg');
        $result = $this->service->encode($photo);

        $this->assertNull($result);
    }

    public function test_encode_envoie_bien_le_fichier_en_multipart(): void
    {
        Http::fake([
            "{$this->baseUrl}/encode" => Http::response([
                'embedding' => array_fill(0, 128, 0.1),
            ], 200),
        ]);

        $photo = UploadedFile::fake()->image('test.jpg');
        $this->service->encode($photo);

        Http::assertSent(function ($request) {
            return $request->url() === "{$this->baseUrl}/encode"
                && $request->method() === 'POST'
                && $request->isMultipart();
        });
    }

    // ============================================================
    // match()
    // ============================================================

    public function test_match_retourne_donnees_complete(): void
    {
        $expected = [
            'match'      => true,
            'distance'   => 0.3214,
            'threshold'  => 0.6,
            'confidence' => 0.6786,
        ];
        Http::fake([
            "{$this->baseUrl}/match" => Http::response($expected, 200),
        ]);

        $emb1   = array_fill(0, 128, 0.1);
        $emb2   = array_fill(0, 128, 0.12);
        $result = $this->service->match($emb1, $emb2);

        $this->assertEquals($expected, $result);
        $this->assertTrue($result['match']);
    }

    public function test_match_envoie_threshold_custom(): void
    {
        Http::fake([
            "{$this->baseUrl}/match" => Http::response([
                'match'      => false,
                'distance'   => 0.5,
                'threshold'  => 0.3,
                'confidence' => 0.5,
            ], 200),
        ]);

        $emb1 = array_fill(0, 128, 0.1);
        $emb2 = array_fill(0, 128, 0.5);
        $this->service->match($emb1, $emb2, 0.3);

        Http::assertSent(function ($request) {
            $data = $request->data();
            return isset($data['threshold']) && $data['threshold'] === 0.3;
        });
    }

    public function test_match_retourne_null_si_payload_invalide(): void
    {
        Http::fake([
            "{$this->baseUrl}/match" => Http::response([
                'detail' => 'Les embeddings doivent avoir une taille de 128',
            ], 400),
        ]);

        $emb1   = [0.1, 0.2]; // taille 2 au lieu de 128
        $emb2   = array_fill(0, 128, 0.1);
        $result = $this->service->match($emb1, $emb2);

        $this->assertNull($result);
    }

    // ============================================================
    // isAvailable()
    // ============================================================

    public function test_is_available_retourne_true_si_health_ok(): void
    {
        Http::fake([
            "{$this->baseUrl}/health" => Http::response([
                'status' => 'ok',
            ], 200),
        ]);

        $this->assertTrue($this->service->isAvailable());
    }

    public function test_is_available_retourne_false_si_service_down(): void
    {
        Http::fake([
            "{$this->baseUrl}/health" => Http::response(null, 503),
        ]);

        $this->assertFalse($this->service->isAvailable());
    }
}
