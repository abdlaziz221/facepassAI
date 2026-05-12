<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateHoraireRequest;
use App\Models\JoursTravail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * Configuration des jours et horaires de travail (Sprint 4 carte 2 + 3, US-040/041).
 *
 * Routes (admin uniquement) :
 *   - GET  /admin/horaires → formulaire d'édition
 *   - PUT  /admin/horaires → enregistre la configuration
 *
 * Le terme UI est "horaires" (URL, view, route name), le terme métier
 * est "jours_travail" (table et modèle).
 *
 * La validation est factorisée dans UpdateHoraireRequest (carte 3).
 */
class HoraireConfigController extends Controller
{
    public function edit(): View
    {
        return view('admin.horaires.edit', [
            'config' => JoursTravail::current(),
        ]);
    }

    public function update(UpdateHoraireRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $config = JoursTravail::current();
        $config->update([
            'jours_ouvrables'   => $validated['jours_ouvrables'],
            'heure_arrivee'     => $validated['heure_arrivee'],
            'heure_debut_pause' => $validated['heure_debut_pause'],
            'heure_fin_pause'   => $validated['heure_fin_pause'],
            'heure_depart'      => $validated['heure_depart'],
            'jours_feries'      => array_values(array_filter($validated['jours_feries'] ?? [])),
        ]);

        Log::info('Configuration des horaires mise à jour', [
            'admin_id' => $request->user()->id,
            'config'   => $config->only(array_keys($validated)),
        ]);

        return redirect()
            ->route('admin.horaires.edit')
            ->with('success', 'Configuration des horaires enregistrée avec succès.');
    }
}
