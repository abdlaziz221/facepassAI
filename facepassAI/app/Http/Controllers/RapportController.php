<?php

namespace App\Http\Controllers;

use App\Exports\RapportPresenceExport;
use App\Http\Requests\GenererRapportRequest;
use App\Models\EmployeProfile;
use App\Models\Pointage;
use App\Services\RetardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sprint 5 carte 10 (US-072) — Interface de génération de rapports.
 *
 * Routes :
 *   GET  /rapports          → formulaire (période + type + format)
 *   POST /rapports/generer  → téléchargement direct du PDF ou Excel
 */
class RapportController extends Controller
{
    /** Formulaire de génération. */
    public function index(): View
    {
        $employes = EmployeProfile::with('user')
            ->get()
            ->sortBy(fn ($e) => $e->user->name ?? '')
            ->values();

        return view('rapports.index', compact('employes'));
    }

    /** Génère et télécharge le rapport selon le format demandé. */
    public function generer(GenererRapportRequest $request): Response
    {
        $data = $request->validated();

        $pointages = Pointage::with('employe.user')
            ->whereDate('created_at', '>=', $data['date_debut'])
            ->whereDate('created_at', '<=', $data['date_fin'])
            ->when(
                $data['employe_id'] ?? null,
                fn ($q, $id) => $q->where('employe_id', $id)
            )
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $retardService = RetardService::fromCurrent();
        $employe = isset($data['employe_id'])
            ? EmployeProfile::with('user')->find($data['employe_id'])
            : null;

        $filename = sprintf(
            'rapport-presences-%s-au-%s',
            $data['date_debut'],
            $data['date_fin']
        );

        if ($data['format'] === 'pdf') {
            $countRetards = $pointages
                ->filter(fn ($p) => $retardService->isRetard($p->type, $p->created_at))
                ->count();
            $countDeparts = $pointages
                ->filter(fn ($p) => $retardService->isDepartAnticipe($p->type, $p->created_at))
                ->count();

            $pdf = Pdf::loadView('pdf.rapport-presences', [
                'pointages'     => $pointages,
                'retardService' => $retardService,
                'data'          => $data,
                'employe'       => $employe,
                'countRetards'  => $countRetards,
                'countDeparts'  => $countDeparts,
            ])->setPaper('a4', 'portrait');

            return $pdf->download($filename . '.pdf');
        }

        return Excel::download(
            new RapportPresenceExport($pointages, $retardService, $data),
            $filename . '.xlsx'
        );
    }
}
