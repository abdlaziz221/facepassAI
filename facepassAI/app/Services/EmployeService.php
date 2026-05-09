<?php

namespace App\Services;

use App\Models\Employe;
use App\Models\EmployeProfile;
use App\Enums\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

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
            
            // 1. Créer l'utilisateur
            $user = Employe::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => Hash::make($password),
                'est_actif' => true,
            ]);
            
            $user->assignRole(Role::Employe->value);

            // 2. Gérer la photo et l'encodage
            $photoPath = null;
            $encodage = null;

            if ($photo) {
                $photoPath = $photo->store('photos', 'public');
                $encodage = $this->encodePhoto($photo, $data['email'], $data['matricule']);
            }

            // 3. Créer le profil
            $profile = EmployeProfile::create([
                'user_id'          => $user->id,
                'matricule'        => $data['matricule'],
                'poste'            => $data['poste'],
                'departement'      => $data['departement'],
                'salaire_brut'     => $data['salaire_brut'],
                'photo_faciale'    => $photoPath,
                'encodage_facial'  => $encodage ? json_encode($encodage) : null,
            ]);

            // 4. Envoi du lien de réinitialisation de mot de passe
            $token = Password::broker()->createToken($user);
            $user->sendPasswordResetNotification($token);

            return $profile;
        });
    }

    /**
     * Met à jour un employé existant (User + Profil).
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
            $encodage = $profile->encodage_facial;

            if ($photo) {
                if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                    Storage::disk('public')->delete($photoPath);
                }
                
                $photoPath = $photo->store('photos', 'public');
                $newEncodage = $this->encodePhoto($photo, $data['email'], $data['matricule']);
                
                if ($newEncodage) {
                    $encodage = json_encode($newEncodage);
                }
            }

            // 3. Mettre à jour le profil
            $profile->update([
                'matricule'        => $data['matricule'],
                'poste'            => $data['poste'],
                'departement'      => $data['departement'],
                'salaire_brut'     => $data['salaire_brut'],
                'photo_faciale'    => $photoPath,
                'encodage_facial'  => $encodage,
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
     * Appelle le service externe pour encoder la photo faciale.
     */
    private function encodePhoto(UploadedFile $photo, string $email, string $matricule): ?array
    {
        try {
            $encodage = $this->faceService->encode($photo);
            
            if (!$encodage) {
                Log::warning("Encodage facial non disponible", [
                    'email' => $email,
                    'matricule' => $matricule
                ]);
            }
            return $encodage;
        } catch (\Exception $e) {
            Log::error("Erreur microservice lors de l'encodage", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
