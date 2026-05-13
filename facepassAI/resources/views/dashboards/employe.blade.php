<x-app-layout>
    <x-slot name="header">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <span class="pill">Employé</span>
                <h1 style="font-size: 32px; font-weight: 800; color: white; letter-spacing: -0.02em; margin: 12px 0 4px;">
                    Bonjour, {{ explode(' ', $user->name)[0] }} 👋
                </h1>
                <p class="text-soft" style="margin: 0;">
                    {{ now()->locale('fr')->isoFormat('dddd D MMMM YYYY · HH:mm') }}
                </p>
            </div>
            <a href="{{ route('pointages.create') }}" target="_blank" class="btn-primary">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                </svg>
                Pointer maintenant
            </a>
        </div>
    </x-slot>
    {{-- KPIs personnels --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
        <div class="card card-stat">
            <div class="label">Statut du jour</div>
            <div class="value" style="font-size: 22px;">
                <span class="pill pill-success" style="font-size: 13px;">Présent</span>
            </div>
            <div class="delta">Pointage à 08:42</div>
        </div>
        <div class="card card-stat">
            <div class="label">Heures ce mois</div>
            <div class="value">142<span style="font-size: 18px; color: #6b7280;">h</span></div>
            <div class="delta">+8h vs mois dernier</div>
        </div>
        <div class="card card-stat">
            <div class="label">Absences validées</div>
            <div class="value">3</div>
            <div class="delta" style="color: #6b7280;">cette année</div>
        </div>
        <div class="card card-stat">
            <div class="label">Solde congés</div>
            <div class="value">12<span style="font-size: 18px; color: #6b7280;">j</span></div>
            <div class="delta" style="color: #6b7280;">restants</div>
        </div>
    </div>
    {{-- Actions rapides --}}
    <h2 class="section-title">Actions rapides</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px;">
        <a href="#" class="quick-action" title="Mon historique de pointage — à venir" style="opacity: 0.55; cursor: not-allowed;">
            <span class="icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/>
                </svg>
            </span>
            <div>
                <div class="title">Mon historique de pointage</div>
                <div class="subtitle">Consulter mes derniers pointages</div>
            </div>
        </a>
        <a href="{{ route('demandes-absence.create') }}" class="quick-action">
            <span class="icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                </svg>
            </span>
            <div>
                <div class="title">Demander une absence</div>
                <div class="subtitle">Congé, maladie, autre motif</div>
            </div>
        </a>
        <a href="{{ route('mes-demandes-absence.index') }}" class="quick-action">
            <span class="icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/>
                </svg>
            </span>
            <div>
                <div class="title">Mes demandes d'absence</div>
                <div class="subtitle">Suivre le statut de mes demandes</div>
            </div>
        </a>
        <a href="#" class="quick-action" title="Mon salaire — à venir" style="opacity: 0.55; cursor: not-allowed;">
            <span class="icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/>
                </svg>
            </span>
            <div>
                <div class="title">Mon salaire</div>
                <div class="subtitle">Voir le récapitulatif mensuel</div>
            </div>
        </a>
        <a href="{{ route('profile.edit') }}" class="quick-action">
            <span class="icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </span>
            <div>
                <div class="title">Mon profil</div>
                <div class="subtitle">Modifier mes informations</div>
            </div>
        </a>
    </div>
</x-app-layout>
