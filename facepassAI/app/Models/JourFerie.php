<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Jour férié ou exception de fermeture de l'entreprise (Sprint 4 carte 5, US-042).
 *
 * Utilisé par :
 *   - L'admin pour configurer les exceptions de calendrier
 *   - Le PointageTypeResolver (Sprint 5) pour ne pas attendre de pointage ces jours-là
 *   - Le calcul de présence (Sprint 5+)
 */
class JourFerie extends Model
{
    use HasFactory;

    protected $table = 'jours_feries';

    protected $fillable = [
        'date',
        'libelle',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Indique si une date donnée est un jour férié enregistré.
     */
    public static function isFerie(Carbon|string $date): bool
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);
        return static::whereDate('date', $carbon->toDateString())->exists();
    }

    /**
     * Retourne le libellé d'une date si c'est un jour férié, ou null sinon.
     */
    public static function libelleFor(Carbon|string $date): ?string
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);
        return static::whereDate('date', $carbon->toDateString())
            ->value('libelle');
    }
}
