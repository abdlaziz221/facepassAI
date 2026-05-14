<x-app-layout>
    <x-slot name="header">
        <div>
            <span class="pill">Rapports</span>
            <h1 style="font-size: 28px; font-weight: 800; color: white; letter-spacing: -0.02em; margin: 12px 0 4px;">
                Générer un rapport
            </h1>
            <p class="text-soft" style="margin: 0;">
                Choisissez la période, le type et le format — téléchargement direct.
            </p>
        </div>
    </x-slot>

    {{-- Sprint 5 carte 5 — Avertissement si horaires non configurés --}}
    <x-horaires-warning />

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

    <form method="POST" action="{{ route('rapports.generer') }}"
          style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
                 border-radius: 12px; padding: 24px;">
        @csrf

        {{-- Section : Type --}}
        <h2 style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.08em;
                   color: #9ca3af; margin: 0 0 12px;">1. Type de rapport</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 10px; margin-bottom: 24px;">
            <label style="display: block; padding: 14px; cursor: pointer;
                          background: rgba(99,102,241,0.08);
                          border: 1px solid rgba(99,102,241,0.3); border-radius: 10px;">
                <input type="radio" name="type" value="presences" checked style="margin-right: 8px;">
                <strong style="color: white;">Présences</strong>
                <div style="font-size: 12px; color: #9ca3af; margin-top: 4px;">
                    Tous les pointages de la période avec statut.
                </div>
            </label>
        </div>

        {{-- Section : Période --}}
        <h2 style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.08em;
                   color: #9ca3af; margin: 0 0 12px;">2. Période</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 12px; margin-bottom: 24px;">
            <div>
                <label style="display: block; font-size: 12px; color: #9ca3af; margin-bottom: 6px;">
                    Du <span style="color:#fca5a5;">*</span>
                </label>
                <input type="date" name="date_debut" required
                       value="{{ old('date_debut', now()->startOfMonth()->format('Y-m-d')) }}"
                       style="width: 100%; padding: 10px 12px; background: rgba(0,0,0,0.4);
                              border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
                              color: white; font-size: 14px;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; color: #9ca3af; margin-bottom: 6px;">
                    Au <span style="color:#fca5a5;">*</span>
                </label>
                <input type="date" name="date_fin" required
                       value="{{ old('date_fin', now()->format('Y-m-d')) }}"
                       style="width: 100%; padding: 10px 12px; background: rgba(0,0,0,0.4);
                              border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
                              color: white; font-size: 14px;">
            </div>
        </div>

        {{-- Section : Filtre employé --}}
        <h2 style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.08em;
                   color: #9ca3af; margin: 0 0 12px;">3. Périmètre (optionnel)</h2>
        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 12px; color: #9ca3af; margin-bottom: 6px;">
                Employé
            </label>
            <select name="employe_id"
                    style="width: 100%; padding: 10px 12px; background: rgba(0,0,0,0.4);
                           border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
                           color: white; font-size: 14px;">
                <option value="">— Tous les employés —</option>
                @foreach ($employes as $emp)
                    <option value="{{ $emp->id }}" {{ (int) old('employe_id') === $emp->id ? 'selected' : '' }}>
                        {{ $emp->user->name ?? ('#' . $emp->id) }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Section : Format --}}
        <h2 style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.08em;
                   color: #9ca3af; margin: 0 0 12px;">4. Format</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 10px; margin-bottom: 28px;">
            <label style="display: block; padding: 14px; cursor: pointer;
                          background: rgba(239,68,68,0.06);
                          border: 1px solid rgba(239,68,68,0.25); border-radius: 10px;">
                <input type="radio" name="format" value="pdf" checked style="margin-right: 8px;">
                <strong style="color: #fca5a5;">📄 PDF</strong>
                <div style="font-size: 12px; color: #9ca3af; margin-top: 4px;">
                    Document imprimable A4, prêt à classer.
                </div>
            </label>
            <label style="display: block; padding: 14px; cursor: pointer;
                          background: rgba(16,185,129,0.06);
                          border: 1px solid rgba(16,185,129,0.25); border-radius: 10px;">
                <input type="radio" name="format" value="excel" style="margin-right: 8px;">
                <strong style="color: #6ee7b7;">📊 Excel</strong>
                <div style="font-size: 12px; color: #9ca3af; margin-top: 4px;">
                    Tableur (.xlsx) avec filtres et tri.
                </div>
            </label>
        </div>

        <button type="submit"
                style="padding: 12px 24px; background: linear-gradient(135deg, #6366f1, #8b5cf6);
                       border: none; border-radius: 10px; color: white; font-size: 14px;
                       font-weight: 600; cursor: pointer;">
            ⬇ Générer & télécharger
        </button>
    </form>
</x-app-layout>
