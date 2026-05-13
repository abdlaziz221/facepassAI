<x-app-layout>
    <x-slot name="header">
        <div>
            <a href="{{ route('admin.gestionnaires.index') }}"
               style="display: inline-flex; align-items: center; gap: 6px; color: #9ca3af;
                      font-size: 13px; text-decoration: none; margin-bottom: 8px;">
                ← Retour à la liste
            </a>
            <h1 style="font-size: 28px; font-weight: 800; color: white; letter-spacing: -0.02em; margin: 0 0 4px;">
                Nouveau gestionnaire
            </h1>
            <p class="text-soft" style="margin: 0;">
                Un mot de passe temporaire sera généré automatiquement.
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

    <form method="POST" action="{{ route('admin.gestionnaires.store') }}"
          style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
                 border-radius: 12px; padding: 24px; max-width: 640px;">
        @csrf

        <div style="margin-bottom: 18px;">
            <label style="display: block; font-size: 12px; color: #9ca3af; margin-bottom: 6px;">
                Nom complet <span style="color: #fca5a5;">*</span>
            </label>
            <input type="text" name="name" value="{{ old('name') }}" required minlength="2" maxlength="120"
                   placeholder="Ex : Marie Diop"
                   style="width: 100%; padding: 10px 12px; background: rgba(0,0,0,0.4);
                          border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
                          color: white; font-size: 14px;">
        </div>

        <div style="margin-bottom: 18px;">
            <label style="display: block; font-size: 12px; color: #9ca3af; margin-bottom: 6px;">
                Adresse email <span style="color: #fca5a5;">*</span>
            </label>
            <input type="email" name="email" value="{{ old('email') }}" required maxlength="191"
                   placeholder="marie.diop@facepass.ai"
                   style="width: 100%; padding: 10px 12px; background: rgba(0,0,0,0.4);
                          border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
                          color: white; font-size: 14px;">
            <p style="margin: 6px 0 0; font-size: 11px; color: #6b7280;">
                Doit être unique dans le système (pas déjà utilisée par un autre compte).
            </p>
        </div>

        <div style="margin-bottom: 24px; padding: 14px; background: rgba(99,102,241,0.06);
                    border: 1px solid rgba(99,102,241,0.25); border-radius: 10px;">
            <p style="margin: 0; color: #a5b4fc; font-size: 13px;">
                🔑 Un <strong>mot de passe temporaire</strong> sera généré après création.
                Il sera affiché une seule fois sur la page suivante — pensez à le copier
                pour le transmettre au gestionnaire.
            </p>
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit"
                    style="padding: 11px 22px; background: linear-gradient(135deg, #6366f1, #8b5cf6);
                           border: none; border-radius: 8px; color: white; font-size: 14px;
                           font-weight: 600; cursor: pointer;">
                Créer le compte
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
