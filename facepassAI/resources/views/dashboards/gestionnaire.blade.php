<x-app-layout>
    <x-slot name="header">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <span class="pill">Gestionnaire</span>
                <h1 style="font-size: 32px; font-weight: 800; color: white; letter-spacing: -0.02em; margin: 12px 0 4px;">
                    Pilotage de l'équipe
                </h1>
                <p class="text-soft" style="margin: 0;">
                    Gestion des employés, validation des absences et configuration des horaires.
                </p>
            </div>
            <a href="{{ route('employes.create') }}" class="btn-primary">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Ajouter un employé
            </a>
        </div>
    </x-slot>
    {{-- Sprint 6 carte 12 (US-102) — Alertes priorisées --}}
    <x-dashboard-alerts :alertes="$alertes ?? collect()" />

    {{-- Sprint 6 carte 10 (US-100) — KPIs du jour (réels) --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
        <div class="card card-stat">
            <div class="label">Présents aujourd'hui</div>
            <div class="value">
                {{ $kpi['presents'] ?? 0 }}<span style="font-size: 18px; color: #6b7280;">/{{ $kpi['total_employes'] ?? 0 }}</span>
            </div>
            <div class="delta">{{ $kpi['taux_presence'] ?? 0 }}% de taux</div>
        </div>
        <div class="card card-stat">
            <div class="label">Retards aujourd'hui</div>
            <div class="value">{{ $kpi['retards'] ?? 0 }}</div>
            <div class="delta" style="color: #fde68a;">à vérifier</div>
        </div>
        <div class="card card-stat" style="border-color: rgba(234, 179, 8, 0.3);">
            <div class="label">Demandes en attente</div>
            <div class="value" style="background: linear-gradient(135deg, #fde68a, #f59e0b); -webkit-background-clip: text; background-clip: text; color: transparent;">
                {{ $kpi['demandes_en_attente'] ?? 0 }}
            </div>
            <div class="delta" style="color: #fde68a;">à valider</div>
        </div>
        <div class="card card-stat">
            <div class="label">Absents</div>
            <div class="value" style="color: #fca5a5;">{{ $kpi['absents'] ?? 0 }}</div>
            <div class="delta" style="color: #fca5a5;">sans pointage ni congé</div>
        </div>
    </div>

    {{-- Sprint 6 carte 11 (US-101) — Graphiques --}}
    @if (!empty($charts))
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
    @endif
    {{-- Actions rapides --}}
    <h2 class="section-title">Actions de gestion</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px;">
        <a href="{{ route('demandes-absence.index') }}" class="quick-action">
            <span class="icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/>
                </svg>
            </span>
            <div>
                <div class="title">Valider les absences</div>
                <div class="subtitle">Voir les demandes en attente</div>
            </div>
        </a>
        <a href="{{ route('employes.index') }}" class="quick-action">
            <span class="icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
            </span>
            <div>
                <div class="title">Gérer les employés</div>
                <div class="subtitle">CRUD, profils, photos faciales</div>
            </div>
        </a>
        <a href="{{ route('admin.horaires.edit') }}" class="quick-action">
            <span class="icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </span>
            <div>
                <div class="title">Configurer les horaires</div>
                <div class="subtitle">Jours ouvrables, jours fériés</div>
            </div>
        </a>
        <a href="{{ route('rapports.index') }}" class="quick-action">
            <span class="icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                </svg>
            </span>
            <div>
                <div class="title">Générer un rapport</div>
                <div class="subtitle">Export PDF / Excel</div>
            </div>
        </a>
    </div>

    {{-- Pointage & suivi (raccourcis complets) --}}
    <h2 class="section-title">Pointage & suivi</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px;">
        <a href="{{ route('pointages.create') }}" class="quick-action" target="_blank">
            <span class="icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/>
                </svg>
            </span>
            <div>
                <div class="title">Kiosque de pointage</div>
                <div class="subtitle">Page caméra (nouvel onglet)</div>
            </div>
        </a>
        <a href="{{ route('pointages.manual.create') }}" class="quick-action">
            <span class="icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                </svg>
            </span>
            <div>
                <div class="title">Pointage manuel</div>
                <div class="subtitle">Saisir pour un employé</div>
            </div>
        </a>
        <a href="{{ route('pointages.historique') }}" class="quick-action">
            <span class="icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                </svg>
            </span>
            <div>
                <div class="title">Historique des pointages</div>
                <div class="subtitle">Tableau filtrable</div>
            </div>
        </a>
        <a href="{{ route('pointages.retards') }}" class="quick-action">
            <span class="icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </span>
            <div>
                <div class="title">Retards & départs anticipés</div>
                <div class="subtitle">Anomalies + export CSV</div>
            </div>
        </a>
    </div>

    {{-- Sprint 6 carte 11 (US-101) — Chart.js --}}
    @if (!empty($charts))
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
            (function () {
                Chart.defaults.color = 'rgba(229,231,235,0.85)';
                Chart.defaults.borderColor = 'rgba(255,255,255,0.08)';
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
                                tension: 0.35, fill: true, pointRadius: 2, pointHoverRadius: 5,
                            }]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
                        }
                    });
                }
                const statsEl = document.getElementById('chartStatutsAbsences');
                if (statsEl) {
                    new Chart(statsEl, {
                        type: 'doughnut',
                        data: {
                            labels: @json($charts['statutsAbsences']['labels']),
                            datasets: [{
                                data: @json($charts['statutsAbsences']['data']),
                                backgroundColor: ['#f59e0b', '#10b981', '#ef4444'],
                                borderColor: '#0f111a', borderWidth: 2,
                            }]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } },
                        }
                    });
                }
            })();
        </script>
    @endif
</x-app-layout>
