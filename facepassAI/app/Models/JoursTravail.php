<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JoursTravail extends Model
{
    /** @use HasFactory<\Database\Factories\JoursTravailFactory> */
    use HasFactory;

    protected $table = 'jours_travail';

    protected $fillable = [
        'jours_ouvrables',
        'heure_arrivee',
        'debut_pause',
        'fin_pause',
        'heure_depart',
        'jours_feries',
    ];
}
