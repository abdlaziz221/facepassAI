<?php

namespace Database\Factories;

use App\Models\JoursTravail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JoursTravail>
 */
class JoursTravailFactory extends Factory
{
    public function definition(): array
    {
        return [
            'jours_ouvrables' => 'Lun,Mar,Mer,Jeu,Ven',
            'heure_arrivee' => '08:00:00',
            'debut_pause' => '12:30:00',
            'fin_pause' => '13:30:00',
            'heure_depart' => '17:00:00',
            'jours_feries' => json_encode([
                '2026-01-01',
                '2026-04-04',
                '2026-05-01',
                '2026-08-15',
                '2026-12-25',
            ]),
        ];
    }
}
