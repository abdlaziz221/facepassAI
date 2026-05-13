@extends('pdf._layout')

@section('title', 'Journal d\'activité — FacePass.AI')
@section('subtitle_header', 'Journal d\'activité')
@section('header_right', 'Export du ' . now()->format('d/m/Y à H:i'))

@section('content')
    <h1>Journal d'activité système</h1>
    <p class="subtitle">
        {{ $logs->count() }} événement(s) — Document généré automatiquement.
    </p>

    <table>
        <thead>
            <tr>
                <th style="width: 90px;">Date/Heure</th>
                <th>Utilisateur</th>
                <th>Module</th>
                <th>Action</th>
                <th>Cible</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td class="mono" style="font-size: 9px;">
                        {{ $log->created_at->format('d/m/Y') }}<br>
                        {{ $log->created_at->format('H:i:s') }}
                    </td>
                    <td>{{ $log->causer?->name ?? 'Système' }}</td>
                    <td>
                        <span class="pill pill-info" style="font-size: 8px;">
                            {{ $log->log_name ?? '—' }}
                        </span>
                    </td>
                    <td>{{ $log->description }}</td>
                    <td class="text-muted" style="font-size: 9px;">
                        {{ class_basename((string) $log->subject_type) }} #{{ $log->subject_id }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 30px; color: #9ca3af;">
                        Aucun événement dans la période sélectionnée.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p style="margin-top: 24px; font-size: 9px; color: #9ca3af;">
        Document confidentiel — usage interne audit/conformité uniquement.
    </p>
@endsection
