<?php

namespace Database\Seeders;

use App\Models\JoursTravail;
use Illuminate\Database\Seeder;

/**
 * Seeder de la configuration par défaut des horaires :
 * Lundi à vendredi, 8h-17h.
 *
 * Idempotent : appelable plusieurs fois sans dupliquer.
 *
 * Sprint 4 Horaires carte 1 (US-040).
 */
class JoursTravailSeeder extends Seeder
{
    public function run(): void
    {
        JoursTravail::updateOrCreate(
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
