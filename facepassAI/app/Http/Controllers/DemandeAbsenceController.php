<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreDemandeAbsenceRequest;
use App\Models\DemandeAbsence;
use App\Models\EmployeProfile;
use App\Models\User;
use App\Notifications\DemandeAbsenceTraiteeNotification;
use App\Notifications\NouvelleDemandeAbsenceNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Contrôleur des demandes d'absence.
 *
 * Côté employé (Sprint 4 cartes 7 + 9, US-050) :
 *   - create() / store() : dépôt d'une nouvelle demande
 *
 * Côté gestionnaire (Sprint 4 carte 10, US-052) :
 *   - index() : tableau filtrable des demandes en attente
 *   - show() : détail d'une demande
 *   - valider() / refuser() : actions de traitement
 */
class DemandeAbsenceController extends Controller
{
    // ========================================================================
    // Côté employé
    // ========================================================================

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

    // ========================================================================
    // Côté gestionnaire / admin (Sprint 4 carte 10 — US-052)
    // ========================================================================

    /**
     * Liste paginée et filtrable des demandes en attente.
     *
     * Filtres GET supportés :
     *   - employe_id : ne garder qu'un employé
     *   - date       : ne garder que les demandes qui couvrent ce jour
     */
    public function index(Request $request): View
    {
        $query = DemandeAbsence::query()
            ->with(['employe.user', 'gestionnaire'])
            ->where('statut', DemandeAbsence::STATUT_EN_ATTENTE);

        if ($request->filled('employe_id')) {
            $query->where('employe_id', (int) $request->input('employe_id'));
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $query->where('date_debut', '<=', $date)
                  ->where('date_fin', '>=', $date);
        }

        $demandes = $query->orderBy('date_debut')
                          ->paginate(15)
                          ->withQueryString();

        // Liste des employés qui ont au moins une demande en attente (pour le filtre)
        $employeIds = DemandeAbsence::where('statut', DemandeAbsence::STATUT_EN_ATTENTE)
            ->pluck('employe_id')
            ->unique();
        $employes = EmployeProfile::with('user')
            ->whereIn('id', $employeIds)
            ->get()
            ->sortBy(fn ($e) => $e->user->name ?? '')
            ->values();

        return view('demandes-absence.index', compact('demandes', 'employes'));
    }

    /**
     * Détail d'une demande (vue gestionnaire).
     */
    public function show(DemandeAbsence $demande): View
    {
        $demande->load(['employe.user', 'gestionnaire']);

        return view('demandes-absence.show', compact('demande'));
    }

    /**
     * Validation d'une demande.
     */
    public function valider(Request $request, DemandeAbsence $demande): RedirectResponse
    {
        // On ne peut valider qu'une demande en_attente
        if ($demande->statut !== DemandeAbsence::STATUT_EN_ATTENTE) {
            return redirect()->route('demandes-absence.index')
                ->withErrors(['statut' => "Cette demande n'est plus en attente."]);
        }

        $validated = $request->validate([
            'commentaire' => ['nullable', 'string', 'max:500'],
        ]);

        $demande->update([
            'statut'                   => DemandeAbsence::STATUT_VALIDEE,
            'gestionnaire_id'          => $request->user()->id,
            'commentaire_gestionnaire' => $validated['commentaire'] ?? null,
        ]);
        $demande->load(['employe.user', 'gestionnaire']);

        // Sprint 4 carte 11 (US-053) — Notifier l'employé du résultat
        $employeUser = $demande->employe->user ?? null;
        if ($employeUser) {
            Notification::send($employeUser, new DemandeAbsenceTraiteeNotification($demande));
        }

        Log::info('Demande d\'absence validée', [
            'demande_id'        => $demande->id,
            'gestionnaire_id'   => $request->user()->id,
            'employe_id'        => $demande->employe_id,
            'employe_notifie'   => (bool) $employeUser,
        ]);

        return redirect()->route('demandes-absence.index')
            ->with('success', "La demande de {$demande->employe->user->name} a été validée.");
    }

    /**
     * Refus d'une demande (commentaire obligatoire pour justifier).
     */
    public function refuser(Request $request, DemandeAbsence $demande): RedirectResponse
    {
        if ($demande->statut !== DemandeAbsence::STATUT_EN_ATTENTE) {
            return redirect()->route('demandes-absence.index')
                ->withErrors(['statut' => "Cette demande n'est plus en attente."]);
        }

        $validated = $request->validate([
            'commentaire' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'commentaire.required' => "Vous devez justifier le refus.",
            'commentaire.min'      => "La justification doit faire au moins 5 caractères.",
            'commentaire.max'      => "La justification ne doit pas dépasser 500 caractères.",
        ]);

        $demande->update([
            'statut'                   => DemandeAbsence::STATUT_REFUSEE,
            'gestionnaire_id'          => $request->user()->id,
            'commentaire_gestionnaire' => $validated['commentaire'],
        ]);
        $demande->load(['employe.user', 'gestionnaire']);

        // Sprint 4 carte 11 (US-053) — Notifier l'employé du refus
        $employeUser = $demande->employe->user ?? null;
        if ($employeUser) {
            Notification::send($employeUser, new DemandeAbsenceTraiteeNotification($demande));
        }

        Log::info('Demande d\'absence refusée', [
            'demande_id'        => $demande->id,
            'gestionnaire_id'   => $request->user()->id,
            'employe_id'        => $demande->employe_id,
            'motif_refus'       => $validated['commentaire'],
            'employe_notifie'   => (bool) $employeUser,
        ]);

        return redirect()->route('demandes-absence.index')
            ->with('success', "La demande de {$demande->employe->user->name} a été refusée.");
    }
}
