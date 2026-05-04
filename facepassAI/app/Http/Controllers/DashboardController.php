<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

/**
 * Aiguille l'utilisateur vers son dashboard selon son rôle (Sprint 1, US-016).
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $role = $user->role;

        // KPIs globaux (placeholders - seront enrichis aux Sprints 3-6)
        $stats = [
            'total_users'        => User::count(),
            'admins'             => User::where('role', Role::Administrateur)->count(),
            'gestionnaires'      => User::where('role', Role::Gestionnaire)->count(),
            'consultants'        => User::where('role', Role::Consultant)->count(),
            'employes'           => User::where('role', Role::Employe)->count(),
        ];

        return match ($role) {
            Role::Administrateur => view('dashboards.administrateur', compact('user', 'stats')),
            Role::Gestionnaire   => view('dashboards.gestionnaire',   compact('user', 'stats')),
            Role::Consultant     => view('dashboards.consultant',     compact('user', 'stats')),
            default              => view('dashboards.employe',        compact('user', 'stats')),
        };
    }
}
