<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Sprint 2, US-021 : règles de validation pour la modification d'un employé.
 * Les unicités email/matricule ignorent l'enregistrement courant.
 */
class UpdateEmployeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // L'autorisation est faite par EmployeProfilePolicy
    }

    public function rules(): array
    {
        /** @var \App\Models\EmployeProfile $profile */
        $profile = $this->route('profile');
        $userId  = $profile->user_id;

        return [
            'name'  => ['required', 'string', 'max:120'],
            'email' => [
                'required', 'string', 'email', 'max:120',
                Rule::unique('users', 'email')->ignore($userId),
            ],

            'matricule' => [
                'required', 'string', 'max:20',
                Rule::unique('employes', 'matricule')->ignore($profile->id),
            ],
            'poste'        => ['required', 'string', 'max:100'],
            'departement'  => ['required', 'string', 'max:100'],
            'salaire_brut' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'photo_faciale' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'     => 'Cette adresse email est déjà utilisée par un autre compte.',
            'matricule.unique' => 'Ce matricule est déjà attribué à un autre employé.',
            'salaire_brut.min' => 'Le salaire brut ne peut pas être négatif.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'         => 'nom complet',
            'email'        => 'adresse email',
            'matricule'    => 'matricule',
            'poste'        => 'poste',
            'departement'  => 'département',
            'salaire_brut' => 'salaire brut',
        ];
    }
}
