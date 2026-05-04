@php
    /** @var \App\Models\User $user */
    $user = Auth::user();
    $role = $user->role->value ?? 'employe';
    $roleLabel = $user->role->label() ?? 'Utilisateur';
@endphp

<nav x-data="{ menu: false }" style="border-bottom: 1px solid rgba(255,255,255,0.06);">
    <div style="max-width: 1280px; margin: 0 auto; padding: 14px 32px;
                display: flex; align-items: center; justify-content: space-between; gap: 24px;">

        {{-- LOGO + APP NAME --}}
        <a href="{{ route('dashboard') }}"
           style="display: flex; align-items: center; gap: 10px; color: white;
                  text-decoration: none; flex-shrink: 0;">
            <x-application-logo class="logo-glow" style="width: 28px; height: 28px;" />
            <span style="font-size: 17px; font-weight: 700; letter-spacing: -0.02em;">
                FacePass<span style="color: #818cf8;">.AI</span>
            </span>
        </a>

        {{-- LIENS DE NAV (selon le rôle) --}}
        <div style="display: flex; align-items: center; gap: 4px; flex: 1;">
            <a href="{{ route('dashboard') }}"
               style="padding: 8px 14px; border-radius: 8px;
                      color: {{ request()->routeIs('dashboard') ? 'white' : '#9ca3af' }};
                      background: {{ request()->routeIs('dashboard') ? 'rgba(99,102,241,0.12)' : 'transparent' }};
                      text-decoration: none; font-size: 14px; font-weight: 500;
                      transition: all .15s;">
                Tableau de bord
            </a>

            @can('employes.view')
                <a href="#" class="link-muted"
                   style="padding: 8px 14px; border-radius: 8px; font-size: 14px; font-weight: 500;">
                    Employés
                </a>
            @endcan

            @can('pointages.view-own')
                <a href="#" class="link-muted"
                   style="padding: 8px 14px; border-radius: 8px; font-size: 14px; font-weight: 500;">
                    Pointages
                </a>
            @endcan

            @can('absences.view-own')
                <a href="#" class="link-muted"
                   style="padding: 8px 14px; border-radius: 8px; font-size: 14px; font-weight: 500;">
                    Absences
                </a>
            @endcan

            @can('rapports.view')
                <a href="#" class="link-muted"
                   style="padding: 8px 14px; border-radius: 8px; font-size: 14px; font-weight: 500;">
                    Rapports
                </a>
            @endcan

            @can('horaires.configure')
                <a href="#" class="link-muted"
                   style="padding: 8px 14px; border-radius: 8px; font-size: 14px; font-weight: 500;">
                    Horaires
                </a>
            @endcan

            @can('gestionnaires.manage')
                <a href="#" class="link-muted"
                   style="padding: 8px 14px; border-radius: 8px; font-size: 14px; font-weight: 500;">
                    Gestionnaires
                </a>
            @endcan

            @can('logs.view')
                <a href="#" class="link-muted"
                   style="padding: 8px 14px; border-radius: 8px; font-size: 14px; font-weight: 500;">
                    Logs
                </a>
            @endcan
        </div>

        {{-- USER MENU --}}
        <div x-data="{ open: false }" style="position: relative; flex-shrink: 0;">
            <button @click="open = !open"
                    style="display: flex; align-items: center; gap: 10px; padding: 6px 12px 6px 6px;
                           border-radius: 999px;
                           background: rgba(255,255,255,0.04);
                           border: 1px solid rgba(255,255,255,0.06);
                           color: #e5e7eb; cursor: pointer; transition: all .15s;">
                <span style="width: 28px; height: 28px; border-radius: 999px;
                             background: linear-gradient(135deg, #6366f1, #8b5cf6);
                             display: flex; align-items: center; justify-content: center;
                             font-size: 12px; font-weight: 700; color: white;">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </span>
                <div style="text-align: left;">
                    <div style="font-size: 13px; font-weight: 600; color: white;">{{ $user->name }}</div>
                    <div style="font-size: 11px; color: #818cf8;">{{ $roleLabel }}</div>
                </div>
                <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor" style="color: #6b7280;">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                </svg>
            </button>

            <div x-show="open"
                 @click.outside="open = false"
                 x-transition
                 style="position: absolute; right: 0; top: calc(100% + 8px); min-width: 200px;
                        background: rgba(15, 17, 26, 0.98);
                        border: 1px solid rgba(255,255,255,0.08);
                        border-radius: 12px; padding: 6px;
                        backdrop-filter: blur(20px); z-index: 50;
                        box-shadow: 0 12px 40px rgba(0,0,0,0.5);"
                 style="display: none;">

                <a href="{{ route('profile.edit') }}"
                   style="display: block; padding: 10px 14px; border-radius: 8px;
                          color: #e5e7eb; text-decoration: none; font-size: 14px;"
                   onmouseover="this.style.background='rgba(99,102,241,0.1)'"
                   onmouseout="this.style.background='transparent'">
                    Mon profil
                </a>

                <div style="height: 1px; background: rgba(255,255,255,0.06); margin: 4px 0;"></div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            style="display: block; width: 100%; text-align: left;
                                   padding: 10px 14px; border-radius: 8px;
                                   background: transparent; border: none; cursor: pointer;
                                   color: #fca5a5; font-size: 14px;"
                            onmouseover="this.style.background='rgba(239,68,68,0.1)'"
                            onmouseout="this.style.background='transparent'">
                        Se déconnecter
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>
