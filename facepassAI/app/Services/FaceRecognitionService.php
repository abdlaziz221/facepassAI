<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client HTTP vers le microservice Python face-service.
 *
 * Toutes les requêtes utilisent :
 * - un timeout configurable (défaut 3s)
 * - un retry automatique (défaut 2 tentatives, 200ms entre)
 * - du logging en cas d'échec
 *
 * Endpoints consommés :
 * - GET  /health
 * - POST /encode  (multipart, photo)
 * - POST /match   (json, 2 embeddings)
 */
class FaceRecognitionService
{
    protected string $baseUrl;
    protected int $timeout;
    protected int $retries;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.face_recognition.url', 'http://localhost:8001'), '/');
        $this->timeout = (int) config('services.face_recognition.timeout', 3);
        $this->retries = (int) config('services.face_recognition.retries', 2);
    }

    /**
     * Envoie une photo au microservice et retourne l'embedding 128D.
     *
     * @return array<int, float>|null  Embedding (128 floats) ou null si échec
     */
    public function encode(UploadedFile $photo): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retries, 200, throw: false)
                ->attach(
                    'file',
                    file_get_contents($photo->path()),
                    $photo->getClientOriginalName()
                )
                ->post($this->baseUrl . '/encode');

            if ($response->successful()) {
                return $response->json('embedding');
            }

            Log::warning('face-service /encode a échoué', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error('face-service /encode exception', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Compare deux embeddings 128D et retourne le résultat du match.
     *
     * @param array<int, float> $embedding1
     * @param array<int, float> $embedding2
     * @param float|null        $threshold  Seuil custom (sinon défaut microservice)
     * @return array{match: bool, distance: float, threshold: float, confidence: float}|null
     */
    public function match(array $embedding1, array $embedding2, ?float $threshold = null): ?array
    {
        $payload = [
            'embedding1' => $embedding1,
            'embedding2' => $embedding2,
        ];
        if ($threshold !== null) {
            $payload['threshold'] = $threshold;
        }

        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retries, 200, throw: false)
                ->asJson()
                ->post($this->baseUrl . '/match', $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('face-service /match a échoué', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error('face-service /match exception', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Vérifie que le microservice répond. Utile pour healthcheck Laravel.
     */
    public function isAvailable(): bool
    {
        try {
            return Http::timeout(2)
                ->get($this->baseUrl . '/health')
                ->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
