<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Sprint 6 carte 5 (US-090) — Création d'un compte gestionnaire par l'admin.
 */
class StoreGestionnaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Contrôlé par le middleware can:gestionnaires.manage sur la route.
        return true;
    }

    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'min:2', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:191',
                Rule::unique('users', 'email'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'Le nom du gestionnaire est obligatoire.',
            'name.min'       => 'Le nom doit faire au moins 2 caractères.',
            'email.required' => "L'adresse email est obligatoire.",
            'email.email'    => "L'adresse email n'est pas valide.",
            'email.unique'   => 'Cette adresse email est déjà utilisée par un autre compte.',
        ];
    }
}
