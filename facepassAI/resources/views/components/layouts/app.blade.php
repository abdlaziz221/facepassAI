<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'FacePassAI' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <div style="display: flex; min-height: 100vh;">

        {{-- Sidebar --}}
        <aside style="width: 250px; background-color: #111827; color: white; display: flex; flex-direction: column;">
            <div style="padding: 1.5rem; border-bottom: 1px solid #374151;">
                <h1 style="font-size: 1.2rem; font-weight: bold;">🎓 FacePassAI</h1>
                <p style="font-size: 0.75rem; color: #9ca3af;">ESP Dakar</p>
            </div>

            <nav style="padding: 1rem; flex: 1;">

                @php $role = Auth::user()->role ?? 'none'; @endphp

                {{-- Liens communs à tous --}}
                <a href="/dashboard" style="display: block; padding: 0.6rem 1rem; border-radius: 0.5rem; color: white; text-decoration: none; margin-bottom: 0.25rem;">🏠 Accueil</a>

                {{-- Employé --}}
                @if($role === 'employe')
                    <a href="/pointage" style="display: block; padding: 0.6rem 1rem; border-radius: 0.5rem; color: white; text-decoration: none; margin-bottom: 0.25rem;">📍 Mon Pointage</a>
                    <a href="/absences/creer" style="display: block; padding: 0.6rem 1rem; border-radius: 0.5rem; color: white; text-decoration: none; margin-bottom: 0.25rem;">📅 Mes Absences</a>
                    <a href="/salaire" style="display: block; padding: 0.6rem 1rem; border-radius: 0.5rem; color: white; text-decoration: none; margin-bottom: 0.25rem;">💰 Mon Salaire</a>
                @endif

                {{-- Consultant --}}
                @if($role === 'consultant')
                    <a href="/employes" style="display: block; padding: 0.6rem 1rem; border-radius: 0.5rem; color: white; text-decoration: none; margin-bottom: 0.25rem;">👥 Employés</a>
                    <a href="/pointages" style="display: block; padding: 0.6rem 1rem; border-radius: 0.5rem; color: white; text-decoration: none; margin-bottom: 0.25rem;">📍 Pointages</a>
                    <a href="/retards" style="display: block; padding: 0.6rem 1rem; border-radius: 0.5rem; color: white; text-decoration: none; margin-bottom: 0.25rem;">⏰ Retards</a>
                    <a href="/rapports" style="display: block; padding: 0.6rem 1rem; border-radius: 0.5rem; color: white; text-decoration: none; margin-bottom: 0.25rem;">📊 Rapports</a>
                @endif

                {{-- Gestionnaire --}}
                @if($role === 'gestionnaire')
                    <a href="/employes" style="display: block; padding: 0.6rem 1rem; border-radius: 0.5rem; color: white; text-decoration: none; margin-bottom: 0.25rem;">👥 Employés</a>
                    <a href="/absences" style="display: block; padding: 0.6rem 1rem; border-radius: 0.5rem; color: white; text-decoration: none; margin-bottom: 0.25rem;">📅 Absences</a>
                    <a href="/pointages" style="display: block; padding: 0.6rem 1rem; border-radius: 0.5rem; color: white; text-decoration: none; margin-bottom: 0.25rem;">📍 Pointages</a>
                    <a href="/retards" style="display: block; padding: 0.6rem 1rem; border-radius: 0.5rem; color: white; text-decoration: none; margin-bottom: 0.25rem;">⏰ Retards</a>
                    <a href="/rapports" style="display: block; padding: 0.6rem 1rem; border-radius: 0.5rem; color: white; text-decoration: none; margin-bottom: 0.25rem;">📊 Rapports</a>
                @endif

                {{-- Administrateur --}}
                @if($role === 'administrateur')
                    <a href="/employes" style="display: block; padding: 0.6rem 1rem; border-radius: 0.5rem; color: white; text-decoration: none; margin-bottom: 0.25rem;">👥 Employés</a>
                    <a href="/gestionnaires" style="display: block; padding: 0.6rem 1rem; border-radius: 0.5rem; color: white; text-decoration: none; margin-bottom: 0.25rem;">🧑‍💼 Gestionnaires</a>
                    <a href="/pointages" style="display: block; padding: 0.6rem 1rem; border-radius: 0.5rem; color: white; text-decoration: none; margin-bottom: 0.25rem;">📍 Pointages</a>
                    <a href="/absences" style="display: block; padding: 0.6rem 1rem; border-radius: 0.5rem; color: white; text-decoration: none; margin-bottom: 0.25rem;">📅 Absences</a>
                    <a href="/jours-travail" style="display: block; padding: 0.6rem 1rem; border-radius: 0.5rem; color: white; text-decoration: none; margin-bottom: 0.25rem;">🗓️ Jours de travail</a>
                    <a href="/rapports" style="display: block; padding: 0.6rem 1rem; border-radius: 0.5rem; color: white; text-decoration: none; margin-bottom: 0.25rem;">📊 Rapports</a>
                    <a href="/logs" style="display: block; padding: 0.6rem 1rem; border-radius: 0.5rem; color: white; text-decoration: none; margin-bottom: 0.25rem;">🔍 Logs système</a>
                @endif

            </nav>

            {{-- Déconnexion --}}
            <div style="padding: 1rem; border-top: 1px solid #374151;">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" style="width: 100%; padding: 0.6rem 1rem; background: #374151; color: white; border: none; border-radius: 0.5rem; cursor: pointer; text-align: left;">
                        🚪 Se déconnecter
                    </button>
                </form>
            </div>

        </aside>

        {{-- Contenu principal --}}
        <main style="flex: 1; padding: 2rem;">

            @if(session('success'))
            <div style="background-color: #dcfce7; color: #166534; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                ✅ {{ session('success') }}
            </div>
            @endif

            @if(session('error'))
            <div style="background-color: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                ❌ {{ session('error') }}
            </div>
            @endif

            {{ $slot }}
        </main>

    </div>

</body>
</html>