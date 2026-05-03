<x-guest-layout>
    <div style="min-height: 100vh; display: flex; flex-direction: column;" class="bg-grid">

        {{-- HEADER --}}
        <header style="border-bottom: 1px solid rgba(255,255,255,0.06);">
            <div style="max-width: 1280px; margin: 0 auto; padding: 18px 32px;
                        display: flex; align-items: center; justify-content: space-between;">
                <a href="/" style="display: flex; align-items: center; gap: 10px;
                                   color: white; text-decoration: none;">
                    <x-application-logo class="logo-glow" style="width: 28px; height: 28px;" />
                    <span style="font-size: 18px; font-weight: 700; letter-spacing: -0.02em;">
                        FacePass<span style="color: #818cf8;">.AI</span>
                    </span>
                </a>
            </div>
        </header>

        {{-- CONTENT --}}
        <main style="flex: 1; display: flex; align-items: center; justify-content: center;
                     padding: 48px 32px;">
            <section class="glass" style="max-width: 460px; width: 100%;
                                          padding: 48px 40px; border-radius: 16px;">

                <div style="text-align: center; margin-bottom: 28px;">
                    <x-application-logo class="logo-glow"
                                        style="width: 56px; height: 56px; margin: 0 auto 20px;" />
                    <h1 style="font-size: 24px; font-weight: 700; color: white;
                               margin: 0 0 8px; letter-spacing: -0.02em;">
                        Mot de passe oublié&nbsp;?
                    </h1>
                    <p style="font-size: 14px; color: #9ca3af; margin: 0;
                              line-height: 1.5;">
                        Entrez votre adresse email, nous vous enverrons un lien
                        pour le réinitialiser.
                    </p>
                </div>

                @if (session('status'))
                    <div style="margin-bottom: 16px; padding: 12px 16px;
                                border-radius: 10px;
                                background: rgba(34, 197, 94, 0.08);
                                border: 1px solid rgba(34, 197, 94, 0.25);
                                color: #86efac; font-size: 13px;">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}"
                      style="display: flex; flex-direction: column; gap: 14px;">
                    @csrf

                    <div>
                        <input type="email" name="email" id="email"
                               value="{{ old('email') }}"
                               placeholder="Adresse email"
                               required autofocus
                               class="input-dark" />
                        @error('email')
                            <p class="error-text">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="btn-primary" style="margin-top: 4px;">
                        Envoyer le lien →
                    </button>
                </form>

                <p style="margin-top: 24px; text-align: center; font-size: 13px;">
                    <a href="{{ route('login') }}" class="link-muted">
                        ← Retour à la connexion
                    </a>
                </p>
            </section>
        </main>

        <footer style="border-top: 1px solid rgba(255,255,255,0.06); padding: 16px 0;">
            <div style="max-width: 1280px; margin: 0 auto; padding: 0 32px;
                        text-align: center; font-size: 12px; color: #6b7280;">
                &copy; {{ date('Y') }} FacePass AI · Tous droits réservés
            </div>
        </footer>
    </div>
</x-guest-layout>
