<x-app-layout>
    <x-slot name="header">
        <div style="display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <span class="pill" style="background: rgba(245,158,11,0.12);
                      border-color: rgba(245,158,11,0.25); color: #fde68a;">
                    Anomalies
                </span>
                <h1 style="font-size: 28px; font-weight: 800; color: white; letter-spacing: -0.02em; margin: 12px 0 4px;">
                    Retards & départs anticipés
                </h1>
                <p class="text-soft" style="margin: 0;">
                    {{ $pointages->total() }} anomalie(s) sur la période — basé sur les horaires configurés.
                </p>
            </div>
            <a href="{{ route('pointages.retards.export', request()->query()) }}"
               style="padding: 10px 18px; background: rgba(99,102,241,0.1);
                      border: 1px solid rgba(99,102,241,0.3); border-radius: 8px;
                      color: #a5b4fc; font-size: 13px; font-weight: 600;
                      text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                ⬇ Export CSV
            </a>
        </div>
    </x-slot>

    {{-- Sprint 5 carte 5 — Avertissement horaires non configurés --}}
    <x-horaires-warning />

    {{-- KPIs --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 16px; margin-bottom: 24px;">
        <div class="card card-stat">
            <div class="label">Retards</div>
            <div class="value" style="background: linear-gradient(135deg, #fde68a, #f59e0b);
                -webkit-background-clip: text; background-clip: text; color: transparent;">
                {{ $countRetards }}
            </div>
            <div class="delta" style="color: #fde68a;">arrivées / retours après l'heure</div>
        </div>
        <div class="card card-stat">
            <div class="label">Départs anticipés</div>
            <div class="value" style="background: linear-gradient(135deg, #fca5a5, #ef4444);
                -webkit-background-clip: text; background-clip: text; color: transparent;">
                {{ $countDeparts }}
            </div>
            <div class="delta" style="color: #fca5a5;">départs / pauses avant l'heure</div>
        </div>
    </div>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('pointages.retards') }}"
          style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                 gap: 12px; align-items: end; margin-bottom: 24px;
                 padding: 16px; background: rgba(255,255,255,0.03);
                 border: 1px solid rgba(255,255,255,0.08); border-radius: 12px;">

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

        <div>
            <label style="display: block; font-size: 12px; color: #9ca3af; margin-bottom: 6px;">Du</label>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                   style="width: 100%; padding: 9px 12px; background: rgba(0,0,0,0.4);
                          border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
                          color: white; font-size: 14px;">
        </div>

        <div>
            <label style="display: block; font-size: 12px; color: #9ca3af; margin-bottom: 6px;">Au</label>
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                   style="width: 100%; padding: 9px 12px; background: rgba(0,0,0,0.4);
                          border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
                          color: white; font-size: 14px;">
        </div>

        <div>
            <label style="display: block; font-size: 12px; color: #9ca3af; margin-bottom: 6px;">Catégorie</label>
            <select name="categorie"
                    style="width: 100%; padding: 9px 12px; background: rgba(0,0,0,0.4);
                           border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
                           color: white; font-size: 14px;">
                <option value="">— Toutes —</option>
                <option value="retard"          {{ ($filters['categorie'] ?? '') === 'retard'          ? 'selected' : '' }}>Retards uniquement</option>
                <option value="depart_anticipe" {{ ($filters['categorie'] ?? '') === 'depart_anticipe' ? 'selected' : '' }}>Départs anticipés uniquement</option>
            </select>
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit"
                    style="padding: 10px 16px; background: linear-gradient(135deg, #6366f1, #8b5cf6);
                           border: none; border-radius: 8px; color: white; font-size: 14px;
                           font-weight: 600; cursor: pointer; white-space: nowrap;">
                Filtrer
            </button>
            @if (array_filter($filters))
                <a href="{{ route('pointages.retards') }}"
                   style="padding: 10px 12px; background: transparent; border: 1px solid rgba(255,255,255,0.15);
                          border-radius: 8px; color: #9ca3af; text-decoration: none; font-size: 13px;
                          display: inline-flex; align-items: center;">
                    Reset
                </a>
            @endif
        </div>
    </form>

    {{-- Tableau --}}
    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
                border-radius: 12px; overflow: hidden;">

        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: rgba(255,255,255,0.04);">
                <tr>
                    <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Employé</th>
                    <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Date</th>
                    <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Type</th>
                    <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Théorique → Réelle</th>
                    <th style="text-align: right; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Écart</th>
                    <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Catégorie</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pointages as $p)
                    @php
                        $a = $p->analyse;
                        $isR = $a['is_retard'];
                        $color = $isR ? '#fde68a' : '#fca5a5';
                        $bgColor = $isR ? 'rgba(245,158,11,0.12)' : 'rgba(239,68,68,0.12)';
                        $bdColor = $isR ? 'rgba(245,158,11,0.3)'  : 'rgba(239,68,68,0.3)';
                    @endphp
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                        <td style="padding: 14px 16px; color: white; font-size: 14px; font-weight: 500;">
                            {{ $p->employe->user->name ?? ('#' . $p->employe_id) }}
                            @if ($p->employe->matricule ?? false)
                                <div style="font-size: 11px; color: #6b7280;">{{ $p->employe->matricule }}</div>
                            @endif
                        </td>
                        <td style="padding: 14px 16px; color: #d1d5db; font-size: 13px;">
                            {{ $p->created_at->format('d/m/Y') }}
                        </td>
                        <td style="padding: 14px 16px; color: #d1d5db; font-size: 13px;">
                            {{ ucfirst(str_replace('_', ' ', $p->type)) }}
                        </td>
                        <td style="padding: 14px 16px; color: #d1d5db; font-size: 14px;
                                   font-variant-numeric: tabular-nums;">
                            <span style="color: #6b7280;">{{ $a['heure_theorique'] }}</span>
                            <span style="color: #6b7280; margin: 0 4px;">→</span>
                            <span style="color: white; font-weight: 600;">{{ $a['heure_reelle'] }}</span>
                        </td>
                        <td style="padding: 14px 16px; text-align: right;
                                   color: {{ $color }}; font-size: 14px; font-weight: 700;
                                   font-variant-numeric: tabular-nums;">
                            @if ($a['ecart_minutes'] > 0)
                                +{{ $a['ecart_minutes'] }} min
                            @else
                                {{ $a['ecart_minutes'] }} min
                            @endif
                        </td>
                        <td style="padding: 14px 16px;">
                            <span style="display: inline-block; padding: 4px 10px; border-radius: 999px;
                                         background: {{ $bgColor }}; border: 1px solid {{ $bdColor }};
                                         color: {{ $color }}; font-size: 12px; font-weight: 600;">
                                {{ $isR ? 'Retard' : 'Départ anticipé' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding: 40px 16px; text-align: center; color: #6b7280; font-size: 14px;">
                            Aucun retard ni départ anticipé
                            @if (array_filter($filters))
                                pour ces filtres.
                                <a href="{{ route('pointages.retards') }}" style="color: #818cf8;">Réinitialiser</a>
                            @else
                                — tout le monde est à l'heure ✨
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
