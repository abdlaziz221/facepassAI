<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Sprint 5 carte 10 (US-072) — Validation du formulaire de génération de rapport.
 */
class GenererRapportRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Permission contrôlée par le middleware can:rapports.export sur la route.
        return true;
    }

    public function rules(): array
    {
        return [
            'date_debut' => ['required', 'date'],
            'date_fin'   => ['required', 'date', 'after_or_equal:date_debut'],
            'type'       => ['required', 'in:presences'],
            'format'     => ['required', 'in:pdf,excel'],
            'employe_id' => ['nullable', 'integer', 'exists:employes,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_debut.required'     => 'La date de début est obligatoire.',
            'date_debut.date'         => 'La date de début doit être une date valide.',
            'date_fin.required'       => 'La date de fin est obligatoire.',
            'date_fin.date'           => 'La date de fin doit être une date valide.',
            'date_fin.after_or_equal' => 'La date de fin doit être après ou égale à la date de début.',
            'type.required'           => 'Choisissez un type de rapport.',
            'type.in'                 => 'Type de rapport non supporté.',
            'format.required'         => 'Choisissez un format (PDF ou Excel).',
            'format.in'               => 'Format non supporté. Choisissez PDF ou Excel.',
            'employe_id.exists'       => 'Cet employé n\'existe pas.',
        ];
    }

    public function attributes(): array
    {
        return [
            'date_debut' => 'date de début',
            'date_fin'   => 'date de fin',
            'employe_id' => 'employé',
        ];
    }
}
