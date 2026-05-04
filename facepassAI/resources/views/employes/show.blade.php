<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('employes.index') }}" class="link-muted" style="font-size: 13px;">← Retour à la liste</a>
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-top: 8px;">
            <div>
                <span class="pill">{{ $profile->matricule }}</span>
                <h1 style="font-size: 28px; font-weight: 800; color: white; letter-spacing: -0.02em; margin: 12px 0 4px;">
                    {{ $profile->user->name }}
                </h1>
                <p class="text-soft" style="margin: 0;">{{ $profile->poste }} · {{ $profile->departement }}</p>
            </div>
            @can('update', $profile)
                <a href="{{ route('employes.edit', $profile) }}" class="btn-primary">Modifier</a>
            @endcan
        </div>
    </x-slot>

    @if (session('success'))
        <div style="margin-bottom: 16px; padding: 12px 16px; border-radius: 10px;
                    background: rgba(34, 197, 94, 0.08); border: 1px solid rgba(34, 197, 94, 0.25);
                    color: #86efac; font-size: 14px;">
            ✓ {{ session('success') }}
        </div>
    @endif

    {{-- Carte d'identité --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
        <div class="card card-stat">
            <div class="label">Email</div>
            <div style="margin-top: 8px; font-size: 16px; color: white; word-break: break-all;">{{ $profile->user->email }}</div>
        </div>
        <div class="card card-stat">
            <div class="label">Statut</div>
            <div style="margin-top: 8px;">
                @if ($profile->user->est_actif)
                    <span class="pill pill-success">Actif</span>
                @else
                    <span class="pill pill-danger">Désactivé</span>
                @endif
            </div>
        </div>
        <div class="card card-stat">
            <div class="label">Salaire brut</div>
            <div class="value">{{ number_format($profile->salaire_brut, 0, ',', ' ') }}<span style="font-size: 14px; color: #6b7280;"> FCFA</span></div>
        </div>
        <div class="card card-stat">
            <div class="label">Membre depuis</div>
            <div style="margin-top: 8px; font-size: 16px; color: white;">{{ $profile->user->created_at->format('d/m/Y') }}</div>
        </div>
    </div>

    <h2 class="section-title">Détails du poste</h2>
    <div class="glass" style="padding: 24px; border-radius: 16px;">
        <dl style="display: grid; grid-template-columns: 1fr 2fr; gap: 12px; margin: 0;">
            <dt style="color: #9ca3af; font-size: 13px;">Matricule</dt>
            <dd style="margin: 0; color: white; font-family: 'JetBrains Mono', monospace;">{{ $profile->matricule }}</dd>
            <dt style="color: #9ca3af; font-size: 13px;">Poste</dt>
            <dd style="margin: 0; color: white;">{{ $profile->poste }}</dd>
            <dt style="color: #9ca3af; font-size: 13px;">Département</dt>
            <dd style="margin: 0; color: white;">{{ $profile->departement }}</dd>
            <dt style="color: #9ca3af; font-size: 13px;">Photo faciale</dt>
            <dd style="margin: 0; color: #6b7280; font-style: italic;">
                {{ $profile->photo_faciale ?: 'Non encore enregistrée (Sprint 3)' }}
            </dd>
        </dl>
    </div>

    {{-- Pointages récents (Sprint 3) — Demandes en cours (Sprint 4) — Sera enrichi à T11 --}}
    <h2 class="section-title">Activité récente</h2>
    <div class="glass" style="padding: 32px; border-radius: 16px; text-align: center; color: #6b7280;">
        Pointages et demandes d'absence — disponibles à partir du Sprint 3.
    </div>
</x-app-layout>
