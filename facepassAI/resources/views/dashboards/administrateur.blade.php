<x-app-layout>
    <x-slot name="header">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <span class="pill" style="background: rgba(239, 68, 68, 0.12); border-color: rgba(239, 68, 68, 0.25); color: #fca5a5;">
                    Administrateur
                </span>
                <h1 style="font-size: 32px; font-weight: 800; color: white; letter-spacing: -0.02em; margin: 12px 0 4px;">
                    Console d'administration
                </h1>
                <p class="text-soft" style="margin: 0;">
                    Vue d'ensemble du système, gestion des comptes et audit complet.
                </p>
            </div>
            <span class="pill pill-success">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                Système opérationnel
            </span>
        </div>
    </x-slot>

    {{-- KPIs globaux --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
        <div class="card card-stat">
            <div class="label">Utilisateurs total</div>
            <div class="value">{{ $stats['total_users'] }}</div>
            <div class="delta">comptes actifs</div>
        </div>
        <div class="card card-stat">
            <div class="label">Gestionnaires</div>
            <div class="value">{{ $stats['gestionnaires'] }}</div>
            <div class="delta" style="color: #6b7280;">{{ $stats['consultants'] }} consultants</div>
        </div>
        <div class="card card-stat">
            <div class="label">Employés</div>
            <div class="value">{{ $stats['employes'] }}</div>
            <div class="delta">en activité</div>
        </div>
        <div class="card card-stat">
            <div class="label">Événements logs</div>
            <div class="value">247</div>
            <div class="delta" style="color: #6b7280;">dernières 24h</div>
        </div>
    </div>

    {{-- Répartition par rôle --}}
    <h2 class="section-title">Répartition des comptes</h2>
    <div class="card" style="padding: 24px;">
        @php
            $total = max(1, $stats['total_users']);
            $rolesData = [
                ['label' => 'Administrateurs', 'count' => $stats['admins'],        'color' => '#fca5a5', 'bg' => 'linear-gradient(90deg, #ef4444, #f97316)'],
                ['label' => 'Gestionnaires',   'count' => $stats['gestionnaires'], 'color' => '#fde68a', 'bg' => 'linear-gradient(90deg, #f59e0b, #eab308)'],
                ['label' => 'Consultants',     'count' => $stats['consultants'],   'color' => '#a5b4fc', 'bg' => 'linear-gradient(90deg, #6366f1, #8b5cf6)'],
                ['label' => 'Employés',        'count' => $stats['employes'],      'color' => '#67e8f9', 'bg' => 'linear-gradient(90deg, #22d3ee, #3b82f6)'],
            ];
        @endphp

        <div style="display: flex; flex-direction: column; gap: 14px;">
            @foreach ($rolesData as $r)
                @php $pct = round(($r['count'] / $total) * 100, 1); @endphp
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <span style="font-size: 14px; color: {{ $r['color'] }}; font-weight: 600;">
                            {{ $r['label'] }}
                        </span>
                        <span class="text-muted" style="font-size: 13px;">
                            {{ $r['count'] }} ({{ $pct }}%)
                        </span>
                    </div>
                    <div style="background: rgba(255,255,255,0.04); border-radius: 999px; height: 8px; overflow: hidden;">
                        <div style="height: 100%; width: {{ $pct }}%; background: {{ $r['bg'] }};
                                    border-radius: 999px; transition: width .8s;"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Actions admin --}}
    <h2 class="section-title">Administration</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px;">
        <a href="#" class="quick-action">
            <span class="icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 019.374 21c-2.331 0-4.512-.645-6.374-1.766z"/>
                </svg>
            </span>
            <div>
                <div class="title">Gérer les gestionnaires</div>
                <div class="subtitle">Créer, modifier, désactiver</div>
            </div>
        </a>
        <a href="#" class="quick-action">
            <span class="icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
                </svg>
            </span>
            <div>
                <div class="title">Logs système</div>
                <div class="subtitle">Audit, historique des actions</div>
            </div>
        </a>
        <a href="#" class="quick-action">
            <span class="icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </span>
            <div>
                <div class="title">Paramètres système</div>
                <div class="subtitle">Configuration globale</div>
            </div>
        </a>
        <a href="#" class="quick-action">
            <span class="icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/>
                </svg>
            </span>
            <div>
                <div class="title">Tableau de bord global</div>
                <div class="subtitle">KPI consolidés</div>
            </div>
        </a>
    </div>
</x-app-layout>
