<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Configuration globale des horaires de l'entreprise (Sprint 4 US-040).
 *
 * Table singleton : une seule ligne dans la base, accessible via
 * HoraireConfig::current() qui retourne (et crée si besoin) l'instance.
 *
 * Utilisée par :
 *   - L'admin pour configurer les horaires de référence
 *   - Le PointageTypeResolver (Sprint 5) pour valider les plages horaires
 *   - Le calcul des retards et départs anticipés (Sprint 5)
 */
class HoraireConfig extends Model
{
    /** Jours de la semaine valides (snake_case, FR). */
    public const JOURS_VALIDES = [
        'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche',
    ];

    protected $fillable = [
        'jours_ouvrables',
        'heure_arrivee',
        'heure_debut_pause',
        'heure_fin_pause',
        'heure_depart',
        'jours_feries',
    ];

    protected $casts = [
        'jours_ouvrables' => 'array',
        'jours_feries'    => 'array',
    ];

    /**
     * Retourne la configuration actuelle (singleton, créée si elle n'existe pas).
     */
    public static function current(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'jours_ouvrables'   => ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi'],
                'heure_arrivee'     => '09:00',
                'heure_debut_pause' => '12:00',
                'heure_fin_pause'   => '13:00',
                'heure_depart'      => '18:00',
                'jours_feries'      => [],
            ]
        );
    }
}
