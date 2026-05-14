<x-app-layout>
    <x-slot name="header">
        <div style="display: flex; align-items: center; gap: 12px;">
            <span style="font-size: 13px;" class="link-muted">
                <a href="{{ route('pointages.create') }}" style="color: #9ca3af;">← Retour au kiosque</a>
            </span>
        </div>
        <h1 style="font-size: 28px; font-weight: 800; color: white; letter-spacing: -0.02em; margin: 8px 0 4px;">
            Pointage manuel
        </h1>
        <p class="text-soft" style="margin: 0;">
            Mode dégradé : utilisez ce formulaire si la caméra est indisponible.
            Un motif justificatif est obligatoire.
        </p>
    </x-slot>

    <div class="glass" style="padding: 32px; border-radius: 16px; max-width: 720px;">

        {{-- Flash success --}}
        @if (session('success'))
            <div style="margin-bottom: 20px; padding: 12px 16px; background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); border-radius: 8px; color: #6ee7b7; font-size: 14px;">
                ✓ {{ session('success') }}
            </div>
        @endif

        {{-- Erreurs globales (non liées à un champ) --}}
        @if ($errors->has('employe_id') && !old('employe_id'))
            <div style="margin-bottom: 20px; padding: 12px 16px; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: 8px; color: #fca5a5; font-size: 14px;">
                {{ $errors->first('employe_id') }}
            </div>
        @endif

        <form method="POST" action="{{ route('pointages.manual.store') }}" style="display: flex; flex-direction: column; gap: 16px;">
            @csrf

            {{-- Employé --}}
            <div>
                <label style="display: block; font-size: 13px; font-weight: 500; color: #9ca3af; margin-bottom: 6px;">
                    Employé
                </label>
                <select name="employe_id" required class="input-dark">
                    <option value="">— Sélectionner un employé —</option>
                    @foreach ($employes as $profile)
                        <option value="{{ $profile->id }}" @selected(old('employe_id') == $profile->id)>
                            {{ $profile->matricule }} — {{ $profile->user->name ?? '?' }}
                            ({{ $profile->poste }} · {{ $profile->departement }})
                        </option>
                    @endforeach
                </select>
                @error('employe_id') <p class="error-text">{{ $message }}</p> @enderror
            </div>

            {{-- Type --}}
            <div>
                <label style="display: block; font-size: 13px; font-weight: 500; color: #9ca3af; margin-bottom: 6px;">
                    Type de pointage
                </label>
                <select name="type" required class="input-dark">
                    <option value="">— Choisir un type —</option>
                    <option value="arrivee"     @selected(old('type') === 'arrivee')>Arrivée</option>
                    <option value="debut_pause" @selected(old('type') === 'debut_pause')>Début de pause</option>
                    <option value="fin_pause"   @selected(old('type') === 'fin_pause')>Fin de pause</option>
                    <option value="depart"      @selected(old('type') === 'depart')>Départ</option>
                </select>
                @error('type') <p class="error-text">{{ $message }}</p> @enderror
            </div>

            {{-- Motif --}}
            <div>
                <label style="display: block; font-size: 13px; font-weight: 500; color: #9ca3af; margin-bottom: 6px;">
                    Motif <span style="color: #ef4444;">*</span>
                </label>
                <textarea name="motif" rows="3" required minlength="5" maxlength="500"
                    class="input-dark"
                    placeholder="Exemple : Caméra hors service, badgeage à la place de l'employé">{{ old('motif') }}</textarea>
                <p style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                    Entre 5 et 500 caractères. Le motif sera conservé dans le journal d'audit.
                </p>
                @error('motif') <p class="error-text">{{ $message }}</p> @enderror
            </div>

            {{-- Note BNF-06 --}}
            <p style="font-size: 12px; color: #6b7280; margin: 0; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.06);">
                Ce pointage sera enregistré avec le flag <code style="background: rgba(255,255,255,0.08); padding: 2px 6px; border-radius: 4px;">manuel=true</code>
                et le motif justificatif. Action tracée dans le journal serveur.
            </p>

            {{-- Boutons --}}
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 8px;">
                <a href="{{ route('dashboard') }}" class="btn-secondary">Annuler</a>
                <button type="submit" class="btn-primary">Enregistrer le pointage</button>
            </div>
        </form>
    </div>
</x-app-layout>
