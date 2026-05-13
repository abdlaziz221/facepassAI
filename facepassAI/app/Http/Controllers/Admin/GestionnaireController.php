<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGestionnaireRequest;
use App\Http\Requests\UpdateGestionnaireRequest;
use App\Models\DemandeAbsence;
use App\Models\Gestionnaire;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Sprint 6 carte 5 (US-090) — CRUD des comptes gestionnaires (admin uniquement).
 *
 * Routes (toutes sous /admin/gestionnaires) :
 *   GET    /            → index : liste paginée
 *   GET    /create      → formulaire de création
 *   POST   /            → création + mot de passe temporaire flashé
 *   GET    /{g}/edit    → formulaire d'édition
 *   PUT    /{g}         → mise à jour
 *   DELETE /{g}         → suppression (avec garde demandes en attente)
 */
class GestionnaireController extends Controller
{
    public function index(): View
    {
        $gestionnaires = Gestionnaire::orderBy('name')->paginate(20);
        $pendingDemandes = DemandeAbsence::where('statut', DemandeAbsence::STATUT_EN_ATTENTE)->count();

        return view('admin.gestionnaires.index', compact('gestionnaires', 'pendingDemandes'));
    }

    public function create(): View
    {
        return view('admin.gestionnaires.create');
    }

    public function store(StoreGestionnaireRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $tempPassword = Str::password(12, true, true, false, false);

        $gestionnaire = Gestionnaire::create([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'password'          => Hash::make($tempPassword),
            'role'              => Role::Gestionnaire->value,
            'est_actif'         => true,
            'email_verified_at' => now(),
        ]);

        $gestionnaire->assignRole(Role::Gestionnaire->value);

        Log::info('Gestionnaire créé par admin', [
            'gestionnaire_id'    => $gestionnaire->id,
            'gestionnaire_email' => $gestionnaire->email,
            'admin_id'           => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.gestionnaires.index')
            ->with('success', "Compte gestionnaire pour {$gestionnaire->name} créé.")
            ->with('temp_password', $tempPassword)
            ->with('temp_password_for', $gestionnaire->name . ' (' . $gestionnaire->email . ')');
    }

    public function edit(Gestionnaire $gestionnaire): View
    {
        return view('admin.gestionnaires.edit', compact('gestionnaire'));
    }

    public function update(UpdateGestionnaireRequest $request, Gestionnaire $gestionnaire): RedirectResponse
    {
        $validated = $request->validated();

        $gestionnaire->update([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'est_actif' => (bool) ($validated['est_actif'] ?? false),
        ]);

        Log::info('Gestionnaire mis à jour par admin', [
            'gestionnaire_id' => $gestionnaire->id,
            'admin_id'        => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.gestionnaires.index')
            ->with('success', "Compte de {$gestionnaire->name} mis à jour.");
    }

    public function destroy(Gestionnaire $gestionnaire): RedirectResponse
    {
        // Sprint 6 carte 5 — Vérif demandes en cours avant suppression
        $pendingCount = DemandeAbsence::where('statut', DemandeAbsence::STATUT_EN_ATTENTE)->count();
        $remainingGestionnaires = Gestionnaire::where('id', '!=', $gestionnaire->id)->count();

        if ($pendingCount > 0 && $remainingGestionnaires === 0) {
            return redirect()
                ->route('admin.gestionnaires.index')
                ->withErrors([
                    'delete' => "Impossible de supprimer le dernier gestionnaire tant qu'il reste "
                              . "{$pendingCount} demande(s) d'absence en attente. "
                              . "Traitez-les d'abord ou créez un autre gestionnaire.",
                ]);
        }

        $name = $gestionnaire->name;
        $gestionnaire->delete();

        Log::info('Gestionnaire supprimé par admin', [
            'gestionnaire_id'   => $gestionnaire->id,
            'gestionnaire_name' => $name,
            'admin_id'          => request()->user()->id,
        ]);

        return redirect()
            ->route('admin.gestionnaires.index')
            ->with('success', "Compte de {$name} supprimé.");
    }
}
