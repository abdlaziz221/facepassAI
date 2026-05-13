@extends('pdf._layout')

@section('title', 'Rapport de présences — FacePass.AI')
@section('subtitle_header', 'Rapport de présences')
@section('header_right', 'Période ' . \Carbon\Carbon::parse($data['date_debut'])->format('d/m/Y') . ' → ' . \Carbon\Carbon::parse($data['date_fin'])->format('d/m/Y'))

@section('content')
    <h1>Rapport de présences</h1>
    <p class="subtitle">
        Période du
        <strong>{{ \Carbon\Carbon::parse($data['date_debut'])->format('d/m/Y') }}</strong>
        au
        <strong>{{ \Carbon\Carbon::parse($data['date_fin'])->format('d/m/Y') }}</strong>
        @if ($employe)
            — Employé : <strong>{{ $employe->user->name ?? ('#' . $employe->id) }}</strong>
        @else
            — Tous les employés
        @endif
    </p>

    {{-- Synthèse --}}
    <h2>Synthèse</h2>
    <table style="margin-bottom: 18px;">
        <thead>
            <tr>
                <th>Total pointages</th>
                <th>Retards</th>
                <th>Départs anticipés</th>
                <th>À l'heure</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="mono"><strong>{{ $pointages->count() }}</strong></td>
                <td class="mono"><strong style="color:#b45309;">{{ $countRetards }}</strong></td>
                <td class="mono"><strong style="color:#991b1b;">{{ $countDeparts }}</strong></td>
                <td class="mono"><strong style="color:#065f46;">{{ $pointages->count() - $countRetards - $countDeparts }}</strong></td>
            </tr>
        </tbody>
    </table>

    {{-- Détail --}}
    <h2>Détail des pointages</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Employé</th>
                <th>Type</th>
                <th>Heure</th>
                <th>Théorique</th>
                <th class="text-right">Écart</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pointages as $p)
                @php $a = $retardService->analyserPointage($p); @endphp
                <tr>
                    <td class="mono">{{ $p->created_at->format('d/m/Y') }}</td>
                    <td>{{ $p->employe->user->name ?? ('#' . $p->employe_id) }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $p->type)) }}</td>
                    <td class="mono">{{ $a['heure_reelle'] }}</td>
                    <td class="mono text-muted">{{ $a['heure_theorique'] ?? '—' }}</td>
                    <td class="mono text-right">
                        @if ($a['heure_theorique'] !== null)
                            {{ $a['ecart_minutes'] > 0 ? '+' : '' }}{{ $a['ecart_minutes'] }} min
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @if ($a['is_retard'])
                            <span class="pill pill-warning">Retard</span>
                        @elseif ($a['is_depart_anticipe'])
                            <span class="pill pill-danger">Anticipé</span>
                        @else
                            <span class="pill pill-success">À l'heure</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center; color:#9ca3af; padding:30px;">
                        Aucun pointage sur cette période.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p style="margin-top:24px; color:#9ca3af; font-size:9px;">
        Rapport généré automatiquement par FacePass.AI le {{ now()->format('d/m/Y à H:i') }}.
        Document confidentiel — usage interne RH uniquement.
    </p>
@endsection
