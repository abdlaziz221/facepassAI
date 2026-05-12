<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDemandeAbsenceRequest;
use App\Models\DemandeAbsence;
use App\Models\EmployeProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * Contrôleur des demandes d'absence côté employé (Sprint 4 carte 7, US-050).
 *
 * Routes (employé authentifié) :
 *   - GET  /demandes-absence/create → formulaire de création
 *   - POST /demandes-absence        → enregistre la demande
 */
class DemandeAbsenceController extends Controller
{
    /**
     * Affiche le formulaire de création d'une demande.
     */
    public function create(): View
    {
        return view('demandes-absence.create');
    }

    /**
     * Enregistre une nouvelle demande d'absence pour l'employé connecté.
     */
    public function store(StoreDemandeAbsenceRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Récupération du profil métier de l'employé connecté
        $profile = EmployeProfile::where('user_id', $user->id)->first();
        if (!$profile) {
            return redirect()
                ->route('dashboard')
                ->withErrors([
                    'profile' => "Aucun profil employé associé à votre compte. Contactez un administrateur.",
                ]);
        }

        $validated = $request->validated();

        $demande = DemandeAbsence::create([
            'employe_id'              => $profile->id,
            'gestionnaire_id'         => null,
            'date_debut'              => $validated['date_debut'],
            'date_fin'                => $validated['date_fin'],
            'motif'                   => $validated['motif'],
            'statut'                  => DemandeAbsence::STATUT_EN_ATTENTE,
            'commentaire_gestionnaire' => null,
        ]);

        Log::info('Demande d\'absence créée', [
            'demande_id' => $demande->id,
            'employe_id' => $profile->id,
            'user_id'    => $user->id,
            'date_debut' => $demande->date_debut->toDateString(),
            'date_fin'   => $demande->date_fin->toDateString(),
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', "Votre demande d'absence du "
                . $demande->date_debut->format('d/m/Y')
                . " au " . $demande->date_fin->format('d/m/Y')
                . " a bien été enregistrée et est en attente de validation.");
    }
}
