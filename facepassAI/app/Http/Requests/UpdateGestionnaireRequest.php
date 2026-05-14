<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Sprint 6 carte 5 (US-090) — Modification d'un compte gestionnaire.
 *
 * L'email reste unique mais on doit pouvoir le ré-utiliser pour l'enregistrement
 * en cours (donc on exclut l'ID via Rule::unique()->ignore()).
 */
class UpdateGestionnaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $gestionnaireId = $this->route('gestionnaire')?->id;

        return [
            'name'  => ['required', 'string', 'min:2', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:191',
                Rule::unique('users', 'email')->ignore($gestionnaireId),
            ],
            'est_actif' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'Le nom du gestionnaire est obligatoire.',
            'email.required' => "L'adresse email est obligatoire.",
            'email.email'    => "L'adresse email n'est pas valide.",
            'email.unique'   => 'Cette adresse email est déjà utilisée par un autre compte.',
        ];
    }
}
