<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreDemandeAbsenceRequest;
use App\Models\DemandeAbsence;
use App\Models\EmployeProfile;
use App\Models\User;
use App\Notifications\NouvelleDemandeAbsenceNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Contrôleur des demandes d'absence côté employé
 * (Sprint 4 cartes 7 + 9, US-050).
 */
class DemandeAbsenceController extends Controller
{
    public function create(): View
    {
        return view('demandes-absence.create');
    }

    public function store(StoreDemandeAbsenceRequest $request): RedirectResponse
    {
        $user = $request->user();

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

        // Sprint 4 carte 9 — Notifier tous les gestionnaires
        // (on utilise la colonne STI 'role' directement, plus fiable que spatie scope)
        $gestionnaires = User::where('role', Role::Gestionnaire->value)->get();
        if ($gestionnaires->isNotEmpty()) {
            Notification::send($gestionnaires, new NouvelleDemandeAbsenceNotification($demande));
        }

        Log::info('Demande d\'absence créée', [
            'demande_id'              => $demande->id,
            'employe_id'              => $profile->id,
            'user_id'                 => $user->id,
            'gestionnaires_notifies'  => $gestionnaires->count(),
            'date_debut'              => $demande->date_debut->toDateString(),
            'date_fin'                => $demande->date_fin->toDateString(),
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', "Votre demande d'absence du "
                . $demande->date_debut->format('d/m/Y')
                . " au " . $demande->date_fin->format('d/m/Y')
                . " a bien été enregistrée et est en attente de validation.");
    }
}
