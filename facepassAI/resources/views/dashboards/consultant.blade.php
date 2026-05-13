<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="pill">Consultant</span>
            <h1 style="font-size: 32px; font-weight: 800; color: white; letter-spacing: -0.02em; margin: 12px 0 4px;">
                Vue d'ensemble
            </h1>
            <p class="text-soft" style="margin: 0;">
                Consultation des présences, pointages et rapports.
            </p>
        </div>
    </x-slot>
    {{-- KPIs équipe --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
        <div class="card card-stat">
            <div class="label">Total Employés</div>
            <div class="value">{{ $stats['employes'] }}</div>
            <div class="delta">actifs aujourd'hui</div>
        </div>
        <div class="card card-stat">
            <div class="label">Présents aujourd'hui</div>
            <div class="value">{{ max(0, $stats['employes'] - 2) }}</div>
            <div class="delta">94% de présence</div>
        </div>
        <div class="card card-stat">
            <div class="label">Retards ce mois</div>
            <div class="value">7</div>
            <div class="delta" style="color: #fde68a;">+2 vs mois dernier</div>
        </div>
        <div class="card card-stat">
            <div class="label">Rapports générés</div>
            <div class="value">12</div>
            <div class="delta" style="color: #6b7280;">ce mois</div>
        </div>
    </div>
    {{-- Actions rapides --}}
    <h2 class="section-title">Actions rapides</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px;">
        <a href="{{ route('employes.index') }}" class="quick-action">
            <span class="icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
            </span>
            <div>
                <div class="title">Liste des employés</div>
                <div class="subtitle">Consulter les profils et statuts</div>
            </div>
        </a>
        <a href="{{ route('pointages.historique') }}" class="quick-action">
            <span class="icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                </svg>
            </span>
            <div>
                <div class="title">Tous les pointages</div>
                <div class="subtitle">Historique complet et filtres</div>
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
                <div class="subtitle">Export PDF / Excel personnalisé</div>
            </div>
        </a>
        <a href="{{ route('pointages.retards') }}" class="quick-action">
            <span class="icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/>
                </svg>
            </span>
            <div>
                <div class="title">Tableau retards & départs</div>
                <div class="subtitle">Anomalies + export CSV</div>
            </div>
        </a>
    </div>
</x-app-layout>
