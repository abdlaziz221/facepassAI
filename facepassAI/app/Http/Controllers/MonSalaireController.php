<?php

namespace App\Http\Controllers;

use App\Models\EmployeProfile;
use App\Models\Pointage;
use App\Services\PayrollService;
use App\Services\RetardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Sprint 6 carte 2 (US-080) — Vue "Mon Salaire" pour l'employé.
 *
 * GET /mon-salaire?mois=YYYY-MM (par défaut : mois courant)
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
            ]);
        }

        $moisInput = $request->input('mois', now()->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', (string) $moisInput)) {
            $moisInput = now()->format('Y-m');
        }
        [$y, $m] = explode('-', $moisInput);
        $year  = (int) $y;
        $month = (int) $m;

        $payroll       = PayrollService::fromCurrent();
        $salaire       = $payroll->calculerSalaireMensuel($profile, $year, $month);
        $retardService = RetardService::fromCurrent();

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
            'retardService'
        ));
    }
}
