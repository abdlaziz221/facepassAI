<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request pour la création d'une demande d'absence par un employé
 * (Sprint 4 Horaires carte 7, US-050).
 *
 * Cohérence garantie :
 *   - date_debut >= aujourd'hui
 *   - date_fin   >= date_debut
 *   - motif obligatoire, entre 5 et 500 caractères
 */
class StoreDemandeAbsenceRequest extends FormRequest
{
    /**
     * L'autorisation est gérée par le middleware de route (role:employe).
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_debut' => ['required', 'date', 'after_or_equal:today'],
            'date_fin'   => ['required', 'date', 'after_or_equal:date_debut'],
            'motif'      => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_debut.required'        => 'La date de début est obligatoire.',
            'date_debut.date'            => 'La date de début doit être une date valide.',
            'date_debut.after_or_equal'  => 'La date de début ne peut pas être dans le passé.',

            'date_fin.required'          => 'La date de fin est obligatoire.',
            'date_fin.date'              => 'La date de fin doit être une date valide.',
            'date_fin.after_or_equal'    => 'La date de fin doit être égale ou postérieure à la date de début.',

            'motif.required' => 'Vous devez préciser un motif pour votre demande.',
            'motif.min'      => 'Le motif doit faire au moins 5 caractères.',
            'motif.max'      => 'Le motif ne doit pas dépasser 500 caractères.',
        ];
    }

    public function attributes(): array
    {
        return [
            'date_debut' => 'date de début',
            'date_fin'   => 'date de fin',
            'motif'      => 'motif',
        ];
    }
}
