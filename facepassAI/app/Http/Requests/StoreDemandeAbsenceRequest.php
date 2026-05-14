<?php

namespace App\Http\Requests;

use App\Models\DemandeAbsence;
use App\Models\EmployeProfile;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request pour la création d'une demande d'absence par un employé
 * (Sprint 4 Horaires cartes 7 + 8, US-050/051).
 *
 * Validation :
 *   - date_debut >= aujourd'hui
 *   - date_fin   >= date_debut
 *   - motif obligatoire, entre 5 et 500 caractères
 *   - Aucune demande en_attente ou validee ne chevauche la période (US-051)
 */
class StoreDemandeAbsenceRequest extends FormRequest
{
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

    /**
     * Validation supplémentaire après les règles standard :
     * détection de chevauchement avec une autre demande de l'employé.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Si déjà des erreurs sur les dates, inutile de vérifier le chevauchement
            if ($validator->errors()->has('date_debut')
                || $validator->errors()->has('date_fin')) {
                return;
            }

            $user = $this->user();
            if (!$user) {
                return;
            }

            $profile = EmployeProfile::where('user_id', $user->id)->first();
            if (!$profile) {
                return; // pas de profil → l'erreur sera levée par le controller
            }

            $debut = $this->input('date_debut');
            $fin   = $this->input('date_fin');

            if (DemandeAbsence::hasOverlap($profile->id, $debut, $fin)) {
                $overlaps = DemandeAbsence::findOverlaps($profile->id, $debut, $fin);
                $first    = $overlaps->first();
                $message  = sprintf(
                    "Vous avez déjà une demande %s pour la période du %s au %s qui chevauche celle-ci. Veuillez choisir d'autres dates.",
                    $first->statut === DemandeAbsence::STATUT_VALIDEE ? 'validée' : 'en attente',
                    $first->date_debut->format('d/m/Y'),
                    $first->date_fin->format('d/m/Y')
                );
                $validator->errors()->add('date_debut', $message);
            }
        });
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
