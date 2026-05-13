<x-app-layout>
    <x-slot name="header">
        <div style="display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <span class="pill" style="background: rgba(239,68,68,0.12);
                      border-color: rgba(239,68,68,0.25); color: #fca5a5;">
                    Administration
                </span>
                <h1 style="font-size: 28px; font-weight: 800; color: white; letter-spacing: -0.02em; margin: 12px 0 4px;">
                    Journal d'activité
                </h1>
                <p class="text-soft" style="margin: 0;">
                    {{ $logs->total() }} événement(s) enregistré(s) — toutes les actions sur les données métier.
                </p>
            </div>

            {{-- Boutons d'export (carte 8 + 9) --}}
            <div style="display: flex; gap: 8px; align-items: end;">
                @php $params = request()->query(); @endphp
                <a href="{{ route('admin.logs.export', array_merge($params, ['format' => 'csv'])) }}"
                   style="padding: 10px 14px; background: rgba(16,185,129,0.1);
                          border: 1px solid rgba(16,185,129,0.3); border-radius: 8px;
                          color: #6ee7b7; font-size: 13px; font-weight: 600;
                          text-decoration: none;">📊 CSV</a>
                <a href="{{ route('admin.logs.export', array_merge($params, ['format' => 'pdf'])) }}"
                   style="padding: 10px 14px; background: rgba(239,68,68,0.1);
                          border: 1px solid rgba(239,68,68,0.3); border-radius: 8px;
                          color: #fca5a5; font-size: 13px; font-weight: 600;
                          text-decoration: none;">📄 PDF</a>
                <a href="{{ route('admin.logs.export', array_merge($params, ['format' => 'txt'])) }}"
                   style="padding: 10px 14px; background: rgba(99,102,241,0.1);
                          border: 1px solid rgba(99,102,241,0.3); border-radius: 8px;
                          color: #a5b4fc; font-size: 13px; font-weight: 600;
                          text-decoration: none;">📝 TXT</a>
            </div>
        </div>
    </x-slot>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('admin.logs.index') }}"
          style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                 gap: 12px; align-items: end; margin-bottom: 24px;
                 padding: 16px; background: rgba(255,255,255,0.03);
                 border: 1px solid rgba(255,255,255,0.08); border-radius: 12px;">

        <div>
            <label style="display: block; font-size: 12px; color: #9ca3af; margin-bottom: 6px;">Du</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                   style="width: 100%; padding: 9px 12px; background: rgba(0,0,0,0.4);
                          border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
                          color: white; font-size: 14px;">
        </div>
        <div>
            <label style="display: block; font-size: 12px; color: #9ca3af; margin-bottom: 6px;">Au</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                   style="width: 100%; padding: 9px 12px; background: rgba(0,0,0,0.4);
                          border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
                          color: white; font-size: 14px;">
        </div>
        <div>
            <label style="display: block; font-size: 12px; color: #9ca3af; margin-bottom: 6px;">Utilisateur</label>
            <select name="causer_id"
                    style="width: 100%; padding: 9px 12px; background: rgba(0,0,0,0.4);
                           border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
                           color: white; font-size: 14px;">
                <option value="">— Tous —</option>
                @foreach ($causers as $u)
                    <option value="{{ $u->id }}" {{ (int) request('causer_id') === $u->id ? 'selected' : '' }}>
                        {{ $u->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="display: block; font-size: 12px; color: #9ca3af; margin-bottom: 6px;">Module</label>
            <select name="log_name"
                    style="width: 100%; padding: 9px 12px; background: rgba(0,0,0,0.4);
                           border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
                           color: white; font-size: 14px;">
                <option value="">— Tous —</option>
                @foreach ($logNames as $name)
                    <option value="{{ $name }}" {{ request('log_name') === $name ? 'selected' : '' }}>
                        {{ ucfirst($name) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="display: block; font-size: 12px; color: #9ca3af; margin-bottom: 6px;">Action</label>
            <select name="action"
                    style="width: 100%; padding: 9px 12px; background: rgba(0,0,0,0.4);
                           border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
                           color: white; font-size: 14px;">
                <option value="">— Toutes —</option>
                @foreach ($actions as $act)
                    <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>
                        {{ ucfirst($act) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="submit"
                    style="padding: 10px 16px; background: linear-gradient(135deg, #6366f1, #8b5cf6);
                           border: none; border-radius: 8px; color: white; font-size: 14px;
                           font-weight: 600; cursor: pointer; white-space: nowrap;">
                Filtrer
            </button>
            @if (array_filter(request()->only(['date_from', 'date_to', 'causer_id', 'log_name', 'action'])))
                <a href="{{ route('admin.logs.index') }}"
                   style="padding: 10px 12px; background: transparent;
                          border: 1px solid rgba(255,255,255,0.15); border-radius: 8px;
                          color: #9ca3af; text-decoration: none; font-size: 13px;
                          display: inline-flex; align-items: center;">Reset</a>
            @endif
        </div>
    </form>

    {{-- Tableau + modale détail --}}
    <div x-data="{ open: false, log: null,
                   show(l) { this.log = l; this.open = true; } }"
         style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
                border-radius: 12px; overflow: hidden;">

        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: rgba(255,255,255,0.04);">
                <tr>
                    <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Date / Heure</th>
                    <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Utilisateur</th>
                    <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Module</th>
                    <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Action</th>
                    <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Cible</th>
                    <th style="text-align: right; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                        <td style="padding: 12px 16px; color: #d1d5db; font-size: 13px;
                                   font-variant-numeric: tabular-nums;">
                            {{ $log->created_at->format('d/m/Y H:i:s') }}
                            <div style="font-size: 11px; color: #6b7280;">
                                {{ $log->created_at->diffForHumans() }}
                            </div>
                        </td>
                        <td style="padding: 12px 16px; color: white; font-size: 13px;">
                            {{ $log->causer?->name ?? 'Système' }}
                        </td>
                        <td style="padding: 12px 16px;">
                            <span style="display: inline-block; padding: 3px 8px; border-radius: 6px;
                                         background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.25);
                                         color: #a5b4fc; font-size: 11px; font-weight: 600;">
                                {{ $log->log_name ?? '—' }}
                            </span>
                        </td>
                        <td style="padding: 12px 16px; color: #d1d5db; font-size: 13px;">
                            {{ $log->description }}
                        </td>
                        <td style="padding: 12px 16px; color: #9ca3af; font-size: 12px;">
                            {{ class_basename((string) $log->subject_type) }} #{{ $log->subject_id }}
                        </td>
                        <td style="padding: 12px 16px; text-align: right;">
                            <button type="button"
                                    @click="show({{ json_encode([
                                        'date'        => $log->created_at->format('d/m/Y H:i:s'),
                                        'causer'      => $log->causer?->name ?? 'Système',
                                        'log_name'    => $log->log_name,
                                        'description' => $log->description,
                                        'subject'     => class_basename((string) $log->subject_type) . ' #' . $log->subject_id,
                                        'properties'  => $log->properties,
                                    ]) }})"
                                    style="padding: 6px 10px; background: rgba(99,102,241,0.12);
                                           border: 1px solid rgba(99,102,241,0.25); border-radius: 6px;
                                           color: #a5b4fc; font-size: 12px; cursor: pointer;">
                                Détails
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding: 40px 16px; text-align: center; color: #6b7280; font-size: 14px;">
                            Aucun événement
                            @if (array_filter(request()->only(['date_from', 'date_to', 'causer_id', 'log_name', 'action'])))
                                pour ces filtres.
                            @else
                                enregistré pour le moment.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Modale Alpine.js : détail d'un log --}}
        <div x-show="open"
             x-cloak
             @keydown.escape.window="open = false"
             style="position: fixed; inset: 0; z-index: 100; display: flex;
                    align-items: center; justify-content: center;
                    background: rgba(0,0,0,0.7); backdrop-filter: blur(4px);">
            <div @click.outside="open = false"
                 style="background: #0f111a; border: 1px solid rgba(255,255,255,0.1);
                        border-radius: 14px; padding: 24px; width: min(720px, 90vw);
                        max-height: 80vh; overflow-y: auto;">
                <h3 style="margin: 0 0 16px; color: white; font-size: 18px; font-weight: 700;">
                    Détail de l'événement
                </h3>
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
                    <tr>
                        <td style="padding: 8px; color: #9ca3af; font-size: 12px;
                                   text-transform: uppercase; letter-spacing: 0.05em; width: 100px;">Date</td>
                        <td style="padding: 8px; color: white; font-size: 14px;" x-text="log?.date"></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; color: #9ca3af; font-size: 12px;
                                   text-transform: uppercase; letter-spacing: 0.05em;">Par</td>
                        <td style="padding: 8px; color: white; font-size: 14px;" x-text="log?.causer"></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; color: #9ca3af; font-size: 12px;
                                   text-transform: uppercase; letter-spacing: 0.05em;">Module</td>
                        <td style="padding: 8px; color: white; font-size: 14px;" x-text="log?.log_name"></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; color: #9ca3af; font-size: 12px;
                                   text-transform: uppercase; letter-spacing: 0.05em;">Action</td>
                        <td style="padding: 8px; color: white; font-size: 14px;" x-text="log?.description"></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; color: #9ca3af; font-size: 12px;
                                   text-transform: uppercase; letter-spacing: 0.05em;">Cible</td>
                        <td style="padding: 8px; color: white; font-size: 14px;" x-text="log?.subject"></td>
                    </tr>
                </table>
                <div>
                    <div style="font-size: 12px; color: #9ca3af; text-transform: uppercase;
                                letter-spacing: 0.05em; margin-bottom: 6px;">Modifications</div>
                    <pre style="background: rgba(0,0,0,0.4); padding: 12px;
                                border: 1px solid rgba(255,255,255,0.08); border-radius: 8px;
                                color: #e5e7eb; font-size: 12px; overflow-x: auto; max-height: 240px;
                                white-space: pre-wrap;"
                         x-text="JSON.stringify(log?.properties, null, 2)"></pre>
                </div>
                <div style="display: flex; justify-content: flex-end; margin-top: 18px;">
                    <button type="button" @click="open = false"
                            style="padding: 9px 18px; background: linear-gradient(135deg, #6366f1, #8b5cf6);
                                   border: none; border-radius: 8px; color: white; font-size: 14px;
                                   font-weight: 600; cursor: pointer;">
                        Fermer
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if ($logs->hasPages())
        <div style="margin-top: 20px;">{{ $logs->links() }}</div>
    @endif
</x-app-layout>
