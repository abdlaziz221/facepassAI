<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('dashboard') }}" class="link-muted" style="font-size: 13px;">← Retour au tableau de bord</a>
        <h1 style="font-size: 28px; font-weight: 800; color: white; letter-spacing: -0.02em; margin: 8px 0 4px;">
            Nouvelle demande d'absence
        </h1>
        <p class="text-soft" style="margin: 0;">
            Indiquez la période souhaitée et le motif de votre absence. Votre demande sera transmise à un gestionnaire.
        </p>
    </x-slot>

    <div class="glass" style="padding: 32px; border-radius: 16px; max-width: 640px;">

        @if ($errors->has('profile'))
            <div style="margin-bottom: 20px; padding: 12px 16px; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: 8px; color: #fca5a5; font-size: 14px;">
                {{ $errors->first('profile') }}
            </div>
        @endif

        <form method="POST" action="{{ route('demandes-absence.store') }}" style="display: flex; flex-direction: column; gap: 18px;">
            @csrf

            {{-- Période --}}
            <div>
                <label style="display: block; font-size: 14px; font-weight: 600; color: white; margin-bottom: 12px;">
                    Période demandée
                </label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div>
                        <label style="display: block; font-size: 13px; color: #9ca3af; margin-bottom: 6px;">
                            Date de début
                        </label>
                        <input type="date" name="date_debut"
                               value="{{ old('date_debut') }}"
                               min="{{ now()->format('Y-m-d') }}"
                               required class="input-dark">
                        @error('date_debut') <p class="error-text">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; color: #9ca3af; margin-bottom: 6px;">
                            Date de fin
                        </label>
                        <input type="date" name="date_fin"
                               value="{{ old('date_fin') }}"
                               min="{{ now()->format('Y-m-d') }}"
                               required class="input-dark">
                        @error('date_fin') <p class="error-text">{{ $message }}</p> @enderror
                    </div>
                </div>
                <p style="font-size: 12px; color: #6b7280; margin-top: 8px;">
                    La date de fin doit être égale ou postérieure à la date de début.
                </p>
            </div>

            {{-- Motif --}}
            <div>
                <label style="display: block; font-size: 14px; font-weight: 600; color: white; margin-bottom: 8px;">
                    Motif <span style="color: #ef4444;">*</span>
                </label>
                <textarea name="motif" rows="4" required minlength="5" maxlength="500"
                          class="input-dark"
                          placeholder="Exemple : Congé annuel, événement familial, formation externe…">{{ old('motif') }}</textarea>
                <p style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                    Entre 5 et 500 caractères. Soyez précis(e) pour faciliter la validation.
                </p>
                @error('motif') <p class="error-text">{{ $message }}</p> @enderror
            </div>

            {{-- Info statut --}}
            <div style="padding: 12px 14px; background: rgba(34,211,238,0.08); border: 1px solid rgba(34,211,238,0.25); border-radius: 8px; color: #67e8f9; font-size: 13px;">
                ℹ️ Votre demande sera enregistrée avec le statut <strong>« en attente »</strong>. Un gestionnaire la validera ou la refusera et vous serez notifié(e) par la suite.
            </div>

            {{-- Boutons --}}
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 8px;">
                <a href="{{ route('dashboard') }}" class="btn-secondary">Annuler</a>
                <button type="submit" class="btn-primary">Envoyer la demande</button>
            </div>
        </form>
    </div>
</x-app-layout>
