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

    /**
     * Sprint 5 carte 5 (US-063) — La configuration a-t-elle été modifiée par l'admin ?
     *
     * On considère qu'elle est configurée si elle a été modifiée après sa
     * création initiale (updated_at significativement après created_at).
     * Sinon on est encore sur les defaults auto-créés par current().
     */
    public function isConfigured(): bool
    {
        if (!$this->created_at || !$this->updated_at) {
            return false;
        }
        return abs($this->updated_at->diffInSeconds($this->created_at)) > 2;
    }

    /** Raccourci : la config singleton actuelle est-elle configurée ? */
    public static function isCurrentConfigured(): bool
    {
        return static::current()->isConfigured();
    }
}
