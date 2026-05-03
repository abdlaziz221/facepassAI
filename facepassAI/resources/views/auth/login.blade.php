<x-guest-layout>
    <div style="min-height: 100vh; display: flex; flex-direction: column;" class="bg-grid">

        {{-- ===========================================================
             HEADER (logo réduit)
             =========================================================== --}}
        <header style="border-bottom: 1px solid rgba(255,255,255,0.06);">
            <div style="max-width: 1280px; margin: 0 auto; padding: 18px 32px;
                        display: flex; align-items: center; justify-content: space-between;">
                <a href="/" style="display: flex; align-items: center; gap: 10px;
                                   color: white; text-decoration: none;">
                    <x-application-logo class="logo-glow"
                                        style="width: 26px; height: 26px;" />
                    <span style="font-size: 18px; font-weight: 700; letter-spacing: -0.02em;">
                        FacePass<span style="color: #818cf8;">.AI</span>
                    </span>
                </a>
                <span class="pill">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="3">
                        <circle cx="12" cy="12" r="10"/>
                    </svg>
                    Connexion sécurisée
                </span>
            </div>
        </header>

        {{-- ===========================================================
             CONTENU - 2 colonnes, beaucoup d'espace en haut
             =========================================================== --}}
        <main style="flex: 1; display: flex; align-items: center;">
            <div style="max-width: 1280px; margin: 0 auto;
                        padding: 96px 32px 64px;
                        display: grid; grid-template-columns: 1fr; gap: 64px;
                        width: 100%; align-items: center;"
                 class="login-grid">

                {{-- ============= COLONNE GAUCHE : pitch + GROS LOGO animé ============= --}}
                <section style="max-width: 560px;">

                    {{-- Logo grand format avec glow + anneaux rotatifs --}}
                    <div style="margin-bottom: 40px;">
                        <x-application-logo class="logo-glow-strong"
                                            style="width: 120px; height: 120px;" />
                    </div>

                    <h1 style="font-size: 44px; line-height: 1.1; font-weight: 800;
                               color: white; letter-spacing: -0.03em; margin: 0 0 20px;">
                        Reconnaissez vos équipes
                        <span style="background: linear-gradient(135deg, #818cf8, #22d3ee);
                                     -webkit-background-clip: text;
                                     background-clip: text;
                                     color: transparent;">
                            en un regard.
                        </span>
                    </h1>

                    <p style="font-size: 17px; line-height: 1.65; color: #9ca3af;
                              margin: 0 0 36px; max-width: 480px;">
                        Une plateforme professionnelle de gestion des présences,
                        propulsée par la reconnaissance faciale. Pointage instantané,
                        suivi des absences et rapports en temps réel, dans une
                        interface conçue pour vos équipes.
                    </p>

                    <ul style="list-style: none; padding: 0; margin: 0;
                               display: flex; flex-direction: column; gap: 14px;">
                        <li style="display: flex; gap: 14px; align-items: flex-start;">
                            <span class="feature-dot"></span>
                            <div>
                                <div style="font-weight: 600; color: #e5e7eb; font-size: 15px;">
                                    Pointage en moins d'une seconde
                                </div>
                                <div style="font-size: 14px; color: #6b7280; margin-top: 2px;">
                                    Reconnaissance faciale temps réel par IA
                                </div>
                            </div>
                        </li>
                        <li style="display: flex; gap: 14px; align-items: flex-start;">
                            <span class="feature-dot"></span>
                            <div>
                                <div style="font-weight: 600; color: #e5e7eb; font-size: 15px;">
                                    Tableaux de bord intelligents
                                </div>
                                <div style="font-size: 14px; color: #6b7280; margin-top: 2px;">
                                    Présences, retards, rapports exportables
                                </div>
                            </div>
                        </li>
                        <li style="display: flex; gap: 14px; align-items: flex-start;">
                            <span class="feature-dot"></span>
                            <div>
                                <div style="font-weight: 600; color: #e5e7eb; font-size: 15px;">
                                    Sécurité et confidentialité
                                </div>
                                <div style="font-size: 14px; color: #6b7280; margin-top: 2px;">
                                    Données chiffrées, conformité RGPD
                                </div>
                            </div>
                        </li>
                    </ul>
                </section>

                {{-- ============= COLONNE DROITE : formulaire ============= --}}
                <section class="glass" style="max-width: 440px; width: 100%;
                                              padding: 40px; border-radius: 16px;
                                              margin-left: auto;">

                    <div style="text-align: center; margin-bottom: 32px;">
                        <h2 style="font-size: 22px; font-weight: 700; color: white;
                                   margin: 0 0 6px;">
                            Connexion
                        </h2>
                        <p style="font-size: 13px; color: #9ca3af; margin: 0;">
                            Accédez à votre espace de travail
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

                    <form method="POST" action="{{ route('login') }}"
                          style="display: flex; flex-direction: column; gap: 14px;">
                        @csrf

                        <div>
                            <input type="text" name="email" id="email"
                                   value="{{ old('email') }}"
                                   placeholder="Identifiant"
                                   required autofocus autocomplete="username"
                                   class="input-dark" />
                            @error('email')
                                <p class="error-text">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <input type="password" name="password" id="password"
                                   placeholder="Mot de passe"
                                   required autocomplete="current-password"
                                   class="input-dark" />
                            @error('password')
                                <p class="error-text">{{ $message }}</p>
                            @enderror
                        </div>

                        <div style="display: flex; align-items: center;
                                    justify-content: space-between;
                                    margin: 4px 0; font-size: 13px;">
                            <label for="remember_me"
                                   style="display: flex; align-items: center; gap: 8px;
                                          color: #9ca3af; cursor: pointer;">
                                <input type="checkbox" name="remember" id="remember_me"
                                       style="accent-color: #6366f1; width: 14px; height: 14px;">
                                Rester connecté
                            </label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="link-muted">
                                    Mot de passe oublié ?
                                </a>
                            @endif
                        </div>

                        <button type="submit" class="btn-primary"
                                style="margin-top: 8px;">
                            Se connecter →
                        </button>
                    </form>

                    <div style="margin-top: 28px; padding-top: 20px;
                                border-top: 1px solid rgba(255,255,255,0.06);
                                text-align: center;">
                        <p style="font-size: 12px; color: #6b7280; margin: 0;">
                            Compte créé par votre administrateur ·
                            <span style="color: #9ca3af;">Support 24/7</span>
                        </p>
                    </div>
                </section>

            </div>
        </main>

        {{-- ===========================================================
             FOOTER
             =========================================================== --}}
        <footer style="border-top: 1px solid rgba(255,255,255,0.06); padding: 16px 0;">
            <div style="max-width: 1280px; margin: 0 auto; padding: 0 32px;
                        display: flex; align-items: center; justify-content: space-between;
                        font-size: 12px; color: #6b7280;">
                <span>&copy; {{ date('Y') }} FacePass AI · Tous droits réservés</span>
                <span style="display: flex; gap: 20px;">
                    <a href="#" class="link-muted">Confidentialité</a>
                    <a href="#" class="link-muted">Conditions</a>
                    <a href="#" class="link-muted">Contact</a>
                </span>
            </div>
        </footer>
    </div>

    <style>
        @media (min-width: 1024px) {
            .login-grid {
                grid-template-columns: 1.1fr 1fr !important;
                gap: 96px !important;
            }
        }
    </style>
</x-guest-layout>
