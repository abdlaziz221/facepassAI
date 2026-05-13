<x-app-layout>
    <x-slot name="header">
        <div style="display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <span class="pill">Mes absences</span>
                <h1 style="font-size: 28px; font-weight: 800; color: white; letter-spacing: -0.02em; margin: 12px 0 4px;">
                    Historique de mes demandes
                </h1>
                <p class="text-soft" style="margin: 0;">
                    {{ $counts['total'] }} demande(s) au total — suivi du traitement.
                </p>
            </div>
            <a href="{{ route('demandes-absence.create') }}" class="btn-primary">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Nouvelle demande
            </a>
        </div>
    </x-slot>

    {{-- Flash --}}
    @if (session('success'))
        <div style="margin-bottom: 20px; padding: 14px 18px; background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); border-radius: 10px; color: #6ee7b7; font-size: 14px;">
            ✓ {{ session('success') }}
        </div>
    @endif

    {{-- Compteurs par statut --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="card card-stat">
            <div class="label">En attente</div>
            <div class="value" style="background: linear-gradient(135deg, #fde68a, #f59e0b);
                                       -webkit-background-clip: text; background-clip: text; color: transparent;">
                {{ $counts['en_attente'] }}
            </div>
            <div class="delta" style="color: #fde68a;">en cours de traitement</div>
        </div>
        <div class="card card-stat">
            <div class="label">Validées</div>
            <div class="value" style="background: linear-gradient(135deg, #6ee7b7, #10b981);
                                       -webkit-background-clip: text; background-clip: text; color: transparent;">
                {{ $counts['validee'] }}
            </div>
            <div class="delta" style="color: #6ee7b7;">acceptées</div>
        </div>
        <div class="card card-stat">
            <div class="label">Refusées</div>
            <div class="value" style="background: linear-gradient(135deg, #fca5a5, #ef4444);
                                       -webkit-background-clip: text; background-clip: text; color: transparent;">
                {{ $counts['refusee'] }}
            </div>
            <div class="delta" style="color: #fca5a5;">non accordées</div>
        </div>
    </div>

    {{-- Tableau historique --}}
    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
                border-radius: 12px; overflow: hidden;">

        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: rgba(255,255,255,0.04);">
                <tr>
                    <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Période</th>
                    <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Motif</th>
                    <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Statut</th>
                    <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Traitée par</th>
                    <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Déposée le</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($demandes as $demande)
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.04); vertical-align: top;">
                        <td style="padding: 14px 16px; color: white; font-size: 14px;">
                            <div style="font-weight: 600;">
                                {{ $demande->date_debut->format('d/m/Y') }}
                                <span style="color: #6b7280;">→</span>
                                {{ $demande->date_fin->format('d/m/Y') }}
                            </div>
                            <div style="font-size: 12px; color: #9ca3af; margin-top: 2px;">
                                {{ $demande->date_debut->diffInDays($demande->date_fin) + 1 }} jour(s)
                            </div>
                        </td>
                        <td style="padding: 14px 16px; color: #d1d5db; font-size: 13px; max-width: 320px;">
                            {{ $demande->motif }}
                            @if ($demande->commentaire_gestionnaire)
                                <div style="margin-top: 8px; padding: 8px 10px;
                                            background: rgba(0,0,0,0.25);
                                            border-left: 3px solid {{ $demande->statut === 'validee' ? '#10b981' : ($demande->statut === 'refusee' ? '#ef4444' : '#9ca3af') }};
                                            border-radius: 4px; font-size: 12px; color: #e5e7eb;">
                                    <span style="color: #9ca3af; font-weight: 600;">
                                        @if ($demande->statut === 'refusee')
                                            Justification du refus :
                                        @else
                                            Commentaire :
                                        @endif
                                    </span>
                                    {{ $demande->commentaire_gestionnaire }}
                                </div>
                            @endif
                        </td>
                        <td style="padding: 14px 16px;">
                            @if ($demande->statut === 'en_attente')
                                <span style="display: inline-flex; align-items: center; gap: 6px;
                                             padding: 4px 10px; border-radius: 999px;
                                             background: rgba(245,158,11,0.12);
                                             border: 1px solid rgba(245,158,11,0.3);
                                             color: #fde68a; font-size: 12px; font-weight: 600;">
                                    <span style="width: 6px; height: 6px; border-radius: 999px; background: #f59e0b;"></span>
                                    En attente
                                </span>
                            @elseif ($demande->statut === 'validee')
                                <span style="display: inline-flex; align-items: center; gap: 6px;
                                             padding: 4px 10px; border-radius: 999px;
                                             background: rgba(16,185,129,0.12);
                                             border: 1px solid rgba(16,185,129,0.3);
                                             color: #6ee7b7; font-size: 12px; font-weight: 600;">
                                    <span style="width: 6px; height: 6px; border-radius: 999px; background: #10b981;"></span>
                                    Validée
                                </span>
                            @else
                                <span style="display: inline-flex; align-items: center; gap: 6px;
                                             padding: 4px 10px; border-radius: 999px;
                                             background: rgba(239,68,68,0.12);
                                             border: 1px solid rgba(239,68,68,0.3);
                                             color: #fca5a5; font-size: 12px; font-weight: 600;">
                                    <span style="width: 6px; height: 6px; border-radius: 999px; background: #ef4444;"></span>
                                    Refusée
                                </span>
                            @endif
                        </td>
                        <td style="padding: 14px 16px; color: #d1d5db; font-size: 13px;">
                            {{ $demande->gestionnaire->name ?? '—' }}
                        </td>
                        <td style="padding: 14px 16px; color: #9ca3af; font-size: 13px;">
                            {{ $demande->created_at->format('d/m/Y') }}
                            <div style="font-size: 11px; color: #6b7280;">
                                {{ $demande->created_at->diffForHumans() }}
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding: 40px 16px; text-align: center; color: #6b7280; font-size: 14px;">
                            Vous n'avez encore déposé aucune demande d'absence.
                            <div style="margin-top: 10px;">
                                <a href="{{ route('demandes-absence.create') }}"
                                   style="color: #818cf8; font-size: 13px;">
                                    Déposer ma première demande →
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($demandes->hasPages())
        <div style="margin-top: 20px;">
            {{ $demandes->links() }}
        </div>
    @endif
</x-app-layout>
