<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Configuration des jours et horaires de travail (Sprint 4 carte 1, US-040).
 *
 * Table singleton : une seule ligne dans la base, accessible via
 * JoursTravail::current() qui retourne (et crée si besoin) l'instance.
 *
 * Note : les jours fériés sont dans une table séparée (JourFerie, US-042).
 */
class JoursTravail extends Model
{
    protected $table = 'jours_travail';

    public const JOURS_VALIDES = [
        'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche',
    ];

    protected $fillable = [
        'jours_ouvrables',
        'heure_arrivee',
        'heure_debut_pause',
        'heure_fin_pause',
        'heure_depart',
    ];

    protected $casts = [
        'jours_ouvrables' => 'array',
    ];

    /**
     * Retourne la configuration actuelle (singleton, créée si elle n'existe pas).
     *
     * Defaults conformes au cahier des charges : lundi-vendredi, 8h-17h.
     */
    public static function current(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'jours_ouvrables'   => ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi'],
                'heure_arrivee'     => '08:00',
                'heure_debut_pause' => '12:00',
                'heure_fin_pause'   => '13:00',
                'heure_depart'      => '17:00',
            ]
        );
    }
}
