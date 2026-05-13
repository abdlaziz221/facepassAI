@php
    /** Sprint 5 carte 5 (US-063) — Bannière d'avertissement si les horaires
     *  de travail n'ont pas été configurés par l'admin (encore sur les defaults).
     */
    $config = \App\Models\JoursTravail::current();
@endphp

@if (!$config->isConfigured())
    <div style="margin-bottom: 20px; padding: 16px 20px;
                background: linear-gradient(135deg, rgba(245,158,11,0.08), rgba(245,158,11,0.04));
                border: 1px solid rgba(245,158,11,0.3);
                border-radius: 12px;
                display: flex; align-items: center; justify-content: space-between;
                flex-wrap: wrap; gap: 16px;">
        <div style="flex: 1; min-width: 240px;">
            <h3 style="margin: 0 0 4px; color: #fde68a; font-size: 14px; font-weight: 700;
                       display: flex; align-items: center; gap: 6px;">
                ⚠ Horaires de travail non configurés
            </h3>
            <p style="margin: 0; color: #fcd34d; font-size: 13px; line-height: 1.5;">
                Les calculs utilisent les valeurs par défaut (lun-ven, 8h-17h, pause 12h-13h).
                Configurez les horaires de votre entreprise pour des résultats fiables.
            </p>
        </div>
        @can('horaires.configure')
            <a href="{{ route('admin.horaires.edit') }}"
               style="padding: 10px 18px; background: linear-gradient(135deg, #f59e0b, #d97706);
                      border: none; border-radius: 8px; color: white; font-size: 13px;
                      font-weight: 600; text-decoration: none; white-space: nowrap;
                      display: inline-flex; align-items: center; gap: 6px;">
                Configurer maintenant →
            </a>
        @endcan
    </div>
@endif
