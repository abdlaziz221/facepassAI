<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="pill">Mes pointages</span>
            <h1 style="font-size: 28px; font-weight: 800; color: white; letter-spacing: -0.02em; margin: 12px 0 4px;">
                Historique de mes pointages
            </h1>
            <p class="text-soft" style="margin: 0;">
                {{ $pointages->total() }} pointage(s) enregistré(s) — du plus récent au plus ancien.
            </p>
        </div>
    </x-slot>

    @if (!$profile)
        <div style="padding: 30px; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3);
                    border-radius: 12px; color: #fca5a5; text-align: center;">
            <h2 style="color: #fca5a5; margin: 0 0 6px;">Profil métier introuvable</h2>
            <p style="margin: 0; font-size: 14px;">
                Aucun profil n'est associé à votre compte. Contactez un administrateur.
            </p>
        </div>
    @else
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
                        <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                                   text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                                   border-bottom: 1px solid rgba(255,255,255,0.06);">Saisie</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pointages as $p)
                        @php $a = $retardService->analyserPointage($p); @endphp
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                            <td style="padding: 12px 16px; color: #d1d5db; font-size: 13px;">
                                {{ $p->created_at->format('d/m/Y') }}
                                <div style="font-size: 11px; color: #6b7280;">
                                    {{ $p->created_at->diffForHumans() }}
                                </div>
                            </td>
                            <td style="padding: 12px 16px;">
                                @php
                                    $typeMap = [
                                        'arrivee'     => ['Arrivée',        '#6ee7b7', 'rgba(16,185,129,0.12)', 'rgba(16,185,129,0.3)'],
                                        'debut_pause' => ['Début de pause', '#fde68a', 'rgba(245,158,11,0.12)', 'rgba(245,158,11,0.3)'],
                                        'fin_pause'   => ['Fin de pause',   '#67e8f9', 'rgba(34,211,238,0.12)', 'rgba(34,211,238,0.3)'],
                                        'depart'      => ['Départ',         '#c4b5fd', 'rgba(139,92,246,0.12)', 'rgba(139,92,246,0.3)'],
                                    ];
                                    $info = $typeMap[$p->type] ?? [$p->type, '#9ca3af', 'rgba(255,255,255,0.05)', 'rgba(255,255,255,0.1)'];
                                @endphp
                                <span style="display: inline-block; padding: 4px 10px; border-radius: 999px;
                                             background: {{ $info[2] }}; border: 1px solid {{ $info[3] }};
                                             color: {{ $info[1] }}; font-size: 12px; font-weight: 600;">
                                    {{ $info[0] }}
                                </span>
                            </td>
                            <td style="padding: 12px 16px; color: white; font-size: 14px;
                                       font-variant-numeric: tabular-nums; font-weight: 600;">
                                {{ $p->created_at->format('H:i') }}
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
                            <td style="padding: 12px 16px;">
                                @if ($p->manuel)
                                    <span style="display: inline-block; padding: 3px 8px; border-radius: 6px;
                                                 background: rgba(245,158,11,0.12); border: 1px solid rgba(245,158,11,0.25);
                                                 color: #fde68a; font-size: 11px; font-weight: 600;">
                                        Manuelle
                                    </span>
                                @else
                                    <span style="display: inline-block; padding: 3px 8px; border-radius: 6px;
                                                 background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.2);
                                                 color: #a5b4fc; font-size: 11px; font-weight: 600;">
                                        Biométrique
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding: 40px 16px; text-align: center; color: #6b7280; font-size: 14px;">
                                Vous n'avez encore aucun pointage enregistré.
                                <div style="margin-top: 10px;">
                                    <a href="{{ route('pointages.create') }}" target="_blank"
                                       style="color: #818cf8; font-size: 13px;">
                                        Aller au kiosque de pointage →
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($pointages->hasPages())
            <div style="margin-top: 20px;">{{ $pointages->links() }}</div>
        @endif
    @endif
</x-app-layout>
