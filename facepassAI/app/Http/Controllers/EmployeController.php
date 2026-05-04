<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreEmployeRequest;
use App\Http\Requests\UpdateEmployeRequest;
use App\Models\Employe;
use App\Models\EmployeProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Sprint 2, US-020 : CRUD des employés.
 *
 * Resource controller standard. L'autorisation est faite via
 * $this->authorize(...) au début de chaque méthode (style Laravel 11+,
 * plus explicite que l'ancien authorizeResource()).
 *
 * Création / mise à jour atomiques (User + EmployeProfile) via DB::transaction.
 */
class EmployeController extends Controller
{
    /**
     * Liste paginée des employés (15/page).
     */
    public function index(): View
    {
        $this->authorize('viewAny', EmployeProfile::class);

        $employes = EmployeProfile::with('user')
            ->orderBy('matricule')
            ->paginate(15);

        return view('employes.index', compact('employes'));
    }

    /**
     * Formulaire de création.
     */
    public function create(): View
    {
        $this->authorize('create', EmployeProfile::class);

        return view('employes.create');
    }

    /**
     * Création : User (auth, role=employe) + EmployeProfile (métier).
     * Mot de passe généré aléatoirement → l'employé fera "Mot de passe oublié".
     */
    public function store(StoreEmployeRequest $request): RedirectResponse
    {
        $this->authorize('create', EmployeProfile::class);

        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $user = Employe::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => Hash::make(Str::random(20)),
                'est_actif' => true,
            ]);
            $user->assignRole(Role::Employe->value);

            EmployeProfile::create([
                'user_id'       => $user->id,
                'matricule'     => $data['matricule'],
                'poste'         => $data['poste'],
                'departement'   => $data['departement'],
                'salaire_brut'  => $data['salaire_brut'],
                'photo_faciale' => $data['photo_faciale'] ?? null,
            ]);
        });

        return redirect()
            ->route('employes.index')
            ->with('success', "L'employé {$data['name']} a été créé avec succès. Un email de réinitialisation lui sera envoyé pour définir son mot de passe.");
    }

    /**
     * Profil détaillé d'un employé.
     */
    public function show(EmployeProfile $profile): View
    {
        $this->authorize('view', $profile);

        $profile->load('user');
        return view('employes.show', compact('profile'));
    }

    /**
     * Formulaire de modification.
     */
    public function edit(EmployeProfile $profile): View
    {
        $this->authorize('update', $profile);

        $profile->load('user');
        return view('employes.edit', compact('profile'));
    }

    /**
     * Mise à jour atomique (User + profil).
     */
    public function update(UpdateEmployeRequest $request, EmployeProfile $profile): RedirectResponse
    {
        $this->authorize('update', $profile);

        $data = $request->validated();

        DB::transaction(function () use ($data, $profile) {
            $profile->user->update([
                'name'  => $data['name'],
                'email' => $data['email'],
            ]);

            $profile->update([
                'matricule'     => $data['matricule'],
                'poste'         => $data['poste'],
                'departement'   => $data['departement'],
                'salaire_brut'  => $data['salaire_brut'],
                'photo_faciale' => $data['photo_faciale'] ?? $profile->photo_faciale,
            ]);
        });

        return redirect()
            ->route('employes.show', $profile)
            ->with('success', 'Profil mis à jour avec succès.');
    }

    /**
     * Suppression : désactive le compte + soft delete du profil
     * (Sprint 2 T8 ajoutera SoftDeletes formel + modale de confirmation).
     */
    public function destroy(EmployeProfile $profile): RedirectResponse
    {
        $this->authorize('delete', $profile);

        $name = $profile->user->name;

        DB::transaction(function () use ($profile) {
            $profile->user->update(['est_actif' => false]);
            $profile->delete();
        });

        return redirect()
            ->route('employes.index')
            ->with('success', "L'employé {$name} a été supprimé (compte désactivé).");
    }
}
