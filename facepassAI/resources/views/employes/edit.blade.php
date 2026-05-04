<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('employes.show', $profile) }}" class="link-muted" style="font-size: 13px;">← Retour au profil</a>
        <h1 style="font-size: 28px; font-weight: 800; color: white; letter-spacing: -0.02em; margin: 8px 0 4px;">
            Modifier {{ $profile->user->name }}
        </h1>
        <p class="text-soft" style="margin: 0;">
            Matricule : {{ $profile->matricule }}
        </p>
    </x-slot>

    <div class="glass" style="padding: 32px; border-radius: 16px; max-width: 720px;">
        <form method="POST" action="{{ route('employes.update', $profile) }}" style="display: flex; flex-direction: column; gap: 16px;">
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

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 8px;">
                <a href="{{ route('employes.show', $profile) }}" class="btn-secondary">Annuler</a>
                <button type="submit" class="btn-primary">Enregistrer les modifications</button>
            </div>
        </form>
    </div>
</x-app-layout>
