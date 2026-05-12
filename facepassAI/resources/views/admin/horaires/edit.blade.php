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

        @if ($pointagesCount > 0)
            <div style="margin-bottom: 24px; padding: 16px 20px; background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.4); border-radius: 12px; color: #fcd34d;">
                <div style="display: flex; align-items: start; gap: 12px;">
                    <span style="font-size: 24px; line-height: 1;">⚠️</span>
                    <div>
                        <p style="margin: 0 0 4px 0; font-weight: 600; color: #fde68a;">
                            Attention — {{ $pointagesCount }} pointage(s) existent déjà
                        </p>
                        <p style="margin: 0; font-size: 13px;">
                            Ces pointages ont été enregistrés avec la configuration actuelle.
                            Toute modification s'appliquera uniquement aux pointages futurs ;
                            les pointages existants ne seront pas modifiés rétroactivement.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.horaires.update') }}"
              x-data="horairesForm(@js($config->jours_ouvrables ?? []), @js($config->jours_feries ?? []), {{ $pointagesCount }})"
              @submit.prevent="onSubmit($event)"
              style="display: flex; flex-direction: column; gap: 24px;">
            @csrf
            @method('PUT')

            {{-- Champ caché pour la confirmation (rempli par la modale) --}}
            <input type="hidden" name="confirm" x-bind:value="confirmed ? '1' : ''">

            {{-- Modale de confirmation (visible seulement si pointages existent et user clique Enregistrer) --}}
            <div x-show="showModal" x-cloak
                 style="position: fixed; inset: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 50; padding: 20px;"
                 @click.self="showModal = false">
                <div style="background: #0f172a; border: 1px solid rgba(245,158,11,0.4); border-radius: 16px; padding: 28px; max-width: 480px; width: 100%; box-shadow: 0 20px 50px rgba(0,0,0,0.5);">
                    <h3 style="margin: 0 0 12px 0; font-size: 18px; font-weight: 700; color: #fde68a;">
                        ⚠️ Confirmation requise
                    </h3>
                    <p style="margin: 0 0 16px 0; color: #cbd5e1; font-size: 14px; line-height: 1.5;">
                        <strong x-text="pointagesCount"></strong> pointage(s) existent déjà avec la configuration actuelle.
                        Êtes-vous sûr(e) de vouloir modifier les horaires de référence ?
                    </p>
                    <p style="margin: 0 0 20px 0; color: #94a3b8; font-size: 12px;">
                        Les pointages existants ne seront pas modifiés rétroactivement.
                    </p>
                    <div style="display: flex; gap: 12px; justify-content: flex-end;">
                        <button type="button" @click="showModal = false"
                                style="padding: 10px 18px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #cbd5e1; border-radius: 8px; cursor: pointer;">
                            Annuler
                        </button>
                        <button type="button" @click="confirmed = true; showModal = false; $el.closest('form').submit()"
                                style="padding: 10px 18px; background: rgba(245,158,11,0.2); border: 1px solid rgba(245,158,11,0.5); color: #fde68a; border-radius: 8px; cursor: pointer; font-weight: 500;">
                            Oui, je confirme
                        </button>
                    </div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- Jours ouvrables --}}
            {{-- ============================================================ --}}
            <div>
                <label style="display: block; font-size: 14px; font-weight: 600; color: white; margin-bottom: 12px;">
                    Jours ouvrables
                </label>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    @foreach (\App\Models\JoursTravail::JOURS_VALIDES as $jour)
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
        function horairesForm(joursInit, feriesInit, pointagesCount) {
            return {
                jours: joursInit,
                feries: feriesInit.length ? feriesInit : [],
                pointagesCount: pointagesCount,
                showModal: false,
                confirmed: false,

                // Soumission du formulaire :
                // - Si pointages existent ET pas encore confirmé → ouvrir modale
                // - Sinon → submit direct
                onSubmit(event) {
                    if (this.pointagesCount > 0 && !this.confirmed) {
                        this.showModal = true;
                        return;
                    }
                    event.target.submit();
                },
            };
        }
    </script>
</x-app-layout>
