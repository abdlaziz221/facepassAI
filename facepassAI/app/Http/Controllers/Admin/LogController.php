<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sprint 6 cartes 7/8/9 (US-091/092) — Consultation et export des logs d'activité.
 *
 * Routes :
 *   GET /admin/logs            → index : tableau paginé + filtres
 *   GET /admin/logs/export     → téléchargement CSV / PDF / TXT (param format)
 */
class LogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = $this->buildQuery($request)
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $causers   = \App\Models\User::orderBy('name')->get(['id', 'name', 'email']);
        $logNames  = Activity::query()->select('log_name')->distinct()->pluck('log_name')->filter()->values();
        $actions   = ['created', 'updated', 'deleted'];

        return view('admin.logs.index', compact('logs', 'causers', 'logNames', 'actions'));
    }

    public function export(Request $request): Response|StreamedResponse
    {
        $format = strtolower((string) $request->input('format', 'csv'));
        $logs   = $this->buildQuery($request)->orderByDesc('created_at')->get();

        return match ($format) {
            'pdf' => $this->exportPdf($logs),
            'txt' => $this->exportTxt($logs),
            default => $this->exportCsv($logs),
        };
    }

    /** Construit la query Activity filtrée — réutilisée par index() et export(). */
    private function buildQuery(Request $request): Builder
    {
        $q = Activity::query()->with('causer');

        if ($request->filled('date_from')) {
            $q->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $q->whereDate('created_at', '<=', $request->input('date_to'));
        }
        if ($request->filled('causer_id')) {
            $q->where('causer_id', (int) $request->input('causer_id'));
        }
        if ($request->filled('action')) {
            $q->where('description', 'like', '%' . $request->input('action') . '%');
        }
        if ($request->filled('log_name')) {
            $q->where('log_name', $request->input('log_name'));
        }

        return $q;
    }

    private function exportCsv(\Illuminate\Support\Collection $logs): StreamedResponse
    {
        $filename = 'logs-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($logs) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Date', 'Utilisateur', 'Module', 'Action', 'Cible', 'Modifications'], ';');
            foreach ($logs as $log) {
                fputcsv($out, [
                    $log->created_at->format('d/m/Y H:i:s'),
                    $log->causer?->name ?? 'Système',
                    $log->log_name ?? '',
                    $log->description,
                    class_basename((string) $log->subject_type) . ' #' . $log->subject_id,
                    json_encode($log->properties, JSON_UNESCAPED_UNICODE),
                ], ';');
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function exportPdf(\Illuminate\Support\Collection $logs): Response
    {
        $pdf = Pdf::loadView('pdf.logs', compact('logs'))->setPaper('a4', 'landscape');
        return $pdf->download('logs-' . now()->format('Ymd-His') . '.pdf');
    }

    private function exportTxt(\Illuminate\Support\Collection $logs): Response
    {
        $content = "=== Journal d'activité FacePass.AI ===\n";
        $content .= "Exporté le " . now()->format('d/m/Y à H:i:s') . "\n";
        $content .= "Total : " . $logs->count() . " entrée(s)\n";
        $content .= str_repeat('-', 80) . "\n\n";

        foreach ($logs as $log) {
            $content .= sprintf(
                "[%s] %s\n  Module    : %s\n  Action    : %s\n  Par       : %s\n  Cible     : %s #%s\n",
                $log->created_at->format('Y-m-d H:i:s'),
                strtoupper($log->description),
                $log->log_name ?? '—',
                $log->description,
                $log->causer?->name ?? 'Système',
                class_basename((string) $log->subject_type) ?: '—',
                $log->subject_id ?? '—'
            );
            if (!empty($log->properties) && count($log->properties)) {
                $content .= "  Détails   : " . json_encode($log->properties, JSON_UNESCAPED_UNICODE) . "\n";
            }
            $content .= "\n";
        }

        return response($content, 200, [
            'Content-Type'        => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="logs-' . now()->format('Ymd-His') . '.txt"',
        ]);
    }
}
