<?php

namespace App\Http\Requests;

use App\Models\JoursTravail;
use App\Models\Pointage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request pour la mise à jour de la configuration des horaires.
 *
 * Notes :
 *   - Les jours fériés sont gérés séparément via JourFerieController (carte 5)
 *   - L'admin doit confirmer si des pointages existent déjà (carte 4)
 *   - Cohérence chronologique des heures (carte 3)
 */
class UpdateHoraireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $pointagesCount  = Pointage::count();
        $confirmRequired = $pointagesCount > 0;

        return [
            'jours_ouvrables'     => ['required', 'array', 'min:1'],
            'jours_ouvrables.*'   => ['string', Rule::in(JoursTravail::JOURS_VALIDES)],

            'heure_arrivee'       => ['required', 'date_format:H:i'],
            'heure_debut_pause'   => ['required', 'date_format:H:i', 'after:heure_arrivee'],
            'heure_fin_pause'     => ['required', 'date_format:H:i', 'after:heure_debut_pause'],
            'heure_depart'        => ['required', 'date_format:H:i', 'after:heure_fin_pause'],

            'confirm' => $confirmRequired
                ? ['required', 'accepted']
                : ['nullable'],
        ];
    }

    public function messages(): array
    {
        $count = Pointage::count();

        return [
            'jours_ouvrables.required' => 'Vous devez sélectionner au moins un jour ouvrable.',
            'jours_ouvrables.array'    => 'Le format des jours ouvrables est invalide.',
            'jours_ouvrables.min'      => 'Sélectionnez au moins un jour ouvrable.',
            'jours_ouvrables.*.in'     => "Le jour « :input » n'est pas un jour de la semaine valide.",

            'heure_arrivee.required'    => "L'heure d'arrivée est obligatoire.",
            'heure_arrivee.date_format' => "L'heure d'arrivée doit être au format HH:MM (exemple : 08:30).",

            'heure_debut_pause.required'    => "L'heure de début de pause est obligatoire.",
            'heure_debut_pause.date_format' => "L'heure de début de pause doit être au format HH:MM (exemple : 12:00).",
            'heure_debut_pause.after'       => "L'heure de début de pause doit être strictement après l'heure d'arrivée.",

            'heure_fin_pause.required'    => "L'heure de fin de pause est obligatoire.",
            'heure_fin_pause.date_format' => "L'heure de fin de pause doit être au format HH:MM (exemple : 13:00).",
            'heure_fin_pause.after'       => "L'heure de fin de pause doit être strictement après l'heure de début de pause.",

            'heure_depart.required'    => "L'heure de départ est obligatoire.",
            'heure_depart.date_format' => "L'heure de départ doit être au format HH:MM (exemple : 17:00).",
            'heure_depart.after'       => "L'heure de départ doit être strictement après l'heure de fin de pause.",

            'confirm.required' => "Attention : {$count} pointage(s) existent déjà avec les anciens horaires. Cochez « Je confirme » pour continuer.",
            'confirm.accepted' => "Vous devez cocher la confirmation pour appliquer cette modification.",
        ];
    }

    public function attributes(): array
    {
        return [
            'jours_ouvrables'   => 'jours ouvrables',
            'heure_arrivee'     => "heure d'arrivée",
            'heure_debut_pause' => 'heure de début de pause',
            'heure_fin_pause'   => 'heure de fin de pause',
            'heure_depart'      => 'heure de départ',
            'confirm'           => 'confirmation',
        ];
    }
}
