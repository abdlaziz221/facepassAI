<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sprint 5 carte 7 (US-071) — Export Excel de test.
 *
 * Sert de modèle pour les futurs exports (retards, paie, pointages, etc.).
 * Démontre : entêtes, données, titre d'onglet, mise en forme de l'entête,
 * largeur auto des colonnes.
 */
class TestExport implements FromArray, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        return [
            ['Aïssatou Sow',  'arrivee', '08:00', 'À l\'heure'],
            ['Mamadou Diop',  'arrivee', '08:15', 'Retard'],
            ['Fatou Ndiaye',  'depart',  '17:00', 'À l\'heure'],
            ['Khady Camara',  'depart',  '16:30', 'Départ anticipé'],
            ['Ibrahima Fall', 'arrivee', '07:50', 'À l\'heure'],
        ];
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['Employé', 'Type', 'Heure', 'Statut'];
    }

    public function title(): string
    {
        return 'Test FacePass.AI';
    }

    /** @return array<int, array<string, mixed>> */
    public function styles(Worksheet $sheet): array
    {
        return [
            // Ligne d'entêtes : fond indigo + texte blanc gras
            1 => [
                'font' => [
                    'bold'  => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size'  => 11,
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '6366F1'],
                ],
            ],
        ];
    }
}
