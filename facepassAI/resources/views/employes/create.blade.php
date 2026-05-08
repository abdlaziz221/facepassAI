<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('employes.index') }}" class="link-muted" style="font-size: 13px;">← Retour à la liste</a>
        <h1 style="font-size: 28px; font-weight: 800; color: white; letter-spacing: -0.02em; margin: 8px 0 4px;">
            Ajouter un employé
        </h1>
        <p class="text-soft" style="margin: 0;">
            Création du compte utilisateur + profil métier.
        </p>
    </x-slot>

    <div class="glass" style="padding: 32px; border-radius: 16px; max-width: 720px;">
        <form method="POST" action="{{ route('employes.store') }}" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 16px;">
            @csrf

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 500; color: #9ca3af; margin-bottom: 6px;">Nom complet</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="input-dark" placeholder="Aïssatou Diop">
                    @error('name') <p class="error-text">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 500; color: #9ca3af; margin-bottom: 6px;">Adresse email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="input-dark" placeholder="aissatou.diop@exemple.com">
                    @error('email') <p class="error-text">{{ $message }}</p> @enderror
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 500; color: #9ca3af; margin-bottom: 6px;">Matricule</label>
                    <input type="text" name="matricule" value="{{ old('matricule') }}" required class="input-dark" placeholder="EMP-2026-XXX" style="text-transform: uppercase;">
                    @error('matricule') <p class="error-text">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 500; color: #9ca3af; margin-bottom: 6px;">Salaire brut (FCFA)</label>
                    <input type="number" step="0.01" min="0" name="salaire_brut" value="{{ old('salaire_brut') }}" required class="input-dark" placeholder="500000">
                    @error('salaire_brut') <p class="error-text">{{ $message }}</p> @enderror
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 500; color: #9ca3af; margin-bottom: 6px;">Poste</label>
                    <input type="text" name="poste" value="{{ old('poste') }}" required class="input-dark" placeholder="Développeur">
                    @error('poste') <p class="error-text">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 500; color: #9ca3af; margin-bottom: 6px;">Département</label>
                    <input type="text" name="departement" value="{{ old('departement') }}" required class="input-dark" placeholder="Informatique">
                    @error('departement') <p class="error-text">{{ $message }}</p> @enderror
                </div>
            </div>

            <p style="font-size: 12px; color: #6b7280; margin: 0; padding-top: 8px; border-top: 1px solid rgba(255,255,255,0.06);">
                Note : un mot de passe temporaire sera généré. L'employé devra utiliser
                « Mot de passe oublié » à sa première connexion. Photo faciale : Sprint 3.
            </p>

            {{-- Photo faciale (optionnelle) avec bouton personnalisé --}}
            <div x-data="{ preview: null, fileName: null }">
                <label style="display: block; font-size: 13px; font-weight: 500; color: #9ca3af; margin-bottom: 6px;">
                    Photo faciale <span style="color: #6b7280;">(optionnelle)</span>
                </label>
    
            {{-- Bouton personnalisé --}}
                <div style="display: flex; align-items: center; gap: 12px;">
                    <button type="button"
                        @click="$refs.photoInput.click()"
                        class="btn-secondary"
                        style="padding: 8px 16px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; cursor: pointer; font-size: 13px;">
                    📁 Choisir une photo
                    </button>
                    <span x-text="fileName || 'Aucun fichier choisi'" style="color: #9ca3af; font-size: 13px;"></span>
                </div>
    
                {{-- Input file caché --}}
                <input type="file" 
                name="photo_faciale" 
                accept="image/jpeg,image/png,image/jpg"
                @change="preview = URL.createObjectURL($event.target.files[0]); fileName = $event.target.files[0]?.name"
                x-ref="photoInput"
                style="display: none;">
    
                {{-- Aperçu en direct --}}
                <template x-if="preview">
                    <div style="margin-top: 16px;">
                        <img :src="preview" style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%; border: 2px solid #3b82f6;">
                    </div>
                </template>
    
                @error('photo_faciale') 
                    <p class="error-text">{{ $message }}</p> 
                @enderror
                
                <p style="font-size: 12px; color: #6b7280; margin-top: 8px;">
                    Formats JPG, PNG. Max 2 Mo. L'encodage facial sera fait ultérieurement.
                </p>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 8px;">
                <a href="{{ route('employes.index') }}" class="btn-secondary">Annuler</a>
                <button type="submit" class="btn-primary">Créer l'employé</button>
            </div>
        </form>
    </div>
</x-app-layout>
