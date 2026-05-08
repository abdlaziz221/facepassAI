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
    /**
 * Création : User (auth, role=employe) + EmployeProfile (métier).
 * Mot de passe généré aléatoirement → l'employé fera "Mot de passe oublié".
 * Sprint 2 T5 : Upload photo + encodage facial via microservice.
 */
    public function store(StoreEmployeRequest $request): RedirectResponse
    {
        $this->authorize('create', EmployeProfile::class);

        $data = $request->validated();

        // Variables pour la photo et l'encodage
        $photoPath = null;
        $encodage = null;

        DB::transaction(function () use ($data, $request, &$photoPath, &$encodage) {
            // 1. Créer l'utilisateur
            $user = Employe::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => Hash::make(Str::random(20)),
                'est_actif' => true,
            ]);
            $user->assignRole(Role::Employe->value);

            // 2. Gérer la photo et l'encodage (Tâche 5)
            if ($request->hasFile('photo_faciale')) {
                $photo = $request->file('photo_faciale');
                
                // 2a. Stockage local sécurisé
                $photoPath = $photo->store('photos', 'public');
                
                // 2b. Appel au microservice pour l'encodage (Sprint 3)
                try {
                    $faceService = app(\App\Services\FaceRecognitionService::class);
                    $encodage = $faceService->encode($photo);
                    
                    if (!$encodage) {
                        \Illuminate\Support\Facades\Log::warning("Encodage facial non disponible", [
                            'email' => $data['email'],
                            'matricule' => $data['matricule']
                        ]);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Erreur lors de l'appel au microservice", [
                        'error' => $e->getMessage()
                    ]);
                }
            }

        // 3. Créer le profil employé
        EmployeProfile::create([
            'user_id'          => $user->id,
            'matricule'        => $data['matricule'],
            'poste'            => $data['poste'],
            'departement'      => $data['departement'],
            'salaire_brut'     => $data['salaire_brut'],
            'photo_faciale'    => $photoPath,
            'encodage_facial'  => $encodage ? json_encode($encodage) : null,
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
    /**
 * Mise à jour atomique (User + profil) avec gestion photo.
 */
    public function update(UpdateEmployeRequest $request, EmployeProfile $profile): RedirectResponse
    {
        $this->authorize('update', $profile);

        $data = $request->validated();

        DB::transaction(function () use ($data, $request, $profile) {
            // 1. Mettre à jour l'utilisateur
            $profile->user->update([
                'name'  => $data['name'],
                'email' => $data['email'],
            ]);

            // 2. Gérer la photo (si nouvelle photo fournie)
            $photoPath = $profile->photo_faciale;
            $encodage = $profile->encodage_facial;

            if ($request->hasFile('photo_faciale')) {
                // 2a. Supprimer l'ancienne photo si elle existe
                if ($photoPath && \Storage::disk('public')->exists($photoPath)) {
                    \Storage::disk('public')->delete($photoPath);
                }
                
                // 2b. Upload de la nouvelle photo
                $photo = $request->file('photo_faciale');
                $photoPath = $photo->store('photos', 'public');
                
                // 2c. Appel au microservice pour le nouvel encodage
                try {
                    $faceService = app(\App\Services\FaceRecognitionService::class);
                    $encodage = $faceService->encode($photo);
                    
                    if (!$encodage) {
                        \Illuminate\Support\Facades\Log::warning("Encodage facial non disponible lors de la modification", [
                            'email' => $data['email'],
                            'matricule' => $data['matricule']
                        ]);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Erreur microservice lors modification", [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // 3. Mettre à jour le profil employé
            $profile->update([
                'matricule'        => $data['matricule'],
                'poste'            => $data['poste'],
                'departement'      => $data['departement'],
                'salaire_brut'     => $data['salaire_brut'],
                'photo_faciale'    => $photoPath,
                'encodage_facial'  => $encodage ? json_encode($encodage) : null,
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

        // Vérifier si l'employé a des pointages aujourd'hui
        // $pointagesActifs = \App\Models\Pointage::where('employe_id', $profile->id)
        //     ->whereDate('created_at', today())
        //     ->exists();

        // if ($pointagesActifs) {
        //     return redirect()
        //         ->route('employes.index')
        //         ->with('error', "L'employé {$name} a des pointages aujourd'hui. Impossible de le supprimer.");
        // }

        DB::transaction(function () use ($profile) {
            $profile->user->update(['est_actif' => false]);
            $profile->delete();
        });

        return redirect()
            ->route('employes.index')
            ->with('success', "L'employé {$name} a été supprimé (compte désactivé).");
    }
}
