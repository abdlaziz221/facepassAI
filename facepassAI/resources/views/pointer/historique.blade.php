<x-app-layout>
    <x-slot name="header">
        <div style="display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <span class="pill">Consultations</span>
                <h1 style="font-size: 28px; font-weight: 800; color: white; letter-spacing: -0.02em; margin: 12px 0 4px;">
                    Historique des pointages
                </h1>
                <p class="text-soft" style="margin: 0;">
                    {{ $pointages->total() }} pointage(s) — filtrable par employé, période et type.
                </p>
            </div>
        </div>
    </x-slot>

    {{-- KPIs synthétiques par type --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 24px;">
        <div class="card card-stat" style="padding: 14px 16px;">
            <div class="label">Arrivées</div>
            <div class="value" style="font-size: 24px;
                background: linear-gradient(135deg, #6ee7b7, #10b981);
                -webkit-background-clip: text; background-clip: text; color: transparent;">
                {{ $counts['arrivee'] }}
            </div>
        </div>
        <div class="card card-stat" style="padding: 14px 16px;">
            <div class="label">Début de pause</div>
            <div class="value" style="font-size: 24px; color: #fde68a;">
                {{ $counts['debut_pause'] }}
            </div>
        </div>
        <div class="card card-stat" style="padding: 14px 16px;">
            <div class="label">Fin de pause</div>
            <div class="value" style="font-size: 24px; color: #67e8f9;">
                {{ $counts['fin_pause'] }}
            </div>
        </div>
        <div class="card card-stat" style="padding: 14px 16px;">
            <div class="label">Départs</div>
            <div class="value" style="font-size: 24px;
                background: linear-gradient(135deg, #c4b5fd, #8b5cf6);
                -webkit-background-clip: text; background-clip: text; color: transparent;">
                {{ $counts['depart'] }}
            </div>
        </div>
    </div>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('pointages.historique') }}"
          style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                 gap: 12px; align-items: end; margin-bottom: 24px;
                 padding: 16px; background: rgba(255,255,255,0.03);
                 border: 1px solid rgba(255,255,255,0.08); border-radius: 12px;">

        {{-- Employé --}}
        <div>
            <label style="display: block; font-size: 12px; color: #9ca3af; margin-bottom: 6px;">Employé</label>
            <select name="employe_id"
                    style="width: 100%; padding: 9px 12px; background: rgba(0,0,0,0.4);
                           border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
                           color: white; font-size: 14px;">
                <option value="">— Tous —</option>
                @foreach ($employes as $emp)
                    <option value="{{ $emp->id }}" {{ (int) ($filters['employe_id'] ?? 0) === $emp->id ? 'selected' : '' }}>
                        {{ $emp->user->name ?? ('#' . $emp->id) }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Date de --}}
        <div>
            <label style="display: block; font-size: 12px; color: #9ca3af; margin-bottom: 6px;">Du</label>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                   style="width: 100%; padding: 9px 12px; background: rgba(0,0,0,0.4);
                          border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
                          color: white; font-size: 14px;">
        </div>

        {{-- Date à --}}
        <div>
            <label style="display: block; font-size: 12px; color: #9ca3af; margin-bottom: 6px;">Au</label>
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                   style="width: 100%; padding: 9px 12px; background: rgba(0,0,0,0.4);
                          border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
                          color: white; font-size: 14px;">
        </div>

        {{-- Type --}}
        <div>
            <label style="display: block; font-size: 12px; color: #9ca3af; margin-bottom: 6px;">Type</label>
            <select name="type"
                    style="width: 100%; padding: 9px 12px; background: rgba(0,0,0,0.4);
                           border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
                           color: white; font-size: 14px;">
                <option value="">— Tous —</option>
                <option value="arrivee"     {{ ($filters['type'] ?? '') === 'arrivee'     ? 'selected' : '' }}>Arrivée</option>
                <option value="debut_pause" {{ ($filters['type'] ?? '') === 'debut_pause' ? 'selected' : '' }}>Début de pause</option>
                <option value="fin_pause"   {{ ($filters['type'] ?? '') === 'fin_pause'   ? 'selected' : '' }}>Fin de pause</option>
                <option value="depart"      {{ ($filters['type'] ?? '') === 'depart'      ? 'selected' : '' }}>Départ</option>
            </select>
        </div>

        {{-- Saisie --}}
        <div>
            <label style="display: block; font-size: 12px; color: #9ca3af; margin-bottom: 6px;">Saisie</label>
            <select name="manuel"
                    style="width: 100%; padding: 9px 12px; background: rgba(0,0,0,0.4);
                           border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
                           color: white; font-size: 14px;">
                <option value="">— Toutes —</option>
                <option value="0" {{ ($filters['manuel'] ?? '') === '0' ? 'selected' : '' }}>Biométrique</option>
                <option value="1" {{ ($filters['manuel'] ?? '') === '1' ? 'selected' : '' }}>Manuelle</option>
            </select>
        </div>

        <div style="display: flex; gap: 8px;" x-data="{ copied: false }">
            <button type="submit"
                    style="padding: 10px 16px; background: linear-gradient(135deg, #6366f1, #8b5cf6);
                           border: none; border-radius: 8px; color: white; font-size: 14px;
                           font-weight: 600; cursor: pointer; white-space: nowrap;">
                Filtrer
            </button>
            @if (array_filter($filters))
                <a href="{{ route('pointages.historique') }}"
                   style="padding: 10px 12px; background: transparent; border: 1px solid rgba(255,255,255,0.15);
                          border-radius: 8px; color: #9ca3af; text-decoration: none; font-size: 13px;
                          display: inline-flex; align-items: center;">
                    Reset
                </a>
                {{-- Sprint 5 carte 2 (US-061) — Copier l'URL avec les filtres --}}
                <button type="button"
                        @click="navigator.clipboard.writeText(window.location.href).then(() => { copied = true; setTimeout(() => copied = false, 2000); })"
                        title="Copier l'URL avec ces filtres dans le presse-papiers"
                        style="padding: 10px 12px; background: rgba(99,102,241,0.1);
                               border: 1px solid rgba(99,102,241,0.25); border-radius: 8px;
                               color: #a5b4fc; font-size: 13px; cursor: pointer;
                               display: inline-flex; align-items: center; gap: 6px;">
                    <span x-show="!copied">📋 Copier le lien</span>
                    <span x-show="copied" x-cloak style="color: #6ee7b7;">✓ Copié !</span>
                </button>
            @endif
        </div>
    </form>

    {{-- Helper pour les liens de tri --}}
    @php
        $sortLink = function (string $column, string $label) use ($sortBy, $sortDir, $filters) {
            $newDir = ($sortBy === $column && $sortDir === 'desc') ? 'asc' : 'desc';
            $params = array_merge(array_filter($filters, fn ($v) => $v !== null && $v !== ''), [
                'sort' => $column,
                'dir'  => $newDir,
            ]);
            $url = route('pointages.historique', $params);
            $arrow = $sortBy === $column ? ($sortDir === 'desc' ? ' ↓' : ' ↑') : '';
            $color = $sortBy === $column ? 'white' : '#9ca3af';
            return "<a href=\"$url\" style=\"color: $color; text-decoration: none;\">$label$arrow</a>";
        };
    @endphp

    {{-- Tableau --}}
    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
                border-radius: 12px; overflow: hidden;">

        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: rgba(255,255,255,0.04);">
                <tr>
                    <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">
                        {!! $sortLink('employe_id', 'Employé') !!}
                    </th>
                    <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">
                        {!! $sortLink('type', 'Type') !!}
                    </th>
                    <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">
                        {!! $sortLink('created_at', 'Date / Heure') !!}
                    </th>
                    <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Saisie</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pointages as $p)
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                        <td style="padding: 14px 16px; color: white; font-size: 14px; font-weight: 500;">
                            {{ $p->employe->user->name ?? '#' . $p->employe_id }}
                            @if ($p->employe->matricule ?? false)
                                <div style="font-size: 11px; color: #6b7280;">{{ $p->employe->matricule }}</div>
                            @endif
                        </td>
                        <td style="padding: 14px 16px;">
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
                        <td style="padding: 14px 16px; color: #d1d5db; font-size: 14px;">
                            {{ $p->created_at->format('d/m/Y') }}
                            <span style="color: #9ca3af;">à</span>
                            <span style="font-variant-numeric: tabular-nums; color: white; font-weight: 600;">
                                {{ $p->created_at->format('H:i') }}
                            </span>
                            <div style="font-size: 11px; color: #6b7280;">
                                {{ $p->created_at->diffForHumans() }}
                            </div>
                        </td>
                        <td style="padding: 14px 16px;">
                            @if ($p->manuel)
                                <span style="display: inline-block; padding: 3px 8px; border-radius: 6px;
                                             background: rgba(245,158,11,0.12); border: 1px solid rgba(245,158,11,0.25);
                                             color: #fde68a; font-size: 11px; font-weight: 600;"
                                      title="{{ $p->motif_manuel }}">
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
                        <td colspan="4" style="padding: 40px 16px; text-align: center; color: #6b7280; font-size: 14px;">
                            Aucun pointage
                            @if (array_filter($filters))
                                pour ces filtres.
                                <a href="{{ route('pointages.historique') }}" style="color: #818cf8;">Réinitialiser</a>
                            @else
                                enregistré pour le moment.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($pointages->hasPages())
        <div style="margin-top: 20px;">
            {{ $pointages->links() }}
        </div>
    @endif
</x-app-layout>
