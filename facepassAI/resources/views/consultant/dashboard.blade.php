<x-layouts.app>
    <h2 style="font-size: 1.5rem; font-weight: bold; margin-bottom: 1.5rem;">
        Dashboard Consultant
    </h2>

    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem;">

        {{-- Carte Employés --}}
        <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #3b82f6;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">👥</div>
            <h3 style="font-weight: bold; margin-bottom: 0.25rem;">Employés</h3>
            <p style="color: #6b7280; font-size: 0.875rem;">Consulter la liste</p>
            <a href="/employes" style="display: inline-block; margin-top: 1rem; background: #3b82f6; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none; font-size: 0.875rem;">
                Voir la liste
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

        {{-- Carte Retards --}}
        <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #ef4444;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">⏰</div>
            <h3 style="font-weight: bold; margin-bottom: 0.25rem;">Retards</h3>
            <p style="color: #6b7280; font-size: 0.875rem;">Retards & départs anticipés</p>
            <a href="/retards" style="display: inline-block; margin-top: 1rem; background: #ef4444; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none; font-size: 0.875rem;">
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

    {{-- Accès en lecture seule --}}
    <div style="background: #fefce8; border: 1px solid #fde047; padding: 1rem; border-radius: 0.75rem;">
        <p style="color: #854d0e; font-size: 0.875rem;">⚠️ Vous avez un accès en <strong>lecture seule</strong>. Aucune modification n'est autorisée.</p>
    </div>

</x-layouts.app>