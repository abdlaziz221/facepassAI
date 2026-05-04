<section>
    <form method="post" action="{{ route('password.update') }}"
          style="display: flex; flex-direction: column; gap: 16px;">
        @csrf
        @method('put')

        {{-- Mot de passe actuel --}}
        <div>
            <label for="update_password_current_password"
                   style="display: block; font-size: 13px; font-weight: 500; color: #9ca3af; margin-bottom: 6px;">
                Mot de passe actuel
            </label>
            <input type="password"
                   name="current_password"
                   id="update_password_current_password"
                   autocomplete="current-password"
                   placeholder="••••••••"
                   class="input-dark" />
            @error('current_password', 'updatePassword')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>

        {{-- Nouveau mot de passe --}}
        <div>
            <label for="update_password_password"
                   style="display: block; font-size: 13px; font-weight: 500; color: #9ca3af; margin-bottom: 6px;">
                Nouveau mot de passe
            </label>
            <input type="password"
                   name="password"
                   id="update_password_password"
                   autocomplete="new-password"
                   placeholder="••••••••"
                   class="input-dark" />
            @error('password', 'updatePassword')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>

        {{-- Confirmation --}}
        <div>
            <label for="update_password_password_confirmation"
                   style="display: block; font-size: 13px; font-weight: 500; color: #9ca3af; margin-bottom: 6px;">
                Confirmer le nouveau mot de passe
            </label>
            <input type="password"
                   name="password_confirmation"
                   id="update_password_password_confirmation"
                   autocomplete="new-password"
                   placeholder="••••••••"
                   class="input-dark" />
            @error('password_confirmation', 'updatePassword')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>

        <div style="display: flex; align-items: center; gap: 16px; margin-top: 8px;">
            <button type="submit" class="btn-primary" style="width: auto;">
                Mettre à jour
            </button>

            @if (session('status') === 'password-updated')
                <span style="font-size: 13px; color: #86efac;">
                    ✓ Mot de passe modifié
                </span>
            @endif
        </div>
    </form>
</section>
