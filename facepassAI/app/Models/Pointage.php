<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pointage extends Model
{
    use HasFactory;

    protected $fillable = [
        'employe_id',
        'jours_travail_id',
        'date_heure',
        'type',
        'statut',
    ];

    protected $casts = [
        'date_heure' => 'datetime',
    ];

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }

    public function joursTravail()
    {
        return $this->belongsTo(JoursTravail::class);
    }
}