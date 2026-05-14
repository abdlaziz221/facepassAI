<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('employes.show', $profile) }}" class="link-muted" style="font-size: 13px;">← Retour au profil</a>
        <h1 style="font-size: 28px; font-weight: 800; color: white; letter-spacing: -0.02em; margin: 8px 0 4px;">
            Modifier {{ $profile->user->name }}
        </h1>
        <p class="text-soft" style="margin: 0;">
            Matricule : <span style="font-family: 'JetBrains Mono', monospace;">{{ $profile->matricule }}</span>
        </p>
    </x-slot>

    <div class="glass" style="padding: 32px; border-radius: 16px; max-width: 760px;">
        <form method="POST" action="{{ route('employes.update', $profile) }}"
              style="display: flex; flex-direction: column; gap: 16px;"
              enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 500; color: #9ca3af; margin-bottom: 6px;">Nom complet</label>
                    <input type="text" name="name" value="{{ old('name', $profile->user->name) }}" required class="input-dark">
                    @error('name') <p class="error-text">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 500; color: #9ca3af; margin-bottom: 6px;">Adresse email</label>
                    <input type="email" name="email" value="{{ old('email', $profile->user->email) }}" required class="input-dark">
                    @error('email') <p class="error-text">{{ $message }}</p> @enderror
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 500; color: #9ca3af; margin-bottom: 6px;">Matricule</label>
                    <input type="text" name="matricule" value="{{ old('matricule', $profile->matricule) }}" required class="input-dark" style="text-transform: uppercase;">
                    @error('matricule') <p class="error-text">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 500; color: #9ca3af; margin-bottom: 6px;">Salaire brut (FCFA)</label>
                    <input type="number" step="0.01" min="0" name="salaire_brut" value="{{ old('salaire_brut', $profile->salaire_brut) }}" required class="input-dark">
                    @error('salaire_brut') <p class="error-text">{{ $message }}</p> @enderror
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 500; color: #9ca3af; margin-bottom: 6px;">Poste</label>
                    <input type="text" name="poste" value="{{ old('poste', $profile->poste) }}" required class="input-dark">
                    @error('poste') <p class="error-text">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 500; color: #9ca3af; margin-bottom: 6px;">Département</label>
                    <input type="text" name="departement" value="{{ old('departement', $profile->departement) }}" required class="input-dark">
                    @error('departement') <p class="error-text">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Photo actuelle (affichage propre + remplacement) --}}
            <div x-data="{ preview: null, fileName: null }"
                 style="padding: 18px; background: rgba(255,255,255,0.03);
                        border: 1px solid rgba(255,255,255,0.08); border-radius: 12px;">
                <label style="display: block; font-size: 13px; font-weight: 600; color: white; margin-bottom: 12px;">
                    Photo faciale
                </label>

                <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
                    {{-- Photo actuelle (grande, claire) --}}
                    <div style="text-align: center;">
                        <div style="font-size: 11px; color: #9ca3af; margin-bottom: 6px;
                                    text-transform: uppercase; letter-spacing: 0.05em;">
                            Actuelle
                        </div>
                        @if ($profile->photo_faciale)
                            <img src="{{ Storage::url($profile->photo_faciale) }}"
                                 alt="Photo actuelle"
                                 style="width: 120px; height: 120px; object-fit: cover; border-radius: 12px;
                                        border: 2px solid rgba(99,102,241,0.4);">
                        @else
                            <div style="width: 120px; height: 120px; border-radius: 12px;
                                        background: rgba(255,255,255,0.04);
                                        border: 2px dashed rgba(255,255,255,0.1);
                                        display: flex; align-items: center; justify-content: center;
                                        color: #6b7280; font-size: 12px;">
                                Aucune
                            </div>
                        @endif
                    </div>

                    {{-- Aperçu nouvelle photo --}}
                    <div style="text-align: center;" x-show="preview" x-cloak>
                        <div style="font-size: 11px; color: #fde68a; margin-bottom: 6px;
                                    text-transform: uppercase; letter-spacing: 0.05em;">
                            Nouvelle (aperçu)
                        </div>
                        <img :src="preview"
                             style="width: 120px; height: 120px; object-fit: cover; border-radius: 12px;
                                    border: 2px solid rgba(245,158,11,0.5);">
                    </div>

                    {{-- Bouton + nom de fichier --}}
                    <div style="flex: 1; min-width: 240px;">
                        <button type="button"
                                @click="$refs.photoInput.click()"
                                class="btn-secondary"
                                style="padding: 10px 18px; background: rgba(99,102,241,0.1);
                                       border: 1px solid rgba(99,102,241,0.3); border-radius: 8px;
                                       color: #a5b4fc; cursor: pointer; font-size: 14px; font-weight: 500;">
                            📁 Choisir une nouvelle photo
                        </button>
                        <p x-text="fileName || 'Aucun fichier choisi'"
                           style="color: #9ca3af; font-size: 12px; margin: 8px 0 0;"></p>

                        <input type="file"
                               name="photo_faciale"
                               accept="image/jpeg,image/png,image/jpg"
                               @change="preview = URL.createObjectURL($event.target.files[0]); fileName = $event.target.files[0]?.name"
                               x-ref="photoInput"
                               style="display: none;">

                        @error('photo_faciale')
                            <p class="error-text" style="margin-top: 8px;">{{ $message }}</p>
                        @enderror

                        <p style="font-size: 11px; color: #6b7280; margin: 10px 0 0;">
                            Laissez vide pour conserver la photo actuelle.<br>
                            Formats : JPG, PNG. Taille max : 2 Mo.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Boutons --}}
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 8px;">
                <a href="{{ route('employes.show', $profile) }}" class="btn-secondary">Annuler</a>
                <button type="submit" class="btn-primary">Enregistrer les modifications</button>
            </div>
        </form>
    </div>
</x-app-layout>
