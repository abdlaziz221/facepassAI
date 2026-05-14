@extends('pdf._layout')

@section('title', 'Fiche de paie — ' . ($profile->user->name ?? 'Employe'))
@section('subtitle_header', 'Fiche de paie')
@section('header_right', \Carbon\Carbon::create($year, $month, 1)->locale('fr')->isoFormat('MMMM YYYY'))

@section('content')
    <h1>Récapitulatif de paie</h1>
    <p class="subtitle">
        Période : <strong>{{ \Carbon\Carbon::create($year, $month, 1)->locale('fr')->isoFormat('MMMM YYYY') }}</strong>
    </p>

    {{-- Sprint 6 carte 3 (US-082) — Mention personnelle --}}
    <div style="background: #f3f4f6; border-radius: 6px; padding: 14px 16px; margin: 12px 0 22px;">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border:none; padding:0; vertical-align: top;">
                    <div style="font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">
                        Employé
                    </div>
                    <div style="font-size: 14px; font-weight: 700; color: #111827; margin-top: 2px;">
                        {{ $profile->user->name ?? '—' }}
                    </div>
                    <div style="font-size: 10px; color: #6b7280; margin-top: 1px;">
                        {{ $profile->user->email ?? '' }}
                    </div>
                </td>
                <td style="border:none; padding:0; text-align: right; vertical-align: top;">
                    <div style="font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">
                        Matricule
                    </div>
                    <div style="font-size: 14px; font-weight: 700; color: #111827; margin-top: 2px;">
                        {{ $profile->matricule ?: '—' }}
                    </div>
                    <div style="font-size: 10px; color: #6b7280; margin-top: 1px;">
                        {{ $profile->poste ?: '' }}{{ $profile->departement ? ' · ' . $profile->departement : '' }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Sprint 6 carte 4 (US-083) — Alerte données incomplètes --}}
    @if (!empty($manquantes))
        <div style="background: #fef3c7; border: 1px solid #f59e0b; padding: 12px 14px;
                    border-radius: 6px; margin: 0 0 18px; font-size: 10px; color: #92400e;">
            <strong>⚠ Données incomplètes — calcul partiel</strong><br>
            Les éléments suivants sont manquants sur le profil :
            <strong>{{ implode(', ', $manquantes) }}</strong>.
            Le montant ci-dessous peut être inexact.
            Contactez l'administrateur RH pour régulariser.
        </div>
    @endif

    {{-- Synthèse --}}
    <h2>Synthèse</h2>
    <table>
        <thead>
            <tr>
                <th>Salaire brut</th>
                <th class="text-right">Total déductions</th>
                <th class="text-right">Salaire net</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="mono" style="font-size: 13px; font-weight: 600;">
                    {{ number_format($salaire['brut'], 0, ',', ' ') }} F CFA
                </td>
                <td class="text-right mono" style="color: #b45309; font-size: 13px;">
                    − {{ number_format($salaire['deductions']['total'], 0, ',', ' ') }} F CFA
                </td>
                <td class="text-right mono" style="color: #065f46; font-weight: 800; font-size: 15px;">
                    {{ number_format($salaire['net'], 0, ',', ' ') }} F CFA
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Détail des déductions --}}
    <h2>Détail des déductions</h2>
    <table>
        <thead>
            <tr>
                <th>Poste</th>
                <th class="text-right">Quantité</th>
                <th class="text-right">Taux unitaire</th>
                <th class="text-right">Montant</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Retards</td>
                <td class="text-right mono">{{ $salaire['deductions']['retards']['minutes'] }} min</td>
                <td class="text-right mono text-muted">
                    {{ number_format($salaire['deductions']['meta']['tarif_minute'], 2, ',', ' ') }} F/min
                </td>
                <td class="text-right mono">
                    − {{ number_format($salaire['deductions']['retards']['montant'], 0, ',', ' ') }} F
                </td>
            </tr>
            <tr>
                <td>Départs anticipés</td>
                <td class="text-right mono">{{ $salaire['deductions']['departs_anticipes']['minutes'] }} min</td>
                <td class="text-right mono text-muted">
                    {{ number_format($salaire['deductions']['meta']['tarif_minute'], 2, ',', ' ') }} F/min
                </td>
                <td class="text-right mono">
                    − {{ number_format($salaire['deductions']['departs_anticipes']['montant'], 0, ',', ' ') }} F
                </td>
            </tr>
            <tr>
                <td>Absences non justifiées</td>
                <td class="text-right mono">{{ $salaire['deductions']['absences']['jours'] }} jour(s)</td>
                <td class="text-right mono text-muted">
                    {{ number_format($salaire['deductions']['meta']['tarif_journalier'], 0, ',', ' ') }} F/jour
                </td>
                <td class="text-right mono">
                    − {{ number_format($salaire['deductions']['absences']['montant'], 0, ',', ' ') }} F
                </td>
            </tr>
            <tr style="background: #fef3c7;">
                <td colspan="3" class="text-right"
                    style="font-weight: 700; text-transform: uppercase; font-size: 9px; letter-spacing: 0.05em;">
                    Total déductions
                </td>
                <td class="text-right mono" style="font-weight: 800; color: #92400e; font-size: 12px;">
                    − {{ number_format($salaire['deductions']['total'], 0, ',', ' ') }} F CFA
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Paramètres du calcul (transparence) --}}
    <h2>Paramètres du calcul</h2>
    <table>
        <tbody>
            <tr>
                <td style="width: 50%;">Jours ouvrables du mois</td>
                <td class="text-right mono">{{ $salaire['deductions']['meta']['jours_ouvrables_mois'] }} jours</td>
            </tr>
            <tr>
                <td>Heures théoriques par jour</td>
                <td class="text-right mono">{{ $salaire['deductions']['meta']['heures_par_jour'] }} h</td>
            </tr>
            <tr>
                <td>Tarif horaire de référence</td>
                <td class="text-right mono">{{ number_format($salaire['deductions']['meta']['tarif_horaire'], 2, ',', ' ') }} F/h</td>
            </tr>
        </tbody>
    </table>

    <p style="margin-top: 24px; font-size: 9px; color: #6b7280; line-height: 1.5;">
        Document généré automatiquement par FacePass.AI le {{ now()->format('d/m/Y à H:i') }}.
        Ce document est <strong>confidentiel</strong> et destiné uniquement à
        <strong>{{ $profile->user->name ?? 'l\'employé concerné' }}</strong>.
        Il ne se substitue pas au bulletin de paie officiel délivré par le service RH.
    </p>
@endsection
