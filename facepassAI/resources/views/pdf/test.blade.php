@extends('pdf._layout')

@section('title', 'Test PDF — FacePass.AI')
@section('subtitle_header', 'Vérification de l\'installation dompdf')

@section('content')
    <h1>Test du module PDF</h1>
    <p class="subtitle">
        Ce document confirme que le paquet <strong>barryvdh/laravel-dompdf</strong>
        est correctement installé et configuré. Il servira de base pour tous les
        futurs rapports (retards, paie, présences, etc.).
    </p>

    <h2>Données de démonstration</h2>
    <table>
        <thead>
            <tr>
                <th>Employé</th>
                <th>Type</th>
                <th>Heure</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['nom'] }}</td>
                    <td>{{ $row['type'] }}</td>
                    <td class="mono">{{ $row['heure'] }}</td>
                    <td>
                        @if ($row['statut'] === 'à l\'heure')
                            <span class="pill pill-success">À l'heure</span>
                        @elseif ($row['statut'] === 'retard')
                            <span class="pill pill-warning">Retard</span>
                        @else
                            <span class="pill pill-danger">Anomalie</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Vérifications</h2>
    <ul>
        <li>Police DejaVu Sans (accents UTF-8 affichés correctement)</li>
        <li>En-tête avec logo + sous-titre + horodatage à droite</li>
        <li>Pied de page avec numérotation automatique des pages</li>
        <li>Tableau avec alternance de couleurs et badges colorés</li>
    </ul>

    <p style="margin-top:30px; color:#9ca3af; font-size:10px;">
        Document généré automatiquement par FacePass.AI le {{ now()->format('d/m/Y à H:i:s') }}.
    </p>
@endsection
