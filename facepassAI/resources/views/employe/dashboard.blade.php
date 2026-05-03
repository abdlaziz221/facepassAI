<x-layouts.app>
    <h2 style="font-size: 1.5rem; font-weight: bold; margin-bottom: 1.5rem;">
        Dashboard Employé
    </h2>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
        
        {{-- Carte Pointage --}}
        <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #3b82f6;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">📍</div>
            <h3 style="font-weight: bold; margin-bottom: 0.25rem;">Pointage</h3>
            <p style="color: #6b7280; font-size: 0.875rem;">Enregistrer ma présence</p>
            <a href="/pointage" style="display: inline-block; margin-top: 1rem; background: #3b82f6; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none; font-size: 0.875rem;">
                Pointer maintenant
            </a>
        </div>

        {{-- Carte Demande d'absence --}}
        <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #f59e0b;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">📅</div>
            <h3 style="font-weight: bold; margin-bottom: 0.25rem;">Absences</h3>
            <p style="color: #6b7280; font-size: 0.875rem;">Soumettre une demande</p>
            <a href="/absences/creer" style="display: inline-block; margin-top: 1rem; background: #f59e0b; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none; font-size: 0.875rem;">
                Nouvelle demande
            </a>
        </div>

        {{-- Carte Salaire --}}
        <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #10b981;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">💰</div>
            <h3 style="font-weight: bold; margin-bottom: 0.25rem;">Mon Salaire</h3>
            <p style="color: #6b7280; font-size: 0.875rem;">Consulter mon salaire ajusté</p>
            <a href="/salaire" style="display: inline-block; margin-top: 1rem; background: #10b981; color: white; padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none; font-size: 0.875rem;">
                Voir le détail
            </a>
        </div>

    </div>

    {{-- Mes derniers pointages --}}
    <div style="background: white; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h3 style="font-weight: bold; margin-bottom: 1rem;">🕐 Mes derniers pointages</h3>
        <p style="color: #6b7280; font-size: 0.875rem;">Aucun pointage enregistré pour le moment.</p>
    </div>

</x-layouts.app>