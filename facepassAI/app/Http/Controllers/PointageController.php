<?php

namespace App\Http\Controllers;

use App\Models\EmployeProfile;
use App\Models\Pointage;
use App\Services\FaceRecognitionService;
use App\Services\PointageQueryService;
use App\Services\PointageTypeResolver;
use App\Services\RetardService;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Contrôleur du pointage biométrique.
 *
 * Routes :
 * - GET  /pointer            → page kiosque (caméra WebRTC, public)
 * - POST /pointages          → reçoit photo + type, identifie l'employé
 * - GET  /pointages/manuel   → form gestionnaire (auth + role)
 * - POST /pointages/manuel   → enregistre un pointage manuel
 *
 * Règles métier :
 *   - Sprint 3 US-032 : reconnaissance faciale
 *   - Sprint 4 US-034 : limite 4 pointages/jour/employé
 *   - Sprint 4 US-035 : max 3 tentatives en session avant 429
 *   - Sprint 4 US-036 : pointage manuel par gestionnaire (fallback caméra)
 */
class PointageController extends Controller
{
    public const MAX_FAILED_ATTEMPTS  = 3;
    public const SESSION_FAILURES_KEY = 'pointage_failed_attempts';

    /** Page kiosque publique (caméra WebRTC). */
    public function create(): View
    {
        return view('pointer.index');
    }

    // ========================================================================
    // Sprint 5 carte 1 (US-060) — Historique complet des pointages
    // ========================================================================

    /**
     * Tableau paginé et filtrable des pointages
     * (vue gestionnaire / consultant / admin).
     *
     * Filtres GET : employe_id, date_from, date_to, type, manuel.
     * Tri GET     : sort (created_at|type|employe_id), dir (asc|desc).
     */
    public function historique(Request $request, PointageQueryService $service): View
    {
        $filters = [
            'employe_id' => $request->input('employe_id'),
            'date_from'  => $request->input('date_from'),
            'date_to'    => $request->input('date_to'),
            'type'       => $request->input('type'),
            'manuel'     => $request->input('manuel'),
        ];

        $sortBy  = (string) $request->input('sort', 'created_at');
        $sortDir = (string) $request->input('dir', 'desc');

        $pointages = $service->paginate($filters, $sortBy, $sortDir);
        $counts    = $service->countsByType($filters);

        // Liste des employés pour le filtre (ceux qui ont au moins 1 pointage)
        $employeIds = Pointage::query()->select('employe_id')->distinct()->pluck('employe_id');
        $employes   = EmployeProfile::with('user')
            ->whereIn('id', $employeIds)
            ->get()
            ->sortBy(fn ($e) => $e->user->name ?? '')
            ->values();

        return view('pointer.historique', compact(
            'pointages',
            'counts',
            'employes',
            'filters',
            'sortBy',
            'sortDir'
        ));
    }

    // ========================================================================
    // Sprint 5 carte 4 (US-062) — Vue retards & départs anticipés
    // ========================================================================

    /**
     * Liste filtrable des pointages anormaux (retards d'arrivée/retour de
     * pause, départs anticipés / pauses anticipées) avec écart en minutes.
     *
     * Filtres GET : employe_id, date_from, date_to, categorie
     *   - categorie = 'retard' | 'depart_anticipe' | null (tous)
     */
    public function retards(
        Request $request,
        PointageQueryService $queryService
    ): View {
        // RetardService lit le singleton JoursTravail à la construction —
        // on l'instancie à la main pour éviter une injection avec un model vide.
        $retardService = RetardService::fromCurrent();

        $filters = [
            'employe_id' => $request->input('employe_id'),
            'date_from'  => $request->input('date_from'),
            'date_to'    => $request->input('date_to'),
        ];
        $categorie = $request->input('categorie'); // 'retard' | 'depart_anticipe' | null

        // On charge les pointages filtrés (base), puis on applique le
        // prédicat retard/depart-anticipé en PHP (logique portée par le
        // RetardService et donc portable entre SGBD).
        $all = $queryService->query($filters)->get();

        $filtered = $all->filter(function ($p) use ($retardService, $categorie) {
            $isR  = $retardService->isRetard($p->type, $p->created_at);
            $isDA = $retardService->isDepartAnticipe($p->type, $p->created_at);

            if (!$isR && !$isDA) {
                return false;
            }
            if ($categorie === 'retard' && !$isR) {
                return false;
            }
            if ($categorie === 'depart_anticipe' && !$isDA) {
                return false;
            }
            return true;
        })->values();

        // Pagination manuelle (puisque le filtrage est PHP-side)
        $perPage = 20;
        $page    = max(1, (int) $request->input('page', 1));
        $slice   = $filtered->slice(($page - 1) * $perPage, $perPage)->values();

        // Attacher l'analyse à chaque pointage pour la vue
        $slice->each(function ($p) use ($retardService) {
            $p->analyse = $retardService->analyserPointage($p);
        });

        $pointages = new LengthAwarePaginator(
            $slice,
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // KPI globaux (sur le set filtré par employé/date, sans filtre catégorie)
        $countRetards = $all->filter(
            fn ($p) => $retardService->isRetard($p->type, $p->created_at)
        )->count();
        $countDeparts = $all->filter(
            fn ($p) => $retardService->isDepartAnticipe($p->type, $p->created_at)
        )->count();

        // Liste des employés ayant des pointages (filtre dropdown)
        $employeIds = Pointage::query()->select('employe_id')->distinct()->pluck('employe_id');
        $employes   = EmployeProfile::with('user')
            ->whereIn('id', $employeIds)
            ->get()
            ->sortBy(fn ($e) => $e->user->name ?? '')
            ->values();

        return view('pointer.retards', [
            'pointages'    => $pointages,
            'employes'     => $employes,
            'filters'      => array_merge($filters, ['categorie' => $categorie]),
            'countRetards' => $countRetards,
            'countDeparts' => $countDeparts,
        ]);
    }

    /**
     * Export CSV rapide de la même vue (mêmes filtres + catégorie).
     */
    public function exportRetards(
        Request $request,
        PointageQueryService $queryService
    ): StreamedResponse {
        $retardService = RetardService::fromCurrent();

        $filters = [
            'employe_id' => $request->input('employe_id'),
            'date_from'  => $request->input('date_from'),
            'date_to'    => $request->input('date_to'),
        ];
        $categorie = $request->input('categorie');

        $rows = $queryService->query($filters)->get()->filter(function ($p) use ($retardService, $categorie) {
            $isR  = $retardService->isRetard($p->type, $p->created_at);
            $isDA = $retardService->isDepartAnticipe($p->type, $p->created_at);
            if (!$isR && !$isDA) return false;
            if ($categorie === 'retard' && !$isR) return false;
            if ($categorie === 'depart_anticipe' && !$isDA) return false;
            return true;
        });

        $filename = 'retards-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows, $retardService) {
            $out = fopen('php://output', 'w');
            // BOM UTF-8 pour Excel
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'Employé', 'Matricule', 'Date', 'Heure', 'Type',
                'Heure théorique', 'Écart (min)', 'Catégorie',
            ], ';');

