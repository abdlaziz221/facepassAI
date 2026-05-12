<?php

namespace App\Http\Controllers;

use App\Models\EmployeProfile;
use App\Models\Pointage;
use App\Services\FaceRecognitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Contrôleur du pointage biométrique (Sprint 3, US-032).
 *
 * Workflow de la méthode store() :
 *   1. Valide la photo et le type de pointage (multipart)
 *   2. Encode la photo via le microservice Python → embedding 128D
 *   3. Itère sur tous les EmployeProfile avec un encodage facial stocké
 *   4. Compare chaque embedding stocké à celui de la photo
 *   5. Garde le meilleur match (distance la plus faible)
 *   6. Si aucun match, retourne 404
 *   7. Sinon, crée le Pointage rattaché à l'employé identifié
 */
class PointageController extends Controller
{
    /**
     * Enregistre un pointage à partir d'une photo et d'un type.
     *
     * Réponses :
     *   - 201 : pointage créé, employé identifié
     *   - 422 : validation échouée OU visage non détecté sur la photo
     *   - 404 : aucun employé ne correspond
     *   - 503 : microservice de reconnaissance indisponible
     */
    public function store(Request $request, FaceRecognitionService $faceService): JsonResponse
    {
        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:5120'], // 5 Mo max
            'type'  => ['required', Rule::in(Pointage::TYPES)],
        ]);

        // 1. Encoder la photo reçue → embedding 128D
        $embedding = $faceService->encode($validated['photo']);
        if (!$embedding) {
            return response()->json([
                'success' => false,
                'message' => "Aucun visage détecté sur la photo ou service de reconnaissance indisponible.",
            ], 422);
        }

        // 2. Trouver l'employé dont le visage correspond le mieux
        $match = $this->findBestMatch($faceService, $embedding);
        if (!$match) {
            return response()->json([
                'success' => false,
                'message' => "Aucun employé reconnu sur cette photo.",
            ], 404);
        }

        // 3. Créer le pointage
        $pointage = Pointage::create([
            'employe_id' => $match['profile']->id,
            'type'       => $validated['type'],
            'manuel'     => false,
        ]);

        // 4. Réponse
        return response()->json([
            'success'  => true,
            'pointage' => [
                'id'         => $pointage->id,
                'type'       => $pointage->type,
                'created_at' => $pointage->created_at->toIso8601String(),
            ],
            'employe'    => [
                'id'        => $match['profile']->id,
                'matricule' => $match['profile']->matricule,
                'nom'       => $match['profile']->user->name ?? null,
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
                continue; // donnée corrompue, on ignore
            }

            $result = $faceService->match($embedding, $reference);
            if (!$result || !($result['match'] ?? false)) {
                continue; // microservice down ou pas de match
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
