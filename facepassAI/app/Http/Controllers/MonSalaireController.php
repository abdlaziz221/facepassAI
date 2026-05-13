<?php

namespace App\Http\Controllers;

use App\Models\EmployeProfile;
use App\Models\Pointage;
use App\Services\PayrollService;
use App\Services\RetardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sprint 6 cartes 2/3/4 (US-080/082/083) — Vue + export PDF "Mon Salaire" pour l'employé.
 *
 * Routes :
 *   GET /mon-salaire?mois=YYYY-MM     → vue HTML (carte 2)
 *   GET /mon-salaire/pdf?mois=YYYY-MM → téléchargement PDF (carte 3)
 *
 * Carte 4 (US-083) : détection des données manquantes (salaire_brut, matricule)
 * via PayrollService::donneesManquantes() — affichée dans la vue et la fiche PDF.
 */
class MonSalaireController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $profile = EmployeProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            return view('mon-salaire.index', [
                'profile'       => null,
                'salaire'       => null,
                'pointages'     => collect(),
                'moisInput'     => now()->format('Y-m'),
                'year'          => (int) now()->format('Y'),
                'month'         => (int) now()->format('m'),
                'retardService' => null,
                'manquantes'    => [],
            ]);
        }

        [$year, $month, $moisInput] = $this->resolveMois($request);

        $payroll       = PayrollService::fromCurrent();
        $salaire       = $payroll->calculerSalaireMensuel($profile, $year, $month);
        $retardService = RetardService::fromCurrent();
        $manquantes    = PayrollService::donneesManquantes($profile);

        $pointages = Pointage::where('employe_id', $profile->id)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('created_at')
            ->get();

        return view('mon-salaire.index', compact(
            'profile',
            'salaire',
            'pointages',
            'moisInput',
            'year',
            'month',
            'retardService',
            'manquantes'
        ));
    }

    /** Sprint 6 carte 3 (US-082) — Téléchargement PDF de la fiche de paie. */
    public function pdf(Request $request): Response
    {
        $user = $request->user();
        $profile = EmployeProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            abort(404, 'Profil métier introuvable.');
        }

        [$year, $month] = $this->resolveMois($request);

        $payroll    = PayrollService::fromCurrent();
        $salaire    = $payroll->calculerSalaireMensuel($profile, $year, $month);
        $manquantes = PayrollService::donneesManquantes($profile);

        $pdf = Pdf::loadView('pdf.salaire', [
            'profile'    => $profile,
            'salaire'    => $salaire,
            'manquantes' => $manquantes,
            'year'       => $year,
            'month'      => $month,
        ])->setPaper('a4', 'portrait');

        $filename = sprintf(
            'salaire-%s-%04d-%02d.pdf',
            Str::slug($profile->user->name ?? ('employe-' . $profile->id)),
            $year,
            $month
        );

        return $pdf->download($filename);
    }

    /** @return array{0:int,1:int,2:string} [year, month, moisInput] */
    private function resolveMois(Request $request): array
    {
        $moisInput = (string) $request->input('mois', now()->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $moisInput)) {
            $moisInput = now()->format('Y-m');
        }
        [$y, $m] = explode('-', $moisInput);
        return [(int) $y, (int) $m, $moisInput];
    }
}