            foreach ($rows as $p) {
                $a = $retardService->analyserPointage($p);
                fputcsv($out, [
                    $p->employe->user->name ?? ('#' . $p->employe_id),
                    $p->employe->matricule ?? '',
                    $p->created_at->format('d/m/Y'),
                    $a['heure_reelle'],
                    $p->type,
                    $a['heure_theorique'] ?? '',
                    $a['ecart_minutes'],
                    $a['is_retard'] ? 'Retard' : 'Départ anticipé',
                ], ';');
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Enregistre un pointage biométrique (kiosque).
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

        // Limite 3 tentatives en session
        $attempts = (int) $request->session()->get(self::SESSION_FAILURES_KEY, 0);
        if ($attempts >= self::MAX_FAILED_ATTEMPTS) {
            Log::warning('Pointage : limite de tentatives atteinte', [
                'ip' => $request->ip(), 'attempts' => $attempts,
            ]);
            return response()->json([
                'success'      => false,
                'message'      => "Trop d'échecs successifs. Veuillez contacter un gestionnaire.",
                'attempts'     => $attempts,
                'max_attempts' => self::MAX_FAILED_ATTEMPTS,
                'remaining'    => 0,
            ], 429);
        }

        $embedding = $faceService->encode($validated['photo']);
        if (!$embedding) {
            return $this->registerFailure($request,
                "Aucun visage détecté sur la photo ou service de reconnaissance indisponible.",
                422, 'face_not_detected');
        }

        $match = $this->findBestMatch($faceService, $embedding);
        if (!$match) {
            return $this->registerFailure($request,
                "Aucun employé reconnu sur cette photo.",
                404, 'employe_not_recognized');
        }

        /** @var EmployeProfile $profile */
        $profile = $match['profile'];

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

    // ============================================================
    // Sprint 4 US-036 — Pointage manuel (fallback caméra)
    // ============================================================

    /**
     * Affiche le formulaire de pointage manuel (gestionnaire/admin uniquement).
     */
    public function manualCreate(): View
    {
        $employes = EmployeProfile::with('user')
            ->orderBy('matricule')
            ->get();

        return view('pointer.manual', compact('employes'));
    }

    /**
     * Enregistre un pointage manuel avec motif justificatif.
     */
    public function manualStore(Request $request, PointageTypeResolver $resolver): RedirectResponse
    {
        $validated = $request->validate([
            'employe_id' => ['required', 'integer', 'exists:employes,id'],
            'type'       => ['required', Rule::in(Pointage::TYPES)],
            'motif'      => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'motif.required' => 'Vous devez préciser un motif justifiant ce pointage manuel.',
            'motif.min'      => 'Le motif doit faire au moins 5 caractères.',
        ]);

        /** @var EmployeProfile $profile */
        $profile = EmployeProfile::with('user')->findOrFail($validated['employe_id']);

        // Limite 4 pointages/jour applicable aussi au manuel
        if ($resolver->dayCompleted($profile)) {
            return back()
                ->withErrors([
                    'employe_id' => $profile->user->name
                        . ' a déjà fait ses 4 pointages aujourd\'hui.',
                ])
                ->withInput();
        }

        $pointage = Pointage::create([
            'employe_id'   => $profile->id,
            'type'         => $validated['type'],
            'manuel'       => true,
            'motif_manuel' => $validated['motif'],
        ]);

        Log::info('Pointage manuel créé', [
            'pointage_id'     => $pointage->id,
            'gestionnaire_id' => $request->user()->id,
            'employe_id'      => $profile->id,
            'type'            => $validated['type'],
            'motif'           => $validated['motif'],
        ]);

        return redirect()
            ->route('pointages.manual.create')
            ->with('success',
                "Pointage manuel ({$validated['type']}) enregistré pour "
                . ($profile->user->name ?? $profile->matricule) . '.');
    }

    // ============================================================
    // Helpers privés
    // ============================================================

    protected function registerFailure(Request $request, string $message, int $status, string $reason): JsonResponse
    {
        $newCount = (int) $request->session()->increment(self::SESSION_FAILURES_KEY);

        Log::warning("Pointage : échec de reconnaissance (tentative {$newCount}/" . self::MAX_FAILED_ATTEMPTS . ')', [
            'reason' => $reason, 'ip' => $request->ip(),
            'user_agent' => $request->userAgent(), 'attempts' => $newCount,
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
            if (!is_array($reference) || count($reference) !== 128) continue;

            $result = $faceService->match($embedding, $reference);
            if (!$result || !($result['match'] ?? false)) continue;

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
