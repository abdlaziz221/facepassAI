<?php

namespace App\Http\Controllers;

use App\Models\EmployeProfile;
use App\Models\Pointage;
use App\Services\FaceRecognitionService;
use App\Services\PointageTypeResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Contrôleur du pointage biométrique.
 *
 * - GET  /pointer    → affiche la page kiosque (caméra WebRTC)
 * - POST /pointages  → reçoit la photo + type, identifie l'employé, crée le pointage
 *
 * Règles métier appliquées :
 *   - Sprint 3 US-032 : reconnaissance faciale (microservice Python)
 *   - Sprint 4 US-034 : limite de 4 pointages par jour et par employé
 *   - Sprint 4 US-035 : gestion des échecs (max 3 tentatives en session)
 */
class PointageController extends Controller
{
    /** Nombre maximal de tentatives d'échec en session avant blocage. */
    public const MAX_FAILED_ATTEMPTS = 3;

    /** Clé de session pour stocker le compteur d'échecs. */
    public const SESSION_FAILURES_KEY = 'pointage_failed_attempts';

    /**
     * Affiche la page de pointage kiosque (vue Blade avec caméra WebRTC).
     */
    public function create(): View
    {
        return view('pointer.index');
    }

    /**
     * Enregistre un pointage à partir d'une photo et d'un type.
     */
    public function store(
        Request $request,
        FaceRecognitionService $faceService,
        PointageTypeResolver $resolver
    ): JsonResponse {
        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:5120'],
            'type'  => ['required', Rule::in(Pointage::TYPES)],
        ]);

        // Sprint 4 US-035 : vérifier le compteur d'échecs en session
        $attempts = (int) $request->session()->get(self::SESSION_FAILURES_KEY, 0);
        if ($attempts >= self::MAX_FAILED_ATTEMPTS) {
            Log::warning('Pointage : limite de tentatives atteinte', [
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
                'attempts'   => $attempts,
            ]);

            return response()->json([
                'success'      => false,
                'message'      => "Trop d'échecs successifs. Veuillez contacter un gestionnaire.",
                'attempts'     => $attempts,
                'max_attempts' => self::MAX_FAILED_ATTEMPTS,
                'remaining'    => 0,
            ], 429);
        }

        // 1. Encoder la photo
        $embedding = $faceService->encode($validated['photo']);
        if (!$embedding) {
            return $this->registerFailure(
                $request,
                "Aucun visage détecté sur la photo ou service de reconnaissance indisponible.",
                422,
                'face_not_detected'
            );
        }

        // 2. Identifier l'employé
        $match = $this->findBestMatch($faceService, $embedding);
        if (!$match) {
            return $this->registerFailure(
                $request,
                "Aucun employé reconnu sur cette photo.",
                404,
                'employe_not_recognized'
            );
        }

        /** @var EmployeProfile $profile */
        $profile = $match['profile'];

        // Sprint 4 US-034 : limite 4 pointages/jour
        // Note : ce n'est PAS un échec de reconnaissance, on ne touche pas au compteur.
        if ($resolver->dayCompleted($profile)) {
            return response()->json([
                'success' => false,
                'message' => "Limite atteinte : " . ($profile->user->name ?? 'Cet employé')
                    . " a déjà fait ses 4 pointages aujourd'hui.",
            ], 422);
        }

        // 3. Créer le pointage et reset le compteur d'échecs
        $pointage = Pointage::create([
            'employe_id' => $profile->id,
            'type'       => $validated['type'],
            'manuel'     => false,
        ]);

        $request->session()->forget(self::SESSION_FAILURES_KEY);

        return response()->json([
            'success'  => true,
            'pointage' => [
                'id'         => $pointage->id,
                'type'       => $pointage->type,
                'created_at' => $pointage->created_at->toIso8601String(),
            ],
            'employe' => [
                'id'        => $profile->id,
                'matricule' => $profile->matricule,
                'nom'       => $profile->user->name ?? null,
            ],
            'distance'   => $match['distance'],
            'confidence' => $match['confidence'],
        ], 201);
    }

    /**
     * Enregistre un échec de reconnaissance : incrémente le compteur en session
     * et journalise l'événement côté serveur.
     */
    protected function registerFailure(Request $request, string $message, int $status, string $reason): JsonResponse
    {
        $newCount = (int) $request->session()->increment(self::SESSION_FAILURES_KEY);

        Log::warning("Pointage : échec de reconnaissance (tentative {$newCount}/" . self::MAX_FAILED_ATTEMPTS . ')', [
            'reason'     => $reason,
            'ip'         => $request->ip(),
            'user_agent' => $request->userAgent(),
            'attempts'   => $newCount,
        ]);

        $remaining = max(0, self::MAX_FAILED_ATTEMPTS - $newCount);
        $finalMessage = $remaining > 0
            ? "{$message} (tentative {$newCount}/" . self::MAX_FAILED_ATTEMPTS . ", il reste {$remaining} essai" . ($remaining > 1 ? 's' : '') . ')'
            : "{$message} Vous avez atteint le maximum de tentatives.";

        return response()->json([
            'success'      => false,
            'message'      => $finalMessage,
            'attempts'     => $newCount,
            'max_attempts' => self::MAX_FAILED_ATTEMPTS,
            'remaining'    => $remaining,
        ], $status);
    }

    /**
     * Cherche l'EmployeProfile dont l'embedding stocké est le plus proche
     * de celui fourni. Retourne null si aucun match.
     *
     * @return array{profile: EmployeProfile, distance: float, confidence: float}|null
     */
    protected function findBestMatch(FaceRecognitionService $faceService, array $embedding): ?array
    {
        $bestMatch    = null;
        $bestDistance = PHP_FLOAT_MAX;

        $candidates = EmployeProfile::query()
            ->whereNotNull('encodage_facial')
            ->with('user')
            ->get();

        foreach ($candidates as $profile) {
            $reference = $profile->encodage_facial;

            if (!is_array($reference) || count($reference) !== 128) {
                continue;
            }

            $result = $faceService->match($embedding, $reference);
            if (!$result || !($result['match'] ?? false)) {
                continue;
            }

            if ($result['distance'] < $bestDistance) {
                $bestDistance = $result['distance'];
                $bestMatch    = [
                    'profile'    => $profile,
                    'distance'   => $result['distance'],
                    'confidence' => $result['confidence'],
                ];
            }
        }

        return $bestMatch;
    }
}
