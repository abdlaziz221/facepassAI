<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('admin.horaires.edit') }}" class="link-muted" style="font-size: 13px;">← Configuration des horaires</a>
        <h1 style="font-size: 28px; font-weight: 800; color: white; letter-spacing: -0.02em; margin: 8px 0 4px;">
            Jours fériés et exceptions
        </h1>
        <p class="text-soft" style="margin: 0;">
            Gestion des dates exceptionnelles (fériés nationaux, ponts, fermetures).
        </p>
    </x-slot>

    <div style="display: flex; gap: 24px; flex-wrap: wrap;">
        {{-- Formulaire d'ajout --}}
        <div class="glass" style="padding: 28px; border-radius: 16px; flex: 1; min-width: 320px;">
            <h2 style="font-size: 16px; font-weight: 600; color: white; margin: 0 0 16px 0;">
                Ajouter une exception
            </h2>

            @if (session('success'))
                <div style="margin-bottom: 16px; padding: 10px 14px; background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); border-radius: 8px; color: #6ee7b7; font-size: 13px;">
                    ✓ {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.jours-feries.store') }}" style="display: flex; flex-direction: column; gap: 14px;">
                @csrf
                <div>
                    <label style="display: block; font-size: 13px; color: #9ca3af; margin-bottom: 6px;">Date</label>
                    <input type="date" name="date" value="{{ old('date') }}" required class="input-dark">
                    @error('date') <p class="error-text">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #9ca3af; margin-bottom: 6px;">
                        Libellé <span style="color: #6b7280;">(optionnel)</span>
                    </label>
                    <input type="text" name="libelle" value="{{ old('libelle') }}" maxlength="100"
                           placeholder="Ex. : Fête nationale, Pont, Fermeture annuelle"
                           class="input-dark">
                    @error('libelle') <p class="error-text">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="btn-primary" style="margin-top: 4px;">
                    Ajouter
                </button>
            </form>
        </div>

        {{-- Liste des jours fériés --}}
        <div class="glass" style="padding: 28px; border-radius: 16px; flex: 1.5; min-width: 360px;">
            <h2 style="font-size: 16px; font-weight: 600; color: white; margin: 0 0 16px 0;">
                Jours fériés enregistrés <span style="color: #6b7280; font-weight: 400;">({{ $feries->count() }})</span>
            </h2>

            @if ($feries->isEmpty())
                <p style="text-align: center; padding: 32px 16px; color: #6b7280; font-size: 14px;">
                    Aucun jour férié enregistré pour le moment.
                </p>
            @else
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    @foreach ($feries as $jour)
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px;">
                            <div>
                                <div style="font-weight: 500; color: white; font-size: 14px;">
                                    {{ $jour->date->format('d/m/Y') }}
                                    <span style="color: #6b7280; font-size: 12px; font-weight: 400;">
                                        ({{ ucfirst($jour->date->locale('fr')->dayName) }})
                                    </span>
                                </div>
                                @if ($jour->libelle)
                                    <div style="color: #9ca3af; font-size: 13px; margin-top: 2px;">
                                        {{ $jour->libelle }}
                                    </div>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('admin.jours-feries.destroy', $jour) }}"
                                  onsubmit="return confirm('Supprimer ce jour férié ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        style="padding: 6px 10px; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5; border-radius: 6px; cursor: pointer; font-size: 12px;">
                                    Supprimer
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
