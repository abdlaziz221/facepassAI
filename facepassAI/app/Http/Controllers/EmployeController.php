<?php

namespace App\Http\Controllers;

use App\Models\EmployeProfile;
use App\Services\EmployeService;
use App\Http\Requests\StoreEmployeRequest;
use App\Http\Requests\UpdateEmployeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeController extends Controller
{
    public function __construct(
        private EmployeService $employeService
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', EmployeProfile::class);

        $query = EmployeProfile::with('user');

        if ($request->filled('departement')) {
            $query->where('departement', $request->departement);
        }

        if ($request->filled('statut')) {
            $statut = $request->statut === 'actif' ? 1 : 0;
            $query->whereHas('user', function ($q) use ($statut) {
                $q->where('est_actif', $statut);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('matricule', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($qu) use ($search) {
                      $qu->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $employes = $query->orderBy('matricule')->paginate(15)->withQueryString();
        $departements = EmployeProfile::select('departement')->distinct()->pluck('departement');

        return view('employes.index', compact('employes', 'departements'));
    }

    public function create(): View
    {
        $this->authorize('create', EmployeProfile::class);

        return view('employes.create');
    }

    public function store(StoreEmployeRequest $request): RedirectResponse
    {
        $this->authorize('create', EmployeProfile::class);

        $profile = $this->employeService->createEmploye(
            $request->validated(),
            $request->file('photo_faciale')
        );

        return redirect()
            ->route('employes.index')
            ->with('success', "L'employé {$profile->user->name} a été créé avec succès. Un email de réinitialisation lui sera envoyé.");
    }

    public function show(EmployeProfile $profile): View
    {
        $this->authorize('view', $profile);

        $profile->load('user');
        return view('employes.show', compact('profile'));
    }

    public function edit(EmployeProfile $profile): View
    {
        $this->authorize('update', $profile);

        $profile->load('user');
        return view('employes.edit', compact('profile'));
    }

    public function update(UpdateEmployeRequest $request, EmployeProfile $profile): RedirectResponse
    {
        $this->authorize('update', $profile);

        $this->employeService->updateEmploye(
            $profile,
            $request->validated(),
            $request->file('photo_faciale')
        );

        return redirect()
            ->route('employes.show', $profile)
            ->with('success', 'Profil mis à jour avec succès.');
    }

    public function destroy(EmployeProfile $profile): RedirectResponse
    {
        $this->authorize('delete', $profile);

        $name = $profile->user->name;

        // Tâche 8 : Vérification des pointages actifs
        $pointagesActifs = \App\Models\Pointage::where('employe_id', $profile->id)
            ->whereDate('created_at', today())
            ->exists();

        if ($pointagesActifs) {
            return redirect()
                ->route('employes.index')
                ->with('error', "L'employé {$name} a des pointages aujourd'hui. Impossible de le supprimer.");
        }

        $this->employeService->deleteEmploye($profile);

        return redirect()
            ->route('employes.index')
            ->with('success', "L'employé {$name} a été supprimé (compte désactivé).");
    }
}
