<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('employes.index') }}" class="link-muted" style="font-size: 13px;">← Retour à la liste</a>
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-top: 8px;">
            <div style="display: flex; align-items: center; gap: 18px;">
                {{-- Avatar (photo faciale en rond) --}}
                @if ($profile->photo_faciale)
                    <img src="{{ Storage::url($profile->photo_faciale) }}"
                         alt="Photo de {{ $profile->user->name }}"
                         style="width: 72px; height: 72px; object-fit: cover; border-radius: 999px;
                                border: 2px solid rgba(99,102,241,0.4);
                                box-shadow: 0 0 24px rgba(99,102,241,0.25);">
                @else
                    <div style="width: 72px; height: 72px; border-radius: 999px;
                                background: linear-gradient(135deg, #6366f1, #8b5cf6);
                                display: flex; align-items: center; justify-content: center;
                                font-size: 26px; font-weight: 700; color: white;
                                border: 2px solid rgba(99,102,241,0.4);">
                        {{ strtoupper(substr($profile->user->name, 0, 2)) }}
                    </div>
                @endif
                <div>
                    <span class="pill">{{ $profile->matricule }}</span>
                    <h1 style="font-size: 28px; font-weight: 800; color: white; letter-spacing: -0.02em; margin: 8px 0 4px;">
                        {{ $profile->user->name }}
                    </h1>
                    <p class="text-soft" style="margin: 0;">{{ $profile->poste }} · {{ $profile->departement }}</p>
                </div>
            </div>
            @can('update', $profile)
                <a href="{{ route('employes.edit', $profile) }}" class="btn-primary">Modifier</a>
            @endcan
        </div>
    </x-slot>

    @if (session('success'))
        <div style="margin-bottom: 16px; padding: 12px 16px; border-radius: 10px;
                    background: rgba(34, 197, 94, 0.08); border: 1px solid rgba(34, 197, 94, 0.25);
                    color: #86efac; font-size: 14px;">
            ✓ {{ session('success') }}
        </div>
    @endif

    {{-- Carte d'identité --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
        <div class="card card-stat">
            <div class="label">Email</div>
            <div style="margin-top: 8px; font-size: 16px; color: white; word-break: break-all;">{{ $profile->user->email }}</div>
        </div>
        <div class="card card-stat">
            <div class="label">Statut</div>
            <div style="margin-top: 8px;">
                @if ($profile->user->est_actif)
                    <span class="pill pill-success">Actif</span>
                @else
                    <span class="pill pill-danger">Désactivé</span>
                @endif
            </div>
        </div>
        <div class="card card-stat">
            <div class="label">Salaire brut</div>
            <div class="value">{{ number_format($profile->salaire_brut, 0, ',', ' ') }}<span style="font-size: 14px; color: #6b7280;"> FCFA</span></div>
        </div>
        <div class="card card-stat">
            <div class="label">Membre depuis</div>
            <div style="margin-top: 8px; font-size: 16px; color: white;">{{ $profile->user->created_at->format('d/m/Y') }}</div>
        </div>
    </div>

    {{-- Détails du poste --}}
    <h2 class="section-title">Détails du poste</h2>
    <div class="glass" style="padding: 24px; border-radius: 16px;">
        <dl style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px; margin: 0; align-items: center;">
            <dt style="color: #9ca3af; font-size: 13px;">Matricule</dt>
            <dd style="margin: 0; color: white; font-family: 'JetBrains Mono', monospace;">{{ $profile->matricule }}</dd>

            <dt style="color: #9ca3af; font-size: 13px;">Poste</dt>
            <dd style="margin: 0; color: white;">{{ $profile->poste }}</dd>

            <dt style="color: #9ca3af; font-size: 13px;">Département</dt>
            <dd style="margin: 0; color: white;">{{ $profile->departement }}</dd>

            <dt style="color: #9ca3af; font-size: 13px;">Photo faciale</dt>
            <dd style="margin: 0;">
                @if ($profile->photo_faciale)
                    <img src="{{ Storage::url($profile->photo_faciale) }}"
                         alt="Photo enrôlement"
                         style="width: 120px; height: 120px; object-fit: cover; border-radius: 12px;
                                border: 1px solid rgba(255,255,255,0.1);">
                @else
                    <span style="color: #6b7280; font-style: italic;">
                        Non encore enregistrée
                    </span>
                @endif
            </dd>

            <dt style="color: #9ca3af; font-size: 13px;">Encodage facial</dt>
            <dd style="margin: 0;">
                @if (!empty($profile->encodage_facial))
                    <span class="pill pill-success">
                        ✓ Enrôlé ({{ count($profile->encodage_facial) }} dimensions)
                    </span>
                @else
                    <span class="pill" style="background: rgba(245,158,11,0.12);
                          border-color: rgba(245,158,11,0.25); color: #fde68a;">
                        Pas encore enrôlé
                    </span>
                @endif
            </dd>
        </dl>
    </div>

    {{-- Activité récente (Sprint 6) --}}
    <h2 class="section-title">Activité récente</h2>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">

        {{-- Derniers pointages --}}
        <div class="glass" style="padding: 0; border-radius: 16px; overflow: hidden;">
            <div style="padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,0.06);
                        display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 14px; color: white; font-weight: 600;">
                    Derniers pointages
                </h3>
                <span style="font-size: 12px; color: #9ca3af;">10 plus récents</span>
            </div>
            @php
                $recentPointages = \App\Models\Pointage::where('employe_id', $profile->id)
                    ->orderByDesc('created_at')
                    ->limit(10)
                    ->get();
                $retardService = \App\Services\RetardService::fromCurrent();
            @endphp
            @if ($recentPointages->isEmpty())
                <div style="padding: 30px 20px; text-align: center; color: #6b7280; font-size: 13px;">
                    Aucun pointage enregistré
                </div>
            @else
                <div style="max-height: 360px; overflow-y: auto;">
                    @foreach ($recentPointages as $p)
                        @php
                            $a = $retardService->analyserPointage($p);
                            $typeMap = [
                                'arrivee'     => ['Arrivée',        '#6ee7b7'],
                                'debut_pause' => ['Début pause',    '#fde68a'],
                                'fin_pause'   => ['Fin pause',      '#67e8f9'],
                                'depart'      => ['Départ',         '#c4b5fd'],
                            ];
                            $info = $typeMap[$p->type] ?? [$p->type, '#9ca3af'];
                        @endphp
                        <div style="padding: 12px 20px; border-bottom: 1px solid rgba(255,255,255,0.04);
                                    display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 13px; color: white; font-weight: 600;">
                                    <span style="color: {{ $info[1] }};">{{ $info[0] }}</span>
                                </div>
                                <div style="font-size: 11px; color: #9ca3af; margin-top: 2px;">
                                    {{ $p->created_at->format('d/m/Y à H:i') }}
                                </div>
                            </div>
                            <div style="text-align: right;">
                                @if ($a['heure_theorique'] !== null && $a['ecart_minutes'] !== 0)
                                    <span style="font-size: 12px; font-weight: 600;
                                                 color: {{ $a['is_retard'] ? '#fde68a' : ($a['is_depart_anticipe'] ? '#fca5a5' : '#6ee7b7') }};
                                                 font-variant-numeric: tabular-nums;">
                                        {{ $a['ecart_minutes'] > 0 ? '+' : '' }}{{ $a['ecart_minutes'] }} min
                                    </span>
                                @else
                                    <span style="font-size: 12px; color: #6ee7b7;">✓</span>
                                @endif
                                @if ($p->manuel)
                                    <div style="font-size: 10px; color: #fde68a; margin-top: 2px;">manuel</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Demandes d'absence --}}
        <div class="glass" style="padding: 0; border-radius: 16px; overflow: hidden;">
            <div style="padding: 16px 20px; border-bottom: 1px solid rgba(255,255,255,0.06);
                        display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 14px; color: white; font-weight: 600;">
                    Demandes d'absence
                </h3>
                <span style="font-size: 12px; color: #9ca3af;">5 plus récentes</span>
            </div>
            @php
                $recentDemandes = \App\Models\DemandeAbsence::where('employe_id', $profile->id)
                    ->orderByDesc('created_at')
                    ->limit(5)
                    ->get();
            @endphp
            @if ($recentDemandes->isEmpty())
                <div style="padding: 30px 20px; text-align: center; color: #6b7280; font-size: 13px;">
                    Aucune demande déposée
                </div>
            @else
                <div style="max-height: 360px; overflow-y: auto;">
                    @foreach ($recentDemandes as $d)
                        <div style="padding: 12px 20px; border-bottom: 1px solid rgba(255,255,255,0.04);">
                            <div style="display: flex; justify-content: space-between; align-items: start; gap: 12px;">
                                <div style="flex: 1;">
                                    <div style="font-size: 13px; color: white; font-weight: 600;">
                                        Du {{ $d->date_debut->format('d/m/Y') }}
                                        au {{ $d->date_fin->format('d/m/Y') }}
                                    </div>
                                    <div style="font-size: 11px; color: #9ca3af; margin-top: 2px;">
                                        {{ \Illuminate\Support\Str::limit($d->motif, 60) }}
                                    </div>
                                </div>
                                <div>
                                    @if ($d->statut === 'en_attente')
                                        <span style="display: inline-block; padding: 3px 8px; border-radius: 999px;
                                                     background: rgba(245,158,11,0.12); border: 1px solid rgba(245,158,11,0.3);
                                                     color: #fde68a; font-size: 10px; font-weight: 600;">
                                            En attente
                                        </span>
                                    @elseif ($d->statut === 'validee')
                                        <span style="display: inline-block; padding: 3px 8px; border-radius: 999px;
                                                     background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3);
                                                     color: #6ee7b7; font-size: 10px; font-weight: 600;">
                                            Validée
                                        </span>
                                    @else
                                        <span style="display: inline-block; padding: 3px 8px; border-radius: 999px;
                                                     background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3);
                                                     color: #fca5a5; font-size: 10px; font-weight: 600;">
                                            Refusée
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
