<x-layouts.app>
    <h2 style="font-size: 1.5rem; font-weight: bold; margin-bottom: 1.5rem;">
        Dashboard Gestionnaire
    </h2>

    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem;">

        {{-- Carte Employés --}}
        <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #3b82f6;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">👥</div>
            <h3 style="font-weight: bold; margin-bottom: 0.25rem;">Employés</h3>
            <p style="color: #6b7280; font-size: 0.875rem;">Gérer les employés</p>
            <a href="/employes" style="display: inline-block; margin-top: 1rem; background: #3b82f6; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none; font-size: 0.875rem;">
                Gérer
            </a>
        </div>

        {{-- Carte Absences --}}
        <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #f59e0b;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">📅</div>
            <h3 style="font-weight: bold; margin-bottom: 0.25rem;">Absences</h3>
            <p style="color: #6b7280; font-size: 0.875rem;">Demandes en attente</p>
            <a href="/absences" style="display: inline-block; margin-top: 1rem; background: #f59e0b; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none; font-size: 0.875rem;">
                Traiter
            </a>
        </div>

        {{-- Carte Pointages --}}
        <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #8b5cf6;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">📍</div>
            <h3 style="font-weight: bold; margin-bottom: 0.25rem;">Pointages</h3>
            <p style="color: #6b7280; font-size: 0.875rem;">Historique des pointages</p>
            <a href="/pointages" style="display: inline-block; margin-top: 1rem; background: #8b5cf6; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none; font-size: 0.875rem;">
                Consulter
            </a>
        </div>

        {{-- Carte Rapports --}}
        <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #10b981;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">📊</div>
            <h3 style="font-weight: bold; margin-bottom: 0.25rem;">Rapports</h3>
            <p style="color: #6b7280; font-size: 0.875rem;">Exporter les rapports</p>
            <a href="/rapports" style="display: inline-block; margin-top: 1rem; background: #10b981; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none; font-size: 0.875rem;">
                Exporter
            </a>
        </div>

    </div>

    {{-- Demandes d'absence en attente --}}
    <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h3 style="font-weight: bold; margin-bottom: 1rem;">📋 Demandes d'absence en attente</h3>
        <p style="color: #6b7280; font-size: 0.875rem;">Aucune demande en attente pour le moment.</p>
    </div>

</x-layouts.app>