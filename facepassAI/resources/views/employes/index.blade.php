<x-app-layout>
    <x-slot name="header">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <span class="pill">Sprint 2</span>
                <h1 style="font-size: 28px; font-weight: 800; color: white; letter-spacing: -0.02em; margin: 12px 0 4px;">
                    Liste des employés
                </h1>
                <p class="text-soft" style="margin: 0;">
                    {{ $employes->total() }} employé(s) au total · page {{ $employes->currentPage() }}/{{ $employes->lastPage() }}
                </p>
            </div>
            @can('create', \App\Models\EmployeProfile::class)
                <a href="{{ route('employes.create') }}" class="btn-primary">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Ajouter un employé
                </a>
            @endcan
        </div>
    </x-slot>

    {{-- Flash messages --}}
    @if (session('success'))
        <div style="margin-bottom: 16px; padding: 12px 16px; border-radius: 10px;
                    background: rgba(34, 197, 94, 0.08); border: 1px solid rgba(34, 197, 94, 0.25);
                    color: #86efac; font-size: 14px;">
            ✓ {{ session('success') }}
        </div>
    @endif

    {{-- Table --}}
    <div class="glass" style="border-radius: 16px; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: rgba(255,255,255,0.025); border-bottom: 1px solid rgba(255,255,255,0.06);">
                <tr style="text-align: left;">
                    <th style="padding: 14px 20px; font-size: 11px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.08em;">Matricule</th>
                    <th style="padding: 14px 20px; font-size: 11px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.08em;">Nom</th>
                    <th style="padding: 14px 20px; font-size: 11px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.08em;">Poste</th>
                    <th style="padding: 14px 20px; font-size: 11px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.08em;">Département</th>
                    <th style="padding: 14px 20px; font-size: 11px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.08em; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employes as $profile)
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                        <td style="padding: 14px 20px; font-size: 13px; color: #c7d2fe; font-family: 'JetBrains Mono', monospace;">
                            {{ $profile->matricule }}
                        </td>
                        <td style="padding: 14px 20px; font-size: 14px; color: white; font-weight: 600;">
                            {{ $profile->user->name }}
                            <div style="font-size: 12px; color: #6b7280; font-weight: 400;">{{ $profile->user->email }}</div>
                        </td>
                        <td style="padding: 14px 20px; font-size: 14px; color: #e5e7eb;">{{ $profile->poste }}</td>
                        <td style="padding: 14px 20px; font-size: 14px; color: #9ca3af;">{{ $profile->departement }}</td>
                        <td style="padding: 14px 20px; text-align: right;">
                            <a href="{{ route('employes.show', $profile) }}" class="link-muted" style="margin-right: 12px;">Voir</a>
                            @can('update', $profile)
                                <a href="{{ route('employes.edit', $profile) }}" class="link-muted" style="margin-right: 12px;">Modifier</a>
                            @endcan
                            @can('delete', $profile)
                                <form method="POST" action="{{ route('employes.destroy', $profile) }}" style="display: inline;"
                                      onsubmit="return confirm('Confirmer la suppression de {{ $profile->user->name }} ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background: none; border: none; color: #fca5a5; cursor: pointer; font-size: 14px;">
                                        Supprimer
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding: 48px; text-align: center; color: #6b7280;">
                            Aucun employé enregistré pour le moment.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div style="margin-top: 16px;">
        {{ $employes->links() }}
    </div>
</x-app-layout>
