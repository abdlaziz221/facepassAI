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
                <a href="/employes" style="display: block; padding: 0.6rem 1rem; border-radius: 0.5rem; color: white; text-decoration: none; margin-bottom: 0.25rem;">👥 Employés</a>
                <a href="/pointages" style="display: block; padding: 0.6rem 1rem; border-radius: 0.5rem; color: white; text-decoration: none; margin-bottom: 0.25rem;">📍 Pointages</a>
                <a href="/absences" style="display: block; padding: 0.6rem 1rem; border-radius: 0.5rem; color: white; text-decoration: none; margin-bottom: 0.25rem;">📅 Absences</a>
                <a href="/rapports" style="display: block; padding: 0.6rem 1rem; border-radius: 0.5rem; color: white; text-decoration: none; margin-bottom: 0.25rem;">📊 Rapports</a>
                <a href="/logs" style="display: block; padding: 0.6rem 1rem; border-radius: 0.5rem; color: white; text-decoration: none; margin-bottom: 0.25rem;">🔍 Logs</a>
            </nav>
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