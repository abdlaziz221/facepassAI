<x-app-layout>
    <x-slot name="header">
        <div style="display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <span class="pill">Gestion</span>
                <h1 style="font-size: 28px; font-weight: 800; color: white; letter-spacing: -0.02em; margin: 12px 0 4px;">
                    Demandes d'absence en attente
                </h1>
                <p class="text-soft" style="margin: 0;">
                    {{ $demandes->total() }} demande(s) en attente de validation.
                </p>
            </div>
        </div>
    </x-slot>

    {{-- Flash --}}
    @if (session('success'))
        <div style="margin-bottom: 20px; padding: 14px 18px; background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); border-radius: 10px; color: #6ee7b7; font-size: 14px;">
            ✓ {{ session('success') }}
        </div>
    @endif
    @if ($errors->has('statut'))
        <div style="margin-bottom: 20px; padding: 14px 18px; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: 10px; color: #fca5a5; font-size: 14px;">
            ⚠ {{ $errors->first('statut') }}
        </div>
    @endif

    {{-- Filtres --}}
    <form method="GET" action="{{ route('demandes-absence.index') }}"
          style="display: flex; flex-wrap: wrap; gap: 12px; align-items: end; margin-bottom: 24px;
                 padding: 16px; background: rgba(255,255,255,0.03);
                 border: 1px solid rgba(255,255,255,0.08); border-radius: 12px;">
        <div style="flex: 1; min-width: 200px;">
            <label style="display: block; font-size: 12px; color: #9ca3af; margin-bottom: 6px;">Employé</label>
            <select name="employe_id"
                    style="width: 100%; padding: 9px 12px; background: rgba(0,0,0,0.4);
                           border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
                           color: white; font-size: 14px;">
                <option value="">— Tous —</option>
                @foreach ($employes as $emp)
                    <option value="{{ $emp->id }}" {{ (int) request('employe_id') === $emp->id ? 'selected' : '' }}>
                        {{ $emp->user->name ?? '#' . $emp->id }}
                    </option>
                @endforeach
            </select>
        </div>
        <div style="min-width: 180px;">
            <label style="display: block; font-size: 12px; color: #9ca3af; margin-bottom: 6px;">Date couverte</label>
            <input type="date" name="date" value="{{ request('date') }}"
                   style="width: 100%; padding: 9px 12px; background: rgba(0,0,0,0.4);
                          border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
                          color: white; font-size: 14px;">
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="submit"
                    style="padding: 10px 18px; background: linear-gradient(135deg, #6366f1, #8b5cf6);
                           border: none; border-radius: 8px; color: white; font-size: 14px;
                           font-weight: 600; cursor: pointer;">
                Filtrer
            </button>
            @if (request('employe_id') || request('date'))
                <a href="{{ route('demandes-absence.index') }}"
                   style="padding: 10px 14px; background: transparent; border: 1px solid rgba(255,255,255,0.15);
                          border-radius: 8px; color: #9ca3af; text-decoration: none; font-size: 14px;">
                    Réinitialiser
                </a>
            @endif
        </div>
    </form>

    {{-- Tableau --}}
    <div x-data="{
            showRefuse: false,
            refuseDemandeId: null,
            refuseEmpName: '',
            openRefuse(id, name) {
                this.refuseDemandeId = id;
                this.refuseEmpName = name;
                this.showRefuse = true;
            }
         }"
         style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
                border-radius: 12px; overflow: hidden;">

        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: rgba(255,255,255,0.04);">
                <tr>
                    <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Employé</th>
                    <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Période</th>
                    <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Motif</th>
                    <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Déposée le</th>
                    <th style="text-align: right; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($demandes as $demande)
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                        <td style="padding: 14px 16px; color: white; font-size: 14px; font-weight: 500;">
                            {{ $demande->employe->user->name ?? '—' }}
                        </td>
                        <td style="padding: 14px 16px; color: #d1d5db; font-size: 14px;">
                            <div style="font-weight: 500;">
                                {{ $demande->date_debut->format('d/m/Y') }}
                                <span style="color: #6b7280;">→</span>
                                {{ $demande->date_fin->format('d/m/Y') }}
                            </div>
                            <div style="font-size: 12px; color: #9ca3af; margin-top: 2px;">
                                {{ $demande->date_debut->diffInDays($demande->date_fin) + 1 }} jour(s)
                            </div>
                        </td>
                        <td style="padding: 14px 16px; color: #d1d5db; font-size: 13px; max-width: 280px;">
                            {{ \Illuminate\Support\Str::limit($demande->motif, 90) }}
                        </td>
                        <td style="padding: 14px 16px; color: #9ca3af; font-size: 13px;">
                            {{ $demande->created_at->format('d/m/Y') }}
                            <div style="font-size: 11px; color: #6b7280;">
                                {{ $demande->created_at->diffForHumans() }}
                            </div>
                        </td>
                        <td style="padding: 14px 16px; text-align: right;">
                            <div style="display: inline-flex; gap: 6px;">
                                <a href="{{ route('demandes-absence.show', $demande) }}"
                                   style="padding: 6px 12px; background: rgba(99,102,241,0.12);
                                          border: 1px solid rgba(99,102,241,0.25); border-radius: 6px;
                                          color: #a5b4fc; font-size: 12px; text-decoration: none;
                                          font-weight: 500;">
                                    Voir
                                </a>
                                @can('absences.validate')
                                    <form method="POST" action="{{ route('demandes-absence.valider', $demande) }}"
                                          style="display: inline;"
                                          onsubmit="return confirm('Valider la demande de {{ $demande->employe->user->name ?? '' }} ?');">
                                        @csrf
                                        <button type="submit"
                                                style="padding: 6px 12px; background: rgba(16,185,129,0.12);
                                                       border: 1px solid rgba(16,185,129,0.25); border-radius: 6px;
                                                       color: #6ee7b7; font-size: 12px; font-weight: 500;
                                                       cursor: pointer;">
                                            ✓ Valider
                                        </button>
                                    </form>
                                    <button type="button"
                                            @click="openRefuse({{ $demande->id }}, '{{ addslashes($demande->employe->user->name ?? '') }}')"
                                            style="padding: 6px 12px; background: rgba(239,68,68,0.12);
                                                   border: 1px solid rgba(239,68,68,0.25); border-radius: 6px;
                                                   color: #fca5a5; font-size: 12px; font-weight: 500;
                                                   cursor: pointer;">
                                        ✕ Refuser
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding: 40px 16px; text-align: center; color: #6b7280; font-size: 14px;">
                            Aucune demande en attente
                            @if (request('employe_id') || request('date'))
                                pour ce filtre.
                                <a href="{{ route('demandes-absence.index') }}" style="color: #818cf8;">Réinitialiser</a>
                            @else
                                pour le moment.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Modal Refuser --}}
        <div x-show="showRefuse"
             x-cloak
             @keydown.escape.window="showRefuse = false"
             style="position: fixed; inset: 0; z-index: 100; display: flex; align-items: center; justify-content: center;
                    background: rgba(0,0,0,0.7); backdrop-filter: blur(4px);">
            <div @click.outside="showRefuse = false"
                 style="background: #0f111a; border: 1px solid rgba(239,68,68,0.3); border-radius: 14px;
                        padding: 24px; width: min(520px, 90vw);">
                <h3 style="font-size: 18px; font-weight: 700; color: white; margin: 0 0 8px;">
                    Refuser la demande
                </h3>
                <p style="color: #9ca3af; font-size: 14px; margin: 0 0 18px;">
                    Demande de <span x-text="refuseEmpName" style="color: white; font-weight: 600;"></span>.
                    Veuillez justifier le refus — l'employé sera informé.
                </p>
                <form method="POST" :action="`/demandes-absence/${refuseDemandeId}/refuser`">
                    @csrf
                    <label style="display: block; font-size: 13px; color: #9ca3af; margin-bottom: 6px;">
                        Justification (5 caractères min, 500 max)
                    </label>
                    <textarea name="commentaire" rows="4" required minlength="5" maxlength="500"
                              placeholder="Ex : déjà 3 personnes absentes sur cette période..."
                              style="width: 100%; padding: 10px 12px; background: rgba(0,0,0,0.4);
                                     border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
                                     color: white; font-size: 14px; resize: vertical; font-family: inherit;"></textarea>
                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 16px;">
                        <button type="button" @click="showRefuse = false"
                                style="padding: 9px 16px; background: transparent;
                                       border: 1px solid rgba(255,255,255,0.15); border-radius: 8px;
                                       color: #9ca3af; font-size: 14px; cursor: pointer;">
                            Annuler
                        </button>
                        <button type="submit"
                                style="padding: 9px 18px; background: linear-gradient(135deg, #ef4444, #f97316);
                                       border: none; border-radius: 8px; color: white; font-size: 14px;
                                       font-weight: 600; cursor: pointer;">
                            Confirmer le refus
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Pagination --}}
    @if ($demandes->hasPages())
        <div style="margin-top: 20px;">
            {{ $demandes->links() }}
        </div>
    @endif
</x-app-layout>
