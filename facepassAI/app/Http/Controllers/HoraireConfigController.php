<?php

namespace App\Http\Controllers;

use App\Models\HoraireConfig;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Configuration globale des horaires de l'entreprise (Sprint 4 US-040).
 *
 * Routes (admin uniquement) :
 *   - GET  /admin/horaires → formulaire d'édition
 *   - PUT  /admin/horaires → enregistre la configuration
 */
class HoraireConfigController extends Controller
{
    public function edit(): View
    {
        return view('admin.horaires.edit', [
            'config' => HoraireConfig::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'jours_ouvrables'     => ['required', 'array', 'min:1'],
            'jours_ouvrables.*'   => ['string', Rule::in(HoraireConfig::JOURS_VALIDES)],
            'heure_arrivee'       => ['required', 'date_format:H:i'],
            'heure_debut_pause'   => ['required', 'date_format:H:i', 'after:heure_arrivee'],
            'heure_fin_pause'     => ['required', 'date_format:H:i', 'after:heure_debut_pause'],
            'heure_depart'        => ['required', 'date_format:H:i', 'after:heure_fin_pause'],
            'jours_feries'        => ['nullable', 'array'],
            'jours_feries.*'      => ['nullable', 'date_format:Y-m-d'],
        ], [
            'jours_ouvrables.min'  => 'Sélectionnez au moins un jour ouvrable.',
            'heure_debut_pause.after' => "L'heure de début de pause doit être après l'arrivée.",
            'heure_fin_pause.after'   => "L'heure de fin de pause doit être après le début.",
            'heure_depart.after'      => "L'heure de départ doit être après la fin de pause.",
        ]);

        $config = HoraireConfig::current();
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
