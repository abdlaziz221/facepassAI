<?php

namespace App\Exports;

use App\Services\RetardService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sprint 5 carte 9 (US-071) — Export Excel du rapport de présences.
 *
 * Colonnes : Date | Employé | Matricule | Type | Heure | Heure théorique
 *          | Écart (min) | Statut
 *
 * Mise en forme :
 *   - Header bold blanc sur fond indigo (#6366F1)
 *   - Auto-size sur toutes les colonnes
 *   - Filtres automatiques sur la ligne d'entête
 */
class RapportPresenceExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize, WithEvents
{
    public function __construct(
        public readonly Collection $pointages,
        public readonly RetardService $retardService,
        public readonly array $context = []
    ) {
    }

    public function collection(): Collection
    {
        return $this->pointages;
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return [
            'Date',
            'Employé',
            'Matricule',
            'Type',
            'Heure',
            'Heure théorique',
            'Écart (min)',
            'Statut',
        ];
    }

    /** @return array<int, mixed> */
    public function map($pointage): array
    {
        $a = $this->retardService->analyserPointage($pointage);

        $statut = 'À l\'heure';
        if ($a['is_retard']) {
            $statut = 'Retard';
        } elseif ($a['is_depart_anticipe']) {
            $statut = 'Départ anticipé';
        }

        return [
            $pointage->created_at->format('d/m/Y'),
            $pointage->employe->user->name ?? ('#' . $pointage->employe_id),
            $pointage->employe->matricule ?? '',
            ucfirst(str_replace('_', ' ', $pointage->type)),
            $a['heure_reelle'],
            $a['heure_theorique'] ?? '',
            $a['heure_theorique'] !== null ? $a['ecart_minutes'] : '',
            $statut,
        ];
    }

    public function title(): string
    {
        $debut = $this->context['date_debut'] ?? '';
        $fin   = $this->context['date_fin']   ?? '';
        return 'Présences ' . substr($debut, 5) . '-' . substr($fin, 5);
    }

    /** @return array<int, array<string, mixed>> */
    public function styles(Worksheet $sheet): array
    {
        return [
            // Ligne d'entêtes : bold + blanc sur fond indigo
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

    /** Filtres automatiques sur la ligne d'entête. */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $highest = $event->sheet->getDelegate()->getHighestRowAndColumn();
                $range = 'A1:' . $highest['column'] . '1';
                $event->sheet->getDelegate()->setAutoFilter($range);
            },
        ];
    }
}
