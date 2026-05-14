<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('admin.gestionnaires.index') }}"
               style="display: inline-flex; align-items: center; gap: 6px; color: #9ca3af;
                      font-size: 13px; text-decoration: none; margin-bottom: 8px;">
                ← Retour à la liste
            </a>
            <h1 style="font-size: 28px; font-weight: 800; color: white; letter-spacing: -0.02em; margin: 0 0 4px;">
                Modifier le compte de {{ $gestionnaire->name }}
            </h1>
            <p class="text-soft" style="margin: 0;">
                Nom, email et statut actif. Pas de modification du mot de passe ici.
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

    <form method="POST" action="{{ route('admin.gestionnaires.update', $gestionnaire) }}"
          style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
                 border-radius: 12px; padding: 24px; max-width: 640px;">
        @csrf
        @method('PUT')

        <div style="margin-bottom: 18px;">
            <label style="display: block; font-size: 12px; color: #9ca3af; margin-bottom: 6px;">
                Nom complet <span style="color: #fca5a5;">*</span>
            </label>
            <input type="text" name="name" value="{{ old('name', $gestionnaire->name) }}"
                   required minlength="2" maxlength="120"
                   style="width: 100%; padding: 10px 12px; background: rgba(0,0,0,0.4);
                          border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
                          color: white; font-size: 14px;">
        </div>

        <div style="margin-bottom: 18px;">
            <label style="display: block; font-size: 12px; color: #9ca3af; margin-bottom: 6px;">
                Adresse email <span style="color: #fca5a5;">*</span>
            </label>
            <input type="email" name="email" value="{{ old('email', $gestionnaire->email) }}"
                   required maxlength="191"
                   style="width: 100%; padding: 10px 12px; background: rgba(0,0,0,0.4);
                          border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
                          color: white; font-size: 14px;">
        </div>

        <div style="margin-bottom: 24px;">
            <label style="display: inline-flex; align-items: center; gap: 10px;
                          padding: 12px 14px; background: rgba(255,255,255,0.04);
                          border: 1px solid rgba(255,255,255,0.08); border-radius: 8px;
                          cursor: pointer;">
                <input type="checkbox" name="est_actif" value="1"
                       {{ old('est_actif', $gestionnaire->est_actif) ? 'checked' : '' }}>
                <span style="color: white; font-size: 14px;">Compte actif</span>
                <span style="color: #9ca3af; font-size: 12px;">
                    (décocher pour empêcher la connexion sans supprimer le compte)
                </span>
            </label>
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit"
                    style="padding: 11px 22px; background: linear-gradient(135deg, #6366f1, #8b5cf6);
                           border: none; border-radius: 8px; color: white; font-size: 14px;
                           font-weight: 600; cursor: pointer;">
                Enregistrer
            </button>
            <a href="{{ route('admin.gestionnaires.index') }}"
               style="padding: 11px 22px; background: transparent;
                      border: 1px solid rgba(255,255,255,0.15); border-radius: 8px;
                      color: #9ca3af; font-size: 14px; text-decoration: none;">
                Annuler
            </a>
        </div>
    </form>
</x-app-layout>
