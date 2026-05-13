<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use App\Services\DashboardKpiService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

/**
 * Aiguille l'utilisateur vers son dashboard selon son rôle (Sprint 1, US-016).
 *
 * Sprint 6 cartes 10/11/12 (US-100/101/102) — Enrichi avec :
 *   - $kpi     : compteurs clés du jour (présents, retards, absents, demandes)
 *   - $charts  : données pour Chart.js (courbe 30j + camembert statuts)
 *   - $alertes : alertes priorisées par gravité
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $role = $user->role;

        // KPIs globaux (composition des comptes)
        $stats = [
            'total_users'   => User::count(),
            'admins'        => User::where('role', Role::Administrateur)->count(),
            'gestionnaires' => User::where('role', Role::Gestionnaire)->count(),
            'consultants'   => User::where('role', Role::Consultant)->count(),
            'employes'      => User::where('role', Role::Employe)->count(),
        ];

        // Sprint 6 cartes 10/11/12 — KPI métier + charts + alertes
        $kpiService = DashboardKpiService::fromCurrent();
        $kpi    = $kpiService->kpiCards();
        $charts = [
            'presences30'     => $kpiService->presencesParJour(30),
            'statutsAbsences' => $kpiService->repartitionStatutsAbsences(),
        ];
        $alertes = $kpiService->alertes();

        $payload = compact('user', 'stats', 'kpi', 'charts', 'alertes');

        return match ($role) {
            Role::Administrateur => view('dashboards.administrateur', $payload),
            Role::Gestionnaire   => view('dashboards.gestionnaire',   $payload),
            Role::Consultant     => view('dashboards.consultant',     $payload),
            default              => view('dashboards.employe',        $payload),
        };
    }
}
