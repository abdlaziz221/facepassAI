<?php
// app/Services/FaceRecognitionService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class FaceRecognitionService
{
    protected string $baseUrl;
    protected int $timeout;
    protected int $retries;

    public function __construct()
    {
        $this->baseUrl = config('services.face_recognition.url', 'http://localhost:8001');
        $this->timeout = config('services.face_recognition.timeout', 3);
        $this->retries = config('services.face_recognition.retries', 2);
    }

    public function encode(UploadedFile $photo): ?array
    {
        $attempts = 0;
        $lastError = null;

        while ($attempts < $this->retries) {
            try {
                $response = Http::timeout($this->timeout)
                    ->attach('file', fopen($photo->path(), 'r'), $photo->getClientOriginalName())
                    ->post($this->baseUrl . '/encode');

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['embedding'] ?? null;
                }

                $lastError = "HTTP " . $response->status();
                Log::warning("Face encode attempt " . ($attempts + 1) . " failed", [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                Log::error("Face encode exception attempt " . ($attempts + 1), [
                    'error' => $lastError
                ]);
            }

            $attempts++;
            if ($attempts < $this->retries) {
                sleep(1);
            }
        }

        Log::error("Face encode failed after {$this->retries} attempts", [
            'last_error' => $lastError
        ]);

        return null;
    }

    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(2)->get($this->baseUrl . '/health');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}