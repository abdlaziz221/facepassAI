<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('demandes-absence.index') }}"
               style="display: inline-flex; align-items: center; gap: 6px; color: #9ca3af;
                      font-size: 13px; text-decoration: none; margin-bottom: 8px;">
                ← Retour à la liste
            </a>
            <h1 style="font-size: 28px; font-weight: 800; color: white; letter-spacing: -0.02em; margin: 0 0 4px;">
                Demande d'absence #{{ $demande->id }}
            </h1>
            <p style="margin: 0;">
                @if ($demande->statut === 'en_attente')
                    <span class="pill" style="background: rgba(245,158,11,0.12);
                          border-color: rgba(245,158,11,0.25); color: #fde68a;">En attente</span>
                @elseif ($demande->statut === 'validee')
                    <span class="pill pill-success">Validée</span>
                @else
                    <span class="pill" style="background: rgba(239,68,68,0.12);
                          border-color: rgba(239,68,68,0.25); color: #fca5a5;">Refusée</span>
                @endif
            </p>
        </div>
    </x-slot>

    @if ($errors->any())
        <div style="margin-bottom: 20px; padding: 14px 18px; background: rgba(239,68,68,0.1);
                    border: 1px solid rgba(239,68,68,0.3); border-radius: 10px;
                    color: #fca5a5; font-size: 14px;">
            @foreach ($errors->all() as $error)
                <div>⚠ {{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- Détails --}}
    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
                border-radius: 12px; padding: 24px; margin-bottom: 24px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                    gap: 24px;">

            <div>
                <div style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em;
                            color: #6b7280; margin-bottom: 6px;">Employé</div>
                <div style="font-size: 16px; font-weight: 600; color: white;">
                    {{ $demande->employe->user->name ?? '—' }}
                </div>
                @if ($demande->employe->user->email ?? false)
                    <div style="font-size: 13px; color: #9ca3af; margin-top: 2px;">
                        {{ $demande->employe->user->email }}
                    </div>
                @endif
            </div>

            <div>
                <div style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em;
                            color: #6b7280; margin-bottom: 6px;">Période</div>
                <div style="font-size: 16px; font-weight: 600; color: white;">
                    Du {{ $demande->date_debut->format('d/m/Y') }}
                    au {{ $demande->date_fin->format('d/m/Y') }}
                </div>
                <div style="font-size: 13px; color: #9ca3af; margin-top: 2px;">
                    {{ $demande->date_debut->diffInDays($demande->date_fin) + 1 }} jour(s)
                </div>
            </div>

            <div>
                <div style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em;
                            color: #6b7280; margin-bottom: 6px;">Déposée le</div>
                <div style="font-size: 16px; font-weight: 600; color: white;">
                    {{ $demande->created_at->format('d/m/Y à H:i') }}
                </div>
                <div style="font-size: 13px; color: #9ca3af; margin-top: 2px;">
                    {{ $demande->created_at->diffForHumans() }}
                </div>
            </div>
        </div>

        <div style="margin-top: 20px;">
            <div style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em;
                        color: #6b7280; margin-bottom: 6px;">Motif</div>
            <div style="font-size: 14px; color: #e5e7eb; line-height: 1.6;
                        background: rgba(0,0,0,0.25); border-radius: 8px; padding: 12px 14px;
                        border: 1px solid rgba(255,255,255,0.04);">
                {{ $demande->motif }}
            </div>
        </div>

        @if ($demande->commentaire_gestionnaire)
            <div style="margin-top: 20px;">
                <div style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em;
                            color: #6b7280; margin-bottom: 6px;">
                    Commentaire du gestionnaire
                    @if ($demande->gestionnaire)
                        <span style="text-transform: none; color: #818cf8; font-weight: 500;
                                     letter-spacing: 0;">
                            — {{ $demande->gestionnaire->name }}
                        </span>
                    @endif
                </div>
                <div style="font-size: 14px; color: #e5e7eb; line-height: 1.6;
                            background: rgba(0,0,0,0.25); border-radius: 8px; padding: 12px 14px;
                            border: 1px solid rgba(255,255,255,0.04);">
                    {{ $demande->commentaire_gestionnaire }}
                </div>
            </div>
        @endif
    </div>

    {{-- Actions (seulement si en_attente et permission) --}}
    @if ($demande->statut === 'en_attente')
        @can('absences.validate')
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                        gap: 16px;">

                {{-- Valider --}}
                <form method="POST" action="{{ route('demandes-absence.valider', $demande) }}"
                      style="background: rgba(16,185,129,0.06);
                             border: 1px solid rgba(16,185,129,0.25);
                             border-radius: 12px; padding: 20px;">
                    @csrf
                    <h3 style="font-size: 16px; font-weight: 600; color: #6ee7b7; margin: 0 0 12px;">
                        ✓ Valider la demande
                    </h3>
                    <label style="display: block; font-size: 13px; color: #9ca3af; margin-bottom: 6px;">
                        Commentaire (optionnel, 500 max)
                    </label>
                    <textarea name="commentaire" rows="3" maxlength="500"
                              placeholder="Ex : congés validés, bon repos !"
                              style="width: 100%; padding: 10px 12px; background: rgba(0,0,0,0.4);
                                     border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
                                     color: white; font-size: 14px; resize: vertical; font-family: inherit;
                                     margin-bottom: 12px;">{{ old('commentaire') }}</textarea>
                    <button type="submit"
                            style="width: 100%; padding: 10px 16px;
                                   background: linear-gradient(135deg, #10b981, #059669);
                                   border: none; border-radius: 8px; color: white; font-size: 14px;
                                   font-weight: 600; cursor: pointer;">
                        Valider la demande
                    </button>
                </form>

                {{-- Refuser --}}
                <form method="POST" action="{{ route('demandes-absence.refuser', $demande) }}"
                      style="background: rgba(239,68,68,0.06);
                             border: 1px solid rgba(239,68,68,0.25);
                             border-radius: 12px; padding: 20px;">
                    @csrf
                    <h3 style="font-size: 16px; font-weight: 600; color: #fca5a5; margin: 0 0 12px;">
                        ✕ Refuser la demande
                    </h3>
                    <label style="display: block; font-size: 13px; color: #9ca3af; margin-bottom: 6px;">
                        Justification (5 caractères min, 500 max) <span style="color: #fca5a5;">*</span>
                    </label>
                    <textarea name="commentaire" rows="3" required minlength="5" maxlength="500"
                              placeholder="Ex : période non disponible, trop d'absences..."
                              style="width: 100%; padding: 10px 12px; background: rgba(0,0,0,0.4);
                                     border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
                                     color: white; font-size: 14px; resize: vertical; font-family: inherit;
                                     margin-bottom: 12px;">{{ old('commentaire') }}</textarea>
                    <button type="submit"
                            style="width: 100%; padding: 10px 16px;
                                   background: linear-gradient(135deg, #ef4444, #f97316);
                                   border: none; border-radius: 8px; color: white; font-size: 14px;
                                   font-weight: 600; cursor: pointer;">
                        Refuser la demande
                    </button>
                </form>
            </div>
        @endcan
    @else
        <div style="padding: 16px 18px; background: rgba(255,255,255,0.03);
                    border: 1px solid rgba(255,255,255,0.08); border-radius: 10px;
                    color: #9ca3af; font-size: 14px; text-align: center;">
            Cette demande a déjà été traitée — aucune action possible.
        </div>
    @endif
</x-app-layout>
