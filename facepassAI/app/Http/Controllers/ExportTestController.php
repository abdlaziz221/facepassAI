<?php

namespace App\Http\Controllers;

use App\Exports\TestExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sprint 5 cartes 6 & 7 — Routes de test pour vérifier l'installation
 * des paquets dompdf et maatwebsite/excel.
 */
class ExportTestController extends Controller
{
    /** GET /test-export-pdf */
    public function pdf(): Response
    {
        $rows = [
            ['nom' => 'Aïssatou Sow',  'type' => 'arrivee', 'heure' => '08:00', 'statut' => 'à l\'heure'],
            ['nom' => 'Mamadou Diop',  'type' => 'arrivee', 'heure' => '08:15', 'statut' => 'retard'],
            ['nom' => 'Fatou Ndiaye',  'type' => 'depart',  'heure' => '17:00', 'statut' => 'à l\'heure'],
            ['nom' => 'Khady Camara',  'type' => 'depart',  'heure' => '16:30', 'statut' => 'anomalie'],
            ['nom' => 'Ibrahima Fall', 'type' => 'arrivee', 'heure' => '07:50', 'statut' => 'à l\'heure'],
        ];

        $pdf = Pdf::loadView('pdf.test', compact('rows'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('test-facepass.pdf');
    }

    /** GET /test-export-excel */
    public function excel(): Response
    {
        return Excel::download(new TestExport(), 'test-facepass.xlsx');
    }
}
