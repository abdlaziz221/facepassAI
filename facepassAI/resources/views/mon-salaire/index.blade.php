<x-app-layout>
    <x-slot name="header">
        <div style="display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <span class="pill">Mon salaire</span>
                <h1 style="font-size: 28px; font-weight: 800; color: white; letter-spacing: -0.02em; margin: 12px 0 4px;">
                    Récapitulatif mensuel
                </h1>
                <p class="text-soft" style="margin: 0;">
                    Brut, déductions et net pour le mois sélectionné.
                </p>
            </div>
            <div style="display: flex; gap: 8px; align-items: end; flex-wrap: wrap;">
                <form method="GET" action="{{ route('mon-salaire.index') }}"
                      style="display: flex; gap: 8px; align-items: end;">
                    <div>
                        <label style="display: block; font-size: 12px; color: #9ca3af; margin-bottom: 6px;">Mois</label>
                        <input type="month" name="mois" value="{{ $moisInput }}"
                               style="padding: 10px 12px; background: rgba(0,0,0,0.4);
                                      border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
                                      color: white; font-size: 14px;">
                    </div>
                    <button type="submit"
                            style="padding: 10px 18px; background: linear-gradient(135deg, #6366f1, #8b5cf6);
                                   border: none; border-radius: 8px; color: white; font-size: 14px;
                                   font-weight: 600; cursor: pointer; white-space: nowrap;">
                        Afficher
                    </button>
                </form>
                @if ($profile)
                    {{-- Sprint 6 carte 3 (US-082) — Téléchargement PDF --}}
                    <a href="{{ route('mon-salaire.pdf', ['mois' => $moisInput]) }}"
                       style="padding: 10px 18px; background: rgba(239,68,68,0.1);
                              border: 1px solid rgba(239,68,68,0.3); border-radius: 8px;
                              color: #fca5a5; font-size: 14px; font-weight: 600;
                              text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
                              white-space: nowrap;">
                        📄 Télécharger PDF
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    {{-- Pas de profil métier --}}
    @if (!$profile)
        <div style="padding: 30px; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3);
                    border-radius: 12px; color: #fca5a5; text-align: center;">
            <h2 style="color: #fca5a5; margin: 0 0 6px;">Profil métier introuvable</h2>
            <p style="margin: 0; font-size: 14px;">
                Aucun profil n'est associé à votre compte. Contactez un administrateur.
            </p>
        </div>
    @else

    {{-- Bannière horaires non configurés --}}
    <x-horaires-warning />

    {{-- Sprint 6 carte 4 (US-083) — Alerte si données incomplètes --}}
    @if (!empty($manquantes))
        <div style="margin-bottom: 20px; padding: 16px 20px;
                    background: linear-gradient(135deg, rgba(245,158,11,0.08), rgba(245,158,11,0.04));
                    border: 1px solid rgba(245,158,11,0.3); border-radius: 12px;
                    display: flex; align-items: center; justify-content: space-between;
                    flex-wrap: wrap; gap: 16px;">
            <div style="flex: 1; min-width: 240px;">
                <h3 style="margin: 0 0 4px; color: #fde68a; font-size: 14px; font-weight: 700;">
                    ⚠ Données incomplètes — calcul partiel
                </h3>
                <p style="margin: 0; color: #fcd34d; font-size: 13px; line-height: 1.5;">
                    Les éléments suivants sont manquants sur votre profil :
                    <strong style="color: #fde68a;">{{ implode(', ', $manquantes) }}</strong>.
                    Les montants ci-dessous peuvent être inexacts.
                </p>
            </div>
            <a href="mailto:admin@facepass.ai?subject=Données%20profil%20incomplètes%20-%20{{ urlencode($profile->user->name ?? 'Employe') }}"
               style="padding: 10px 18px; background: linear-gradient(135deg, #f59e0b, #d97706);
                      border: none; border-radius: 8px; color: white; font-size: 13px;
                      font-weight: 600; text-decoration: none; white-space: nowrap;
                      display: inline-flex; align-items: center; gap: 6px;">
                Contacter l'administrateur →
            </a>
        </div>
    @endif

    {{-- 3 KPI : brut / déductions / net --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 16px; margin-bottom: 24px;">
        <div class="card card-stat">
            <div class="label">Salaire brut</div>
            <div class="value" style="font-size: 24px;">
                {{ number_format($salaire['brut'], 0, ',', ' ') }}
                <span style="font-size: 14px; color: #9ca3af;">F CFA</span>
            </div>
            <div class="delta" style="color: #9ca3af;">contrat mensuel</div>
        </div>
        <div class="card card-stat">
            <div class="label">Total déductions</div>
            <div class="value" style="font-size: 24px;
                background: linear-gradient(135deg, #fde68a, #f59e0b);
                -webkit-background-clip: text; background-clip: text; color: transparent;">
                − {{ number_format($salaire['deductions']['total'], 0, ',', ' ') }}
                <span style="font-size: 14px; color: #fde68a;
                             background: none; -webkit-text-fill-color: #fde68a;">F CFA</span>
            </div>
            <div class="delta" style="color: #fde68a;">retards + anticipés + absences</div>
        </div>
        <div class="card card-stat" style="border-color: rgba(16,185,129,0.3);">
            <div class="label">Salaire net</div>
            <div class="value" style="font-size: 28px; font-weight: 800;
                background: linear-gradient(135deg, #6ee7b7, #10b981);
                -webkit-background-clip: text; background-clip: text; color: transparent;">
                {{ number_format($salaire['net'], 0, ',', ' ') }}
                <span style="font-size: 14px; color: #6ee7b7;
                             background: none; -webkit-text-fill-color: #6ee7b7;">F CFA</span>
            </div>
            <div class="delta" style="color: #6ee7b7;">à percevoir</div>
        </div>
    </div>

    {{-- Détail des déductions --}}
    <h2 class="section-title">Détail des déductions</h2>
    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
                border-radius: 12px; overflow: hidden; margin-bottom: 24px;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: rgba(255,255,255,0.04);">
                <tr>
                    <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Poste</th>
                    <th style="text-align: right; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Quantité</th>
                    <th style="text-align: right; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Taux</th>
                    <th style="text-align: right; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Montant</th>
                </tr>
            </thead>
            <tbody>
                {{-- Retards --}}
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                    <td style="padding: 14px 16px; color: white; font-size: 14px;">
                        ⏰ Retards
                    </td>
                    <td style="padding: 14px 16px; text-align: right; color: #d1d5db; font-size: 14px;
                               font-variant-numeric: tabular-nums;">
                        {{ $salaire['deductions']['retards']['minutes'] }} min
                    </td>
                    <td style="padding: 14px 16px; text-align: right; color: #9ca3af; font-size: 13px;
                               font-variant-numeric: tabular-nums;">
                        {{ number_format($salaire['deductions']['meta']['tarif_minute'], 2, ',', ' ') }} F/min
                    </td>
                    <td style="padding: 14px 16px; text-align: right; color: #fde68a; font-size: 14px;
                               font-weight: 600; font-variant-numeric: tabular-nums;">
                        − {{ number_format($salaire['deductions']['retards']['montant'], 0, ',', ' ') }} F
                    </td>
                </tr>

                {{-- Départs anticipés --}}
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                    <td style="padding: 14px 16px; color: white; font-size: 14px;">
                        🚪 Départs anticipés
                    </td>
                    <td style="padding: 14px 16px; text-align: right; color: #d1d5db; font-size: 14px;
                               font-variant-numeric: tabular-nums;">
                        {{ $salaire['deductions']['departs_anticipes']['minutes'] }} min
                    </td>
                    <td style="padding: 14px 16px; text-align: right; color: #9ca3af; font-size: 13px;
                               font-variant-numeric: tabular-nums;">
                        {{ number_format($salaire['deductions']['meta']['tarif_minute'], 2, ',', ' ') }} F/min
                    </td>
                    <td style="padding: 14px 16px; text-align: right; color: #fca5a5; font-size: 14px;
                               font-weight: 600; font-variant-numeric: tabular-nums;">
                        − {{ number_format($salaire['deductions']['departs_anticipes']['montant'], 0, ',', ' ') }} F
                    </td>
                </tr>

                {{-- Absences non justifiées --}}
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                    <td style="padding: 14px 16px; color: white; font-size: 14px;">
                        📅 Absences non justifiées
                    </td>
                    <td style="padding: 14px 16px; text-align: right; color: #d1d5db; font-size: 14px;
                               font-variant-numeric: tabular-nums;">
                        {{ $salaire['deductions']['absences']['jours'] }} jour(s)
                    </td>
                    <td style="padding: 14px 16px; text-align: right; color: #9ca3af; font-size: 13px;
                               font-variant-numeric: tabular-nums;">
                        {{ number_format($salaire['deductions']['meta']['tarif_journalier'], 0, ',', ' ') }} F/j
                    </td>
                    <td style="padding: 14px 16px; text-align: right; color: #fca5a5; font-size: 14px;
                               font-weight: 600; font-variant-numeric: tabular-nums;">
                        − {{ number_format($salaire['deductions']['absences']['montant'], 0, ',', ' ') }} F
                    </td>
                </tr>

                {{-- TOTAL --}}
                <tr style="background: rgba(255,255,255,0.04);">
                    <td colspan="3" style="padding: 14px 16px; color: white; font-size: 13px;
                                           text-transform: uppercase; letter-spacing: 0.08em;
                                           font-weight: 700; text-align: right;">
                        Total déductions
                    </td>
                    <td style="padding: 14px 16px; text-align: right;
                               background: linear-gradient(135deg, #fde68a, #f59e0b);
                               -webkit-background-clip: text; background-clip: text; color: transparent;
                               font-size: 16px; font-weight: 800;
                               font-variant-numeric: tabular-nums;">
                        − {{ number_format($salaire['deductions']['total'], 0, ',', ' ') }} F
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Pointages du mois --}}
    @if ($pointages->isNotEmpty())
        <h2 class="section-title">Mes pointages du mois</h2>
        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
                    border-radius: 12px; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: rgba(255,255,255,0.04);">
                    <tr>
                        <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                                   text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                                   border-bottom: 1px solid rgba(255,255,255,0.06);">Date</th>
                        <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                                   text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                                   border-bottom: 1px solid rgba(255,255,255,0.06);">Type</th>
                        <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                                   text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                                   border-bottom: 1px solid rgba(255,255,255,0.06);">Heure</th>
                        <th style="text-align: right; padding: 12px 16px; font-size: 12px;
                                   text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                                   border-bottom: 1px solid rgba(255,255,255,0.06);">Écart</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pointages as $p)
                        @php $a = $retardService->analyserPointage($p); @endphp
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                            <td style="padding: 12px 16px; color: #d1d5db; font-size: 13px;">
                                {{ $p->created_at->format('d/m/Y') }}
                            </td>
                            <td style="padding: 12px 16px; color: #d1d5db; font-size: 13px;">
                                {{ ucfirst(str_replace('_', ' ', $p->type)) }}
                            </td>
                            <td style="padding: 12px 16px; color: white; font-size: 13px;
                                       font-variant-numeric: tabular-nums;">
                                {{ $a['heure_reelle'] }}
                            </td>
                            <td style="padding: 12px 16px; text-align: right; font-size: 13px;
                                       font-variant-numeric: tabular-nums; font-weight: 600;
                                       color: {{ $a['is_retard'] ? '#fde68a' : ($a['is_depart_anticipe'] ? '#fca5a5' : '#6ee7b7') }};">
                                @if ($a['heure_theorique'] !== null)
                                    {{ $a['ecart_minutes'] > 0 ? '+' : '' }}{{ $a['ecart_minutes'] }} min
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @endif {{-- /if(!$profile) --}}
</x-app-layout>
