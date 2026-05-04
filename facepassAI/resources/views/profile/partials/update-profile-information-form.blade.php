<section>
    {{-- Email de vérification (si non vérifié) --}}
    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
        <div style="margin-bottom: 16px; padding: 12px 16px; border-radius: 10px;
                    background: rgba(234, 179, 8, 0.08);
                    border: 1px solid rgba(234, 179, 8, 0.25);
                    color: #fde68a; font-size: 13px;">
            Votre adresse email n'est pas vérifiée.
            <form method="POST" action="{{ route('verification.send') }}" style="display: inline;">
                @csrf
                <button type="submit" style="background: none; border: none; color: #a5b4fc; cursor: pointer; text-decoration: underline; font-size: 13px;">
                    Renvoyer l'email de vérification
                </button>
            </form>
            @if (session('status') === 'verification-link-sent')
                <span style="color: #86efac;"> · Un nouveau lien vient d'être envoyé.</span>
            @endif
        </div>
    @endif

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}"
          style="display: flex; flex-direction: column; gap: 16px;">
        @csrf
        @method('patch')

        {{-- Nom --}}
        <div>
            <label for="name" style="display: block; font-size: 13px; font-weight: 500;
                                     color: #9ca3af; margin-bottom: 6px;">
                Nom complet
            </label>
            <input type="text" name="name" id="name"
                   value="{{ old('name', $user->name) }}"
                   required autofocus autocomplete="name"
                   class="input-dark" />
            @error('name')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>

        {{-- Email --}}
        <div>
            <label for="email" style="display: block; font-size: 13px; font-weight: 500;
                                      color: #9ca3af; margin-bottom: 6px;">
                Adresse email
            </label>
            <input type="email" name="email" id="email"
                   value="{{ old('email', $user->email) }}"
                   required autocomplete="username"
                   class="input-dark" />
            @error('email')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>

        <div style="display: flex; align-items: center; gap: 16px; margin-top: 8px;">
            <button type="submit" class="btn-primary" style="width: auto;">
                Enregistrer
            </button>

            @if (session('status') === 'profile-updated')
                <span style="font-size: 13px; color: #86efac;">
                    ✓ Modifications enregistrées
                </span>
            @endif
        </div>
    </form>
</section>
