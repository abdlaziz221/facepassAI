<x-app-layout>
    <x-slot name="header">
        <div style="display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <span class="pill" style="background: rgba(239,68,68,0.12);
                      border-color: rgba(239,68,68,0.25); color: #fca5a5;">
                    Administration
                </span>
                <h1 style="font-size: 28px; font-weight: 800; color: white; letter-spacing: -0.02em; margin: 12px 0 4px;">
                    Comptes gestionnaires
                </h1>
                <p class="text-soft" style="margin: 0;">
                    {{ $gestionnaires->total() }} compte(s) actif(s) — créer, modifier, désactiver.
                </p>
            </div>
            <a href="{{ route('admin.gestionnaires.create') }}"
               style="padding: 10px 18px; background: linear-gradient(135deg, #6366f1, #8b5cf6);
                      border: none; border-radius: 8px; color: white; font-size: 14px;
                      font-weight: 600; text-decoration: none; display: inline-flex;
                      align-items: center; gap: 6px;">
                + Nouveau gestionnaire
            </a>
        </div>
    </x-slot>

    {{-- Flash success --}}
    @if (session('success'))
        <div style="margin-bottom: 20px; padding: 14px 18px; background: rgba(16,185,129,0.1);
                    border: 1px solid rgba(16,185,129,0.3); border-radius: 10px;
                    color: #6ee7b7; font-size: 14px;">
            ✓ {{ session('success') }}
        </div>
    @endif

    {{-- Flash mot de passe temporaire --}}
    @if (session('temp_password'))
        <div style="margin-bottom: 20px; padding: 20px; background: rgba(245,158,11,0.08);
                    border: 1px solid rgba(245,158,11,0.3); border-radius: 12px;"
             x-data="{ revealed: false, copied: false }">
            <h3 style="margin: 0 0 8px; color: #fde68a; font-size: 14px; font-weight: 700;">
                🔑 Mot de passe temporaire — à transmettre à {{ session('temp_password_for') }}
            </h3>
            <p style="margin: 0 0 12px; color: #fcd34d; font-size: 13px;">
                Ce mot de passe ne sera affiché qu'une seule fois. Notez-le ou copiez-le maintenant.
                Le gestionnaire devra le changer à sa première connexion.
            </p>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <code style="padding: 10px 14px; background: rgba(0,0,0,0.4);
                             border: 1px solid rgba(245,158,11,0.4); border-radius: 8px;
                             color: white; font-size: 16px; font-family: monospace; letter-spacing: 0.05em;">
                    <span x-show="!revealed">••••••••••••</span>
                    <span x-show="revealed" x-cloak>{{ session('temp_password') }}</span>
                </code>
                <button type="button" @click="revealed = !revealed"
                        style="padding: 8px 14px; background: rgba(255,255,255,0.05);
                               border: 1px solid rgba(255,255,255,0.15); border-radius: 8px;
                               color: white; font-size: 13px; cursor: pointer;">
                    <span x-show="!revealed">👁 Afficher</span>
                    <span x-show="revealed" x-cloak>👁‍🗨 Masquer</span>
                </button>
                <button type="button"
                        @click="navigator.clipboard.writeText('{{ session('temp_password') }}').then(() => { copied = true; setTimeout(() => copied = false, 2000); })"
                        style="padding: 8px 14px; background: linear-gradient(135deg, #f59e0b, #d97706);
                               border: none; border-radius: 8px; color: white; font-size: 13px;
                               font-weight: 600; cursor: pointer;">
                    <span x-show="!copied">📋 Copier</span>
                    <span x-show="copied" x-cloak>✓ Copié !</span>
                </button>
            </div>
        </div>
    @endif

    {{-- Erreurs --}}
    @if ($errors->any())
        <div style="margin-bottom: 20px; padding: 14px 18px; background: rgba(239,68,68,0.1);
                    border: 1px solid rgba(239,68,68,0.3); border-radius: 10px;
                    color: #fca5a5; font-size: 14px;">
            @foreach ($errors->all() as $error)
                <div>⚠ {{ $error }}</div>
            @endforeach
        </div>
    @endif

    {{-- Alerte demandes en attente --}}
    @if ($pendingDemandes > 0)
        <div style="margin-bottom: 20px; padding: 14px 18px; background: rgba(245,158,11,0.08);
                    border: 1px solid rgba(245,158,11,0.25); border-radius: 10px;
                    color: #fde68a; font-size: 13px;">
            ⚠ Il reste <strong>{{ $pendingDemandes }}</strong> demande(s) d'absence en attente.
            La suppression du dernier gestionnaire est bloquée tant que ces demandes ne sont pas traitées.
        </div>
    @endif

    {{-- Tableau --}}
    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
                border-radius: 12px; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="background: rgba(255,255,255,0.04);">
                <tr>
                    <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Nom</th>
                    <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Email</th>
                    <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Statut</th>
                    <th style="text-align: left; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Créé le</th>
                    <th style="text-align: right; padding: 12px 16px; font-size: 12px;
                               text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af;
                               border-bottom: 1px solid rgba(255,255,255,0.06);">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($gestionnaires as $g)
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                        <td style="padding: 14px 16px; color: white; font-size: 14px; font-weight: 500;">
                            {{ $g->name }}
                        </td>
                        <td style="padding: 14px 16px; color: #d1d5db; font-size: 13px;">
                            {{ $g->email }}
                        </td>
                        <td style="padding: 14px 16px;">
                            @if ($g->est_actif)
                                <span style="display: inline-block; padding: 4px 10px; border-radius: 999px;
                                             background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3);
                                             color: #6ee7b7; font-size: 12px; font-weight: 600;">
                                    Actif
                                </span>
                            @else
                                <span style="display: inline-block; padding: 4px 10px; border-radius: 999px;
                                             background: rgba(107,114,128,0.12); border: 1px solid rgba(107,114,128,0.3);
                                             color: #9ca3af; font-size: 12px; font-weight: 600;">
                                    Désactivé
                                </span>
                            @endif
                        </td>
                        <td style="padding: 14px 16px; color: #9ca3af; font-size: 13px;">
                            {{ $g->created_at->format('d/m/Y') }}
                        </td>
                        <td style="padding: 14px 16px; text-align: right;">
                            <div style="display: inline-flex; gap: 6px;">
                                <a href="{{ route('admin.gestionnaires.edit', $g) }}"
                                   style="padding: 6px 12px; background: rgba(99,102,241,0.12);
                                          border: 1px solid rgba(99,102,241,0.25); border-radius: 6px;
                                          color: #a5b4fc; font-size: 12px; text-decoration: none;
                                          font-weight: 500;">
                                    Modifier
                                </a>
                                <form method="POST" action="{{ route('admin.gestionnaires.destroy', $g) }}"
                                      style="display: inline;"
                                      onsubmit="return confirm('Supprimer définitivement le compte de {{ addslashes($g->name) }} ? Cette action est irréversible.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            style="padding: 6px 12px; background: rgba(239,68,68,0.12);
                                                   border: 1px solid rgba(239,68,68,0.25); border-radius: 6px;
                                                   color: #fca5a5; font-size: 12px; font-weight: 500;
                                                   cursor: pointer;">
                                        Supprimer
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding: 40px 16px; text-align: center; color: #6b7280; font-size: 14px;">
                            Aucun gestionnaire pour le moment.
                            <div style="margin-top: 10px;">
                                <a href="{{ route('admin.gestionnaires.create') }}"
                                   style="color: #818cf8; font-size: 13px;">
                                    Créer le premier compte →
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($gestionnaires->hasPages())
        <div style="margin-top: 20px;">
            {{ $gestionnaires->links() }}
        </div>
    @endif
</x-app-layout>
