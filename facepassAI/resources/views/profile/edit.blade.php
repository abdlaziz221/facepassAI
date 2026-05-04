<x-app-layout>
    @php
        /** @var \App\Models\User $user */
        $user = $user ?? auth()->user();
        $roleLabel = $user->role->label() ?? 'Utilisateur';
        $initials = strtoupper(substr($user->name, 0, 2));
    @endphp

    {{-- ============================================================
         BANDEAU IDENTITÉ (avatar + infos)
         ============================================================ --}}
    <section x-data="{ tab: 'info' }" style="margin-bottom: 32px;">

        {{-- Carte d'identité --}}
        <div class="glass" style="padding: 32px; border-radius: 16px;
                                   display: flex; gap: 24px; align-items: center;
                                   flex-wrap: wrap;">

            {{-- Avatar gradient avec initiales --}}
            <div style="position: relative; flex-shrink: 0;">
                <div style="width: 88px; height: 88px; border-radius: 22px;
                            background: linear-gradient(135deg, #6366f1, #8b5cf6 50%, #22d3ee);
                            display: flex; align-items: center; justify-content: center;
                            font-size: 32px; font-weight: 800; color: white;
                            box-shadow: 0 12px 32px rgba(99, 102, 241, 0.4),
                                        0 0 0 4px rgba(99, 102, 241, 0.1);
                            letter-spacing: -0.02em;">
                    {{ $initials }}
                </div>
                {{-- Pastille rôle en bas à droite --}}
                <span style="position: absolute; bottom: -6px; right: -6px;
                             width: 26px; height: 26px; border-radius: 999px;
                             background: linear-gradient(135deg, #22c55e, #16a34a);
                             border: 3px solid #050609;
                             display: flex; align-items: center; justify-content: center;">
                    <svg width="12" height="12" fill="none" stroke="white" stroke-width="3" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                </span>
            </div>

            {{-- Infos --}}
            <div style="flex: 1; min-width: 220px;">
                <span class="pill" style="margin-bottom: 8px;">{{ $roleLabel }}</span>
                <h1 style="font-size: 26px; font-weight: 800; color: white;
                           letter-spacing: -0.02em; margin: 6px 0 4px;">
                    {{ $user->name }}
                </h1>
                <p style="margin: 0; color: #9ca3af; font-size: 14px;
                          display: flex; align-items: center; gap: 6px;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                    </svg>
                    {{ $user->email }}
                </p>
                <p style="margin: 8px 0 0; color: #6b7280; font-size: 12px;">
                    Compte créé le {{ $user->created_at->format('d/m/Y') }}
                </p>
            </div>

            {{-- Statut compte --}}
            <div style="text-align: right;">
                <div style="font-size: 11px; color: #6b7280; text-transform: uppercase;
                            letter-spacing: 0.08em; font-weight: 600; margin-bottom: 4px;">
                    Statut
                </div>
                <span class="pill pill-success">
                    <span style="width: 6px; height: 6px; border-radius: 999px; background: #4ade80;
                                 box-shadow: 0 0 8px #4ade80;"></span>
                    Actif
                </span>
            </div>
        </div>

        {{-- Onglets --}}
        <div style="display: flex; gap: 4px; margin-top: 24px; padding: 4px;
                    background: rgba(255,255,255,0.025); border: 1px solid rgba(255,255,255,0.06);
                    border-radius: 12px; max-width: fit-content;">
            <button @click="tab = 'info'"
                    :style="tab === 'info' ? 'background: rgba(99,102,241,0.18); color: white;' : 'color: #9ca3af;'"
                    style="padding: 10px 18px; border-radius: 8px; border: none;
                           font-size: 13px; font-weight: 600; cursor: pointer;
                           background: transparent; transition: all .15s;
                           display: flex; align-items: center; gap: 8px;">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                </svg>
                Informations
            </button>
            <button @click="tab = 'security'"
                    :style="tab === 'security' ? 'background: rgba(99,102,241,0.18); color: white;' : 'color: #9ca3af;'"
                    style="padding: 10px 18px; border-radius: 8px; border: none;
                           font-size: 13px; font-weight: 600; cursor: pointer;
                           background: transparent; transition: all .15s;
                           display: flex; align-items: center; gap: 8px;">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                </svg>
                Sécurité
            </button>
            <button @click="tab = 'danger'"
                    :style="tab === 'danger' ? 'background: rgba(239,68,68,0.18); color: #fca5a5;' : 'color: #9ca3af;'"
                    style="padding: 10px 18px; border-radius: 8px; border: none;
                           font-size: 13px; font-weight: 600; cursor: pointer;
                           background: transparent; transition: all .15s;
                           display: flex; align-items: center; gap: 8px;">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
                Zone dangereuse
            </button>
        </div>

        {{-- ============================================================
             CONTENU des onglets
             ============================================================ --}}

        {{-- Onglet Informations --}}
        <div x-show="tab === 'info'" x-transition.opacity style="margin-top: 24px;">
            <div class="glass" style="padding: 32px; border-radius: 16px; max-width: 640px;">
                <div style="margin-bottom: 28px;">
                    <h2 style="font-size: 18px; font-weight: 700; color: white;
                               margin: 0 0 6px; display: flex; align-items: center; gap: 8px;">
                        <span style="width: 6px; height: 6px; border-radius: 999px;
                                     background: linear-gradient(135deg, #6366f1, #22d3ee);"></span>
                        Informations personnelles
                    </h2>
                    <p style="font-size: 13px; color: #9ca3af; margin: 0;">
                        Modifiez votre nom et votre adresse email professionnelle.
                    </p>
                </div>
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        {{-- Onglet Sécurité --}}
        <div x-show="tab === 'security'" x-transition.opacity x-cloak style="margin-top: 24px; display: none;">
            <div class="glass" style="padding: 32px; border-radius: 16px; max-width: 640px;">
                <div style="margin-bottom: 28px;">
                    <h2 style="font-size: 18px; font-weight: 700; color: white;
                               margin: 0 0 6px; display: flex; align-items: center; gap: 8px;">
                        <span style="width: 6px; height: 6px; border-radius: 999px;
                                     background: linear-gradient(135deg, #6366f1, #22d3ee);"></span>
                        Mot de passe
                    </h2>
                    <p style="font-size: 13px; color: #9ca3af; margin: 0;">
                        Choisissez un mot de passe long et complexe pour sécuriser votre compte.
                    </p>
                </div>
                @include('profile.partials.update-password-form')
            </div>
        </div>

        {{-- Onglet Zone dangereuse --}}
        <div x-show="tab === 'danger'" x-transition.opacity x-cloak style="margin-top: 24px; display: none;">
            <div class="glass" style="padding: 32px; border-radius: 16px; max-width: 640px;
                                       border-color: rgba(239, 68, 68, 0.25);">
                <div style="margin-bottom: 28px;">
                    <h2 style="font-size: 18px; font-weight: 700; color: #fca5a5;
                               margin: 0 0 6px; display: flex; align-items: center; gap: 8px;">
                        <span style="width: 6px; height: 6px; border-radius: 999px; background: #ef4444;"></span>
                        Supprimer mon compte
                    </h2>
                    <p style="font-size: 13px; color: #9ca3af; margin: 0; line-height: 1.6;">
                        Cette action est <strong style="color: #fca5a5;">définitive</strong>.
                        Toutes vos données (pointages, absences, profil) seront effacées et
                        ne pourront pas être récupérées.
                    </p>
                </div>
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </section>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</x-app-layout>
