<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Employe;
use App\Models\EmployeProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Service de gestion des employés (création / modification / suppression).
 *
 * Encapsule la complexité métier :
 *   - Crée un User (STI Employe) + un EmployeProfile en transaction
 *   - Upload la photo dans storage/app/public/photos
 *   - Appelle le microservice face-service pour calculer l'embedding facial
 *   - Stocke l'embedding dans encodage_facial (colonne json, cast array)
 *   - Envoie un lien de réinitialisation de mot de passe à la création
 *
 * IMPORTANT : le modèle EmployeProfile a le cast `'encodage_facial' => 'array'`.
 * Il faut donc passer un tableau PHP brut (pas du json_encode) ; Laravel
 * gère la sérialisation/désérialisation automatiquement.
 */
class EmployeService
{
    public function __construct(
        private FaceRecognitionService $faceService
    ) {}

    /**
     * Crée un nouvel employé (User + Profil) et tente d'encoder la photo.
     */
    public function createEmploye(array $data, ?UploadedFile $photo): EmployeProfile
    {
        return DB::transaction(function () use ($data, $photo) {
            $password = Str::random(20);

            // 1. Créer l'utilisateur (STI Employe → role=employe)
            $user = Employe::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => Hash::make($password),
                'est_actif' => true,
            ]);
            $user->assignRole(Role::Employe->value);

            // 2. Photo + encodage facial
            $photoPath = null;
            $encodage  = null;
            if ($photo) {
                $photoPath = $photo->store('photos', 'public');
                $encodage  = $this->encodePhoto($photo, $data['email'], $data['matricule']);
            }

            // 3. Créer le profil métier
            $profile = EmployeProfile::create([
                'user_id'         => $user->id,
                'matricule'       => $data['matricule'],
                'poste'           => $data['poste'],
                'departement'     => $data['departement'],
                'salaire_brut'    => $data['salaire_brut'],
                'photo_faciale'   => $photoPath,
                'encodage_facial' => $encodage,  // tableau brut, le cast s'en occupe
            ]);

            // 4. Lien de réinitialisation de mot de passe
            $token = Password::broker()->createToken($user);
            $user->sendPasswordResetNotification($token);

            return $profile;
        });
    }

    /**
     * Met à jour un employé existant (User + Profil).
     * Si une nouvelle photo est fournie, on remplace l'ancienne et on
     * recalcule l'encodage facial.
     */
    public function updateEmploye(EmployeProfile $profile, array $data, ?UploadedFile $photo): EmployeProfile
    {
        return DB::transaction(function () use ($profile, $data, $photo) {
            // 1. Mettre à jour l'utilisateur
            $profile->user->update([
                'name'  => $data['name'],
                'email' => $data['email'],
            ]);

            // 2. Gérer la nouvelle photo si fournie
            $photoPath = $profile->photo_faciale;
            $encodage  = $profile->encodage_facial; // déjà un array grâce au cast

            if ($photo) {
                if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                    Storage::disk('public')->delete($photoPath);
                }
                $photoPath = $photo->store('photos', 'public');

                $newEncodage = $this->encodePhoto($photo, $data['email'], $data['matricule']);
                if ($newEncodage) {
                    $encodage = $newEncodage;
                }
            }

            // 3. Mettre à jour le profil
            $profile->update([
                'matricule'       => $data['matricule'],
                'poste'           => $data['poste'],
                'departement'     => $data['departement'],
                'salaire_brut'    => $data['salaire_brut'],
                'photo_faciale'   => $photoPath,
                'encodage_facial' => $encodage,
            ]);

            return $profile;
        });
    }

    /**
     * Désactive et supprime (soft delete) un employé.
     */
    public function deleteEmploye(EmployeProfile $profile): void
    {
        DB::transaction(function () use ($profile) {
            $profile->user->update(['est_actif' => false]);
            $profile->delete();
        });
    }

    /**
     * Appelle le microservice pour encoder la photo. Retourne le tableau
     * d'embedding (128 floats) ou null si échec (visage non détecté,
     * service indisponible, etc.).
     */
    private function encodePhoto(UploadedFile $photo, string $email, string $matricule): ?array
    {
        try {
            $encodage = $this->faceService->encode($photo);
            if (!$encodage) {
                Log::warning('Encodage facial non disponible', [
                    'email'     => $email,
                    'matricule' => $matricule,
                ]);
            }
            return $encodage;
        } catch (\Throwable $e) {
            Log::error("Erreur microservice lors de l'encodage", [
                'error'     => $e->getMessage(),
                'email'     => $email,
                'matricule' => $matricule,
            ]);
            return null;
        }
    }
}
