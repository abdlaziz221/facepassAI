<?php

namespace Database\Seeders;

use App\Models\Employe;
use App\Models\Pointage;
use Illuminate\Database\Seeder;

class PointageSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer tous les employés
        $employes = Employe::all();

        // Pour chaque employé, créer des pointages
        foreach ($employes as $employe) {
            // Entre 10 et 50 pointages par employé
            $nbPointages = rand(10, 50);
            
            Pointage::factory()
                ->count($nbPointages)
                ->create([
                    'employe_id' => $employe->id
                ]);
        }

        // Pointages spécifiques pour l'employé démo (EMP-001)
        $employeDemo = Employe::where('matricule', 'EMP-001')->first();
        
        if ($employeDemo) {
            $today = now()->startOfDay();
            
            $pointagesDemo = [
                ['type' => 'arrivee', 'statut' => 'valide', 'heure' => $today->copy()->setHour(8)->setMinute(5)],
                ['type' => 'debut_pause', 'statut' => 'valide', 'heure' => $today->copy()->setHour(12)->setMinute(30)],
                ['type' => 'fin_pause', 'statut' => 'valide', 'heure' => $today->copy()->setHour(13)->setMinute(30)],
                ['type' => 'depart', 'statut' => 'valide', 'heure' => $today->copy()->setHour(17)->setMinute(15)],
            ];
            
            foreach ($pointagesDemo as $pointage) {
                Pointage::create([
                    'employe_id' => $employeDemo->id,
                    'type' => $pointage['type'],
                    'statut' => $pointage['statut'],
                    'date_heure' => $pointage['heure'],
                ]);
            }
        }

        // Pointage en retard pour un employé spécifique
        $employeRetard = Employe::where('matricule', 'EMP-002')->first();
        if ($employeRetard) {
            $today = now()->startOfDay();
            Pointage::create([
                'employe_id' => $employeRetard->id,
                'type' => 'arrivee',
                'statut' => 'en_retard',
                'date_heure' => $today->copy()->setHour(9)->setMinute(45),
            ]);
        }

        $this->command->info('Pointages seeded: Pointages créés pour tous les employés');
    }
}