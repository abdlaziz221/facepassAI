<?php

namespace App\Http\Controllers;

use App\Models\EmployeProfile;
use App\Models\Pointage;
use App\Services\FaceRecognitionService;
use App\Services\PointageTypeResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Contrôleur du pointage biométrique (Sprint 3 US-032 + Sprint 4 US-034).
 *
 * - GET  /pointer    → affiche la page kiosque (caméra WebRTC)
 * - POST /pointages  → reçoit la photo + type, identifie l'employé, crée le pointage
 *
 * Règles métier appliquées :
 *   - Sprint 3 : reconnaissance faciale (microservice Python)
 *   - Sprint 4 US-034 : limite de 4 pointages par jour et par employé
 */
class PointageController extends Controller
{
    /**
     * Affiche la page de pointage kiosque (vue Blade avec caméra WebRTC).
     */
    public function create(): View
    {
        return view('pointer.index');
    }

    /**
     * Enregistre un pointage à partir d'une photo et d'un type.
     *
     * Workflow :
     *   1. Valide la photo et le type
     *   2. Encode la photo via le microservice Python → embedding 128D
     *   3. Itère sur tous les EmployeProfile avec un encodage facial stocké
     *   4. Compare chaque embedding stocké à celui de la photo
     *   5. Garde le meilleur match (distance la plus faible)
     *   6. Si aucun match, retourne 404
     *   7. Si l'employé a déjà fait ses 4 pointages aujourd'hui → 422
     *   8. Sinon, crée le Pointage rattaché à l'employé identifié
     */
    public function store(
        Request $request,
        FaceRecognitionService $faceService,
        PointageTypeResolver $resolver
    ): JsonResponse {
        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:5120'], // 5 Mo max
            'type'  => ['required', Rule::in(Pointage::TYPES)],
        ]);

        $embedding = $faceService->encode($validated['photo']);
        if (!$embedding) {
            return response()->json([
                'success' => false,
                'message' => "Aucun visage détecté sur la photo ou service de reconnaissance indisponible.",
            ], 422);
        }

        $match = $this->findBestMatch($faceService, $embedding);
        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => "Aucun employé reconnu sur cette photo.",
            ], 404);
        }

        /** @var EmployeProfile $profile */
        $profile = $match['profile'];

        // Sprint 4 US-034 : limite de 4 pointages par jour
        if ($resolver->dayCompleted($profile)) {
            return response()->json([
                'success' => false,
                'message' => "Limite atteinte : " . ($profile->user->name ?? 'Cet employé')
                    . " a déjà fait ses 4 pointages aujourd'hui.",
            ], 422);
        }

        $pointage = Pointage::create([
            'employe_id' => $profile->id,
            'type'       => $validated['type'],
            'manuel'     => false,
        ]);

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
