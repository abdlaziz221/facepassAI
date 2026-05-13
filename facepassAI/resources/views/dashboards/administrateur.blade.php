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
    {{-- Sprint 6 carte 12 (US-102) — Alertes priorisées --}}
    <x-dashboard-alerts :alertes="$alertes ?? collect()" />

    {{-- Sprint 6 carte 10 (US-100) — KPIs du jour --}}
    <h2 class="section-title" style="margin-top: 0;">Aujourd'hui</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="card card-stat" style="border-color: rgba(16,185,129,0.3);">
            <div class="label">Présents</div>
            <div class="value" style="background: linear-gradient(135deg, #6ee7b7, #10b981);
                -webkit-background-clip: text; background-clip: text; color: transparent;">
                {{ $kpi['presents'] ?? 0 }}<span style="font-size: 16px; color: #6b7280;">/{{ $kpi['total_employes'] ?? 0 }}</span>
            </div>
            <div class="delta" style="color: #6ee7b7;">{{ $kpi['taux_presence'] ?? 0 }}% de présence</div>
        </div>
        <div class="card card-stat">
            <div class="label">Retards du jour</div>
            <div class="value" style="background: linear-gradient(135deg, #fde68a, #f59e0b);
                -webkit-background-clip: text; background-clip: text; color: transparent;">
                {{ $kpi['retards'] ?? 0 }}
            </div>
            <div class="delta" style="color: #fde68a;">arrivées après l'heure</div>
        </div>
        <div class="card card-stat">
            <div class="label">Absents</div>
            <div class="value" style="background: linear-gradient(135deg, #fca5a5, #ef4444);
                -webkit-background-clip: text; background-clip: text; color: transparent;">
                {{ $kpi['absents'] ?? 0 }}
            </div>
            <div class="delta" style="color: #fca5a5;">sans pointage ni congé</div>
        </div>
        <div class="card card-stat" style="border-color: rgba(99,102,241,0.3);">
            <div class="label">Demandes en attente</div>
            <div class="value" style="background: linear-gradient(135deg, #a5b4fc, #6366f1);
                -webkit-background-clip: text; background-clip: text; color: transparent;">
                {{ $kpi['demandes_en_attente'] ?? 0 }}
            </div>
            <div class="delta" style="color: #a5b4fc;">à traiter</div>
        </div>
    </div>

    {{-- Sprint 6 carte 11 (US-101) — Graphiques Chart.js --}}
    <h2 class="section-title">Évolution sur 30 jours</h2>
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 24px;">
        <div class="card" style="padding: 20px; min-height: 260px;">
            <h3 style="margin: 0 0 12px; font-size: 14px; color: #d1d5db;">Présences par jour</h3>
            <canvas id="chartPresences30j"></canvas>
        </div>
        <div class="card" style="padding: 20px; min-height: 260px;">
            <h3 style="margin: 0 0 12px; font-size: 14px; color: #d1d5db;">Répartition des demandes</h3>
            <canvas id="chartStatutsAbsences"></canvas>
        </div>
    </div>

    {{-- Composition des comptes --}}
    <h2 class="section-title">Composition des comptes</h2>
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
        <a href="{{ route('admin.gestionnaires.index') }}" class="quick-action">
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
        <a href="{{ route('admin.logs.index') }}" class="quick-action">
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
        <a href="{{ route('admin.horaires.edit') }}" class="quick-action">
            <span class="icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </span>
            <div>
                <div class="title">Paramètres système</div>
                <div class="subtitle">Horaires & jours fériés</div>
            </div>
        </a>
        <a href="#" class="quick-action" title="Tableau de bord global — à venir" style="opacity: 0.55; cursor: not-allowed;">
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

    {{-- Sprint 6 carte 11 (US-101) — Chart.js via CDN --}}
    @if (!empty($charts))
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
            (function () {
                const textColor = 'rgba(229,231,235,0.85)';
                const gridColor = 'rgba(255,255,255,0.08)';
                Chart.defaults.color = textColor;
                Chart.defaults.borderColor = gridColor;
                Chart.defaults.font.family = 'inherit';

                // Courbe : présences sur 30 jours
                const presEl = document.getElementById('chartPresences30j');
                if (presEl) {
                    new Chart(presEl, {
                        type: 'line',
                        data: {
                            labels: @json($charts['presences30']['labels']),
                            datasets: [{
                                label: 'Employés présents',
                                data: @json($charts['presences30']['data']),
                                borderColor: '#6366f1',
                                backgroundColor: 'rgba(99,102,241,0.15)',
                                tension: 0.35,
                                fill: true,
                                pointRadius: 2,
                                pointHoverRadius: 5,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: gridColor } },
                                x: { grid: { display: false } },
                            },
                        }
                    });
                }

                // Camembert : répartition des statuts d'absence
                const statsEl = document.getElementById('chartStatutsAbsences');
                if (statsEl) {
                    new Chart(statsEl, {
                        type: 'doughnut',
                        data: {
                            labels: @json($charts['statutsAbsences']['labels']),
                            datasets: [{
                                data: @json($charts['statutsAbsences']['data']),
                                backgroundColor: ['#f59e0b', '#10b981', '#ef4444'],
                                borderColor: '#0f111a',
                                borderWidth: 2,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } },
                        }
                    });
                }
            })();
        </script>
    @endif
</x-app-layout>
