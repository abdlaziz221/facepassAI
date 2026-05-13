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
    {{-- KPIs équipe --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
        <div class="card card-stat">
            <div class="label">Présents aujourd'hui</div>
            <div class="value">{{ max(0, $stats['employes'] - 2) }}<span style="font-size: 18px; color: #6b7280;">/{{ $stats['employes'] }}</span></div>
            <div class="delta">94% de taux</div>
        </div>
        <div class="card card-stat">
            <div class="label">Retards aujourd'hui</div>
            <div class="value">2</div>
            <div class="delta" style="color: #fde68a;">à vérifier</div>
        </div>
        <div class="card card-stat" style="border-color: rgba(234, 179, 8, 0.3);">
            <div class="label">Demandes en attente</div>
            <div class="value" style="background: linear-gradient(135deg, #fde68a, #f59e0b); -webkit-background-clip: text; background-clip: text; color: transparent;">5</div>
            <div class="delta" style="color: #fde68a;">à valider</div>
        </div>
        <div class="card card-stat">
            <div class="label">Total employés</div>
            <div class="value">{{ $stats['employes'] }}</div>
            <div class="delta" style="color: #6b7280;">actifs</div>
        </div>
    </div>
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
        <a href="#" class="quick-action" title="Générer un rapport — à venir (Sprint 5)" style="opacity: 0.55; cursor: not-allowed;">
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
</x-app-layout>
