<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateHoraireRequest;
use App\Models\JoursTravail;
use App\Models\Pointage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * Configuration des jours et horaires de travail
 * (Sprint 4 cartes 2 + 3 + 4, US-040/041/043).
 */
class HoraireConfigController extends Controller
{
    public function edit(): View
    {
        return view('admin.horaires.edit', [
            'config'         => JoursTravail::current(),
            'pointagesCount' => Pointage::count(),
        ]);
    }

    public function update(UpdateHoraireRequest $request): RedirectResponse
    {
        $validated      = $request->validated();
        $pointagesCount = Pointage::count();

        $config = JoursTravail::current();
        $oldValues = $config->only([
            'jours_ouvrables', 'heure_arrivee', 'heure_debut_pause',
            'heure_fin_pause', 'heure_depart', 'jours_feries',
        ]);

        $config->update([
            'jours_ouvrables'   => $validated['jours_ouvrables'],
            'heure_arrivee'     => $validated['heure_arrivee'],
            'heure_debut_pause' => $validated['heure_debut_pause'],
            'heure_fin_pause'   => $validated['heure_fin_pause'],
            'heure_depart'      => $validated['heure_depart'],
            'jours_feries'      => array_values(array_filter($validated['jours_feries'] ?? [])),
        ]);

        Log::info('Configuration des horaires mise à jour', [
            'admin_id'                 => $request->user()->id,
            'pointages_preexistants'   => $pointagesCount,
            'anciennes_valeurs'        => $oldValues,
            'nouvelles_valeurs'        => $config->fresh()->only(array_keys($oldValues)),
        ]);

        $successMessage = 'Configuration des horaires enregistrée avec succès.';
        if ($pointagesCount > 0) {
            $successMessage .= " ({$pointagesCount} pointage(s) existaient déjà — restent inchangés.)";
        }

        return redirect()
            ->route('admin.horaires.edit')
            ->with('success', $successMessage);
    }
}
