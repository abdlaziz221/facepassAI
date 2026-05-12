<x-app-layout>
    <x-slot name="header">
        <h1 style="font-size: 28px; font-weight: 800; color: white; letter-spacing: -0.02em; margin: 8px 0 4px;">
            Configuration des horaires
        </h1>
        <p class="text-soft" style="margin: 0;">
            Définissez les jours ouvrables, les horaires de référence et les jours fériés de l'entreprise.
        </p>
    </x-slot>

    <div class="glass" style="padding: 32px; border-radius: 16px; max-width: 760px;">

        @if (session('success'))
            <div style="margin-bottom: 20px; padding: 12px 16px; background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); border-radius: 8px; color: #6ee7b7; font-size: 14px;">
                ✓ {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.horaires.update') }}"
              x-data="horairesForm(@js($config->jours_ouvrables ?? []), @js($config->jours_feries ?? []))"
              style="display: flex; flex-direction: column; gap: 24px;">
            @csrf
            @method('PUT')

            {{-- ============================================================ --}}
            {{-- Jours ouvrables --}}
            {{-- ============================================================ --}}
            <div>
                <label style="display: block; font-size: 14px; font-weight: 600; color: white; margin-bottom: 12px;">
                    Jours ouvrables
                </label>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    @foreach (\App\Models\HoraireConfig::JOURS_VALIDES as $jour)
                        <label style="cursor: pointer;" x-bind:class="jours.includes('{{ $jour }}') ? 'jour-active' : 'jour-inactive'"
                               class="jour-toggle">
                            <input type="checkbox"
                                   name="jours_ouvrables[]"
                                   value="{{ $jour }}"
                                   x-model="jours"
                                   style="display: none;">
                            <span style="padding: 10px 18px; border-radius: 10px; display: inline-block; text-transform: capitalize; font-size: 14px; transition: all 0.15s;"
                                  x-bind:style="jours.includes('{{ $jour }}')
                                      ? 'background: rgba(34,211,238,0.15); border: 1px solid rgba(34,211,238,0.5); color: #67e8f9;'
                                      : 'background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); color: #9ca3af;'">
                                {{ $jour }}
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('jours_ouvrables') <p class="error-text" style="margin-top: 8px;">{{ $message }}</p> @enderror
                @error('jours_ouvrables.*') <p class="error-text" style="margin-top: 8px;">{{ $message }}</p> @enderror
            </div>

            {{-- ============================================================ --}}
            {{-- Heures de référence --}}
            {{-- ============================================================ --}}
            <div>
                <label style="display: block; font-size: 14px; font-weight: 600; color: white; margin-bottom: 12px;">
                    Horaires de référence
                </label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 13px; color: #9ca3af; margin-bottom: 6px;">Arrivée</label>
                        <input type="time" name="heure_arrivee"
                               value="{{ old('heure_arrivee', substr($config->heure_arrivee, 0, 5)) }}"
                               required class="input-dark">
                        @error('heure_arrivee') <p class="error-text">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; color: #9ca3af; margin-bottom: 6px;">Début de pause</label>
                        <input type="time" name="heure_debut_pause"
                               value="{{ old('heure_debut_pause', substr($config->heure_debut_pause, 0, 5)) }}"
                               required class="input-dark">
                        @error('heure_debut_pause') <p class="error-text">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; color: #9ca3af; margin-bottom: 6px;">Fin de pause</label>
                        <input type="time" name="heure_fin_pause"
                               value="{{ old('heure_fin_pause', substr($config->heure_fin_pause, 0, 5)) }}"
                               required class="input-dark">
                        @error('heure_fin_pause') <p class="error-text">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; color: #9ca3af; margin-bottom: 6px;">Départ</label>
                        <input type="time" name="heure_depart"
                               value="{{ old('heure_depart', substr($config->heure_depart, 0, 5)) }}"
                               required class="input-dark">
                        @error('heure_depart') <p class="error-text">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- Jours fériés --}}
            {{-- ============================================================ --}}
            <div>
                <label style="display: block; font-size: 14px; font-weight: 600; color: white; margin-bottom: 12px;">
                    Jours fériés
                </label>

                <template x-for="(date, idx) in feries" :key="idx">
                    <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 8px;">
                        <input type="date"
                               x-bind:name="`jours_feries[${idx}]`"
                               x-model="feries[idx]"
                               class="input-dark"
                               style="flex: 1;">
                        <button type="button" @click="feries.splice(idx, 1)"
                                style="padding: 8px 12px; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5; border-radius: 8px; cursor: pointer; font-size: 13px;">
                            Retirer
                        </button>
                    </div>
                </template>

                <button type="button" @click="feries.push('')"
                        style="margin-top: 8px; padding: 10px 16px; background: rgba(34,211,238,0.1); border: 1px solid rgba(34,211,238,0.3); color: #67e8f9; border-radius: 8px; cursor: pointer; font-size: 13px;">
                    + Ajouter un jour férié
                </button>
                @error('jours_feries.*') <p class="error-text" style="margin-top: 8px;">{{ $message }}</p> @enderror
            </div>

            {{-- ============================================================ --}}
            {{-- Boutons --}}
            {{-- ============================================================ --}}
            <div style="display: flex; gap: 12px; justify-content: flex-end; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.06);">
                <a href="{{ route('dashboard') }}" class="btn-secondary">Annuler</a>
                <button type="submit" class="btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>

    <script>
        function horairesForm(joursInit, feriesInit) {
            return {
                jours: joursInit,
                feries: feriesInit.length ? feriesInit : [],
            };
        }
    </script>
</x-app-layout>
