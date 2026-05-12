<?php

namespace App\Http\Controllers;

use App\Models\JourFerie;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * CRUD simple des jours fériés et exceptions (Sprint 4 Horaires carte 5, US-042).
 */
class JourFerieController extends Controller
{
    public function index(): View
    {
        return view('admin.jours-feries.index', [
            'feries' => JourFerie::orderBy('date')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date'    => ['required', 'date_format:Y-m-d'],
            'libelle' => ['nullable', 'string', 'max:100'],
        ], [
            'date.required'    => 'La date est obligatoire.',
            'date.date_format' => 'La date doit être au format YYYY-MM-DD.',
        ]);

        // Vérification d'unicité robuste (SQLite stocke date avec heure)
        $dateNormalisee = Carbon::parse($validated['date'])->format('Y-m-d');
        if (JourFerie::whereDate('date', $dateNormalisee)->exists()) {
            return back()
                ->withInput()
                ->withErrors(['date' => 'Cette date est déjà enregistrée comme jour férié.']);
        }

        $jour = JourFerie::create([
            'date'    => $dateNormalisee,
            'libelle' => $validated['libelle'] ?? null,
        ]);

        Log::info('Jour férié ajouté', [
            'admin_id' => $request->user()->id,
            'date'     => $jour->date->toDateString(),
            'libelle'  => $jour->libelle,
        ]);

        return redirect()
            ->route('admin.jours-feries.index')
            ->with('success', "Jour férié du {$jour->date->format('d/m/Y')} ajouté.");
    }

    public function destroy(Request $request, JourFerie $jour): RedirectResponse
    {
        $dateStr = $jour->date->format('d/m/Y');

        Log::info('Jour férié supprimé', [
            'admin_id' => $request->user()->id,
            'date'     => $jour->date->toDateString(),
            'libelle'  => $jour->libelle,
        ]);

        $jour->delete();

        return redirect()
            ->route('admin.jours-feries.index')
            ->with('success', "Jour férié du {$dateStr} supprimé.");
    }
}
