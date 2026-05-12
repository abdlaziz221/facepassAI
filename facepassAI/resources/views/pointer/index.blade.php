<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>FacePass AI — Pointage biométrique</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Inter', sans-serif; }

        @keyframes pulse-ring {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50%      { transform: scale(1.08); opacity: 1; }
        }
        .pulse-ring { animation: pulse-ring 2s ease-in-out infinite; }

        @keyframes spin-slow {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }
        .spin-slow { animation: spin-slow 8s linear infinite; }

        @keyframes scan-line {
            0%   { top: 0%;   opacity: 1; }
            50%  { top: 100%; opacity: 0.5; }
            100% { top: 0%;   opacity: 1; }
        }
        .scan-line { animation: scan-line 4s ease-in-out infinite; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-950 via-black to-slate-950 text-white antialiased overflow-x-hidden">

    {{-- Halo de fond --}}
    <div class="fixed inset-0 pointer-events-none">
        <div class="absolute top-1/4 -left-32 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-1/4 -right-32 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl"></div>
    </div>

    <div class="relative container mx-auto px-6 py-8 max-w-5xl">

        {{-- Header --}}
        <header class="text-center mb-8">
            <div class="inline-flex items-center gap-3">
                <div class="relative w-12 h-12">
                    <div class="absolute inset-0 rounded-full border-2 border-cyan-400/30 spin-slow"></div>
                    <div class="absolute inset-2 rounded-full bg-cyan-400/20"></div>
                    <div class="absolute inset-0 flex items-center justify-center text-cyan-400 text-2xl">⊙</div>
                </div>
                <h1 class="text-4xl font-bold tracking-tight">
                    Face<span class="text-cyan-400">Pass</span> AI
                </h1>
            </div>
            <p class="text-slate-400 mt-3 text-sm uppercase tracking-widest">Pointage biométrique</p>
        </header>

        {{-- Caméra --}}
        <div class="relative max-w-3xl mx-auto">
            <div class="aspect-video relative bg-slate-900 rounded-3xl overflow-hidden ring-1 ring-cyan-500/30 shadow-2xl shadow-cyan-500/10">
                <video id="camera-preview" autoplay playsinline muted class="w-full h-full object-cover"></video>
                <canvas id="canvas-capture" class="hidden"></canvas>

                {{-- Overlay scan-line décoratif --}}
                <div id="overlay-decor" class="hidden absolute inset-0 pointer-events-none">
                    <div class="scan-line absolute w-full h-px bg-cyan-400/70 shadow-[0_0_10px_2px_rgba(34,211,238,0.5)]"></div>
                    <div class="absolute top-3 left-3 w-8 h-8 border-t-2 border-l-2 border-cyan-400/70 rounded-tl-xl"></div>
                    <div class="absolute top-3 right-3 w-8 h-8 border-t-2 border-r-2 border-cyan-400/70 rounded-tr-xl"></div>
                    <div class="absolute bottom-3 left-3 w-8 h-8 border-b-2 border-l-2 border-cyan-400/70 rounded-bl-xl"></div>
                    <div class="absolute bottom-3 right-3 w-8 h-8 border-b-2 border-r-2 border-cyan-400/70 rounded-br-xl"></div>
                </div>

                {{-- Overlay statut (démarrage / erreur) --}}
                <div id="camera-status" class="absolute inset-0 flex items-center justify-center bg-slate-950/90 backdrop-blur-sm">
                    <div class="text-center px-6">
                        <div class="pulse-ring w-20 h-20 mx-auto mb-4 rounded-full border-4 border-cyan-400"></div>
                        <p id="camera-status-text" class="text-slate-300 font-medium">Initialisation de la caméra…</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Boutons --}}
        <div class="mt-8 grid grid-cols-2 md:grid-cols-4 gap-3 max-w-3xl mx-auto">
            <button data-type="arrivee" disabled
                class="action-btn py-5 px-3 rounded-2xl font-medium transition-all duration-200 bg-emerald-600/90 hover:bg-emerald-500 active:scale-95 disabled:opacity-30 disabled:cursor-not-allowed shadow-lg shadow-emerald-500/20">
                <div class="text-2xl mb-1">→</div>
                <div class="text-sm">Arrivée</div>
            </button>
            <button data-type="debut_pause" disabled
                class="action-btn py-5 px-3 rounded-2xl font-medium transition-all duration-200 bg-amber-600/90 hover:bg-amber-500 active:scale-95 disabled:opacity-30 disabled:cursor-not-allowed shadow-lg shadow-amber-500/20">
                <div class="text-2xl mb-1">⏸</div>
                <div class="text-sm">Début pause</div>
            </button>
            <button data-type="fin_pause" disabled
                class="action-btn py-5 px-3 rounded-2xl font-medium transition-all duration-200 bg-blue-600/90 hover:bg-blue-500 active:scale-95 disabled:opacity-30 disabled:cursor-not-allowed shadow-lg shadow-blue-500/20">
                <div class="text-2xl mb-1">▶</div>
                <div class="text-sm">Fin pause</div>
            </button>
            <button data-type="depart" disabled
                class="action-btn py-5 px-3 rounded-2xl font-medium transition-all duration-200 bg-rose-600/90 hover:bg-rose-500 active:scale-95 disabled:opacity-30 disabled:cursor-not-allowed shadow-lg shadow-rose-500/20">
                <div class="text-2xl mb-1">←</div>
                <div class="text-sm">Départ</div>
            </button>
        </div>

        {{-- Feedback --}}
        <div id="feedback" class="hidden mt-8 max-w-3xl mx-auto p-5 rounded-2xl text-center font-medium border backdrop-blur-sm">
            <div id="feedback-icon" class="text-3xl mb-2"></div>
            <div id="feedback-text"></div>
        </div>

        {{-- Footer --}}
        <footer class="mt-12 text-center text-xs text-slate-500">
            <p>FacePass AI — ESP Dakar 2026 · Aucune image n'est conservée (BNF-06)</p>
        </footer>
    </div>

    <script>
    (function () {
        'use strict';

        const video       = document.getElementById('camera-preview');
        const canvas      = document.getElementById('canvas-capture');
        const statusEl    = document.getElementById('camera-status');
        const statusText  = document.getElementById('camera-status-text');
        const overlay     = document.getElementById('overlay-decor');
        const feedback    = document.getElementById('feedback');
        const feedbackIcon= document.getElementById('feedback-icon');
        const feedbackTxt = document.getElementById('feedback-text');
        const buttons     = document.querySelectorAll('.action-btn');

        let cameraReady = false;
        let inFlight    = false;

        // ----------------------------------------------------------
        // 1. Démarrer la caméra
        // ----------------------------------------------------------
        async function startCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { width: 1280, height: 720, facingMode: 'user' },
                    audio: false,
                });
                video.srcObject = stream;
                await video.play();

                cameraReady = true;
                statusEl.classList.add('hidden');
                overlay.classList.remove('hidden');
                buttons.forEach(b => b.disabled = false);
            } catch (err) {
                statusText.textContent = "Caméra inaccessible. Autorisez l'accès puis rechargez.";

                // Sprint 4 US-036 : fallback caméra → lien vers le mode manuel (gestionnaire)
                const fallback = document.createElement('a');
                fallback.href = '{{ route('pointages.manual.create') }}';
                fallback.textContent = '🛠  Mode pointage manuel (gestionnaire)';
                fallback.className = 'inline-block mt-6 px-4 py-2 rounded-lg bg-cyan-500/15 border border-cyan-400/40 text-cyan-300 hover:bg-cyan-500/25 hover:text-cyan-200 transition no-underline';
                statusText.parentElement.appendChild(fallback);

                console.error('getUserMedia failed:', err);
            }
        }

        // ----------------------------------------------------------
        // 2. Capturer une frame en JPEG blob
        // ----------------------------------------------------------
        function capture() {
            const ctx = canvas.getContext('2d');
            canvas.width  = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0);
            return new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.85));
        }

        // ----------------------------------------------------------
        // 3. Envoyer au backend
        // ----------------------------------------------------------
        async function pointer(type) {
            if (!cameraReady || inFlight) return;
            inFlight = true;
            buttons.forEach(b => b.disabled = true);

            showFeedback('loading', '⏳', 'Reconnaissance en cours…');

            try {
                const blob = await capture();
                const fd   = new FormData();
                fd.append('photo', blob, 'capture.jpg');
                fd.append('type', type);

                const csrf = document.querySelector('meta[name="csrf-token"]').content;
                const res  = await fetch('{{ route('pointages.store') }}', {
                    method:  'POST',
                    body:    fd,
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    const nom = data.employe?.nom || data.employe?.matricule || 'Employé';
                    const conf = Math.round((data.confidence ?? 0) * 100);
                    showFeedback('success', '✓', `Bonjour ${nom} — pointage ${type} enregistré (confiance ${conf} %)`);
                } else {
                    showFeedback('error', '✗', data.message || 'Erreur inconnue.');
                }
            } catch (err) {
                showFeedback('error', '✗', `Erreur réseau : ${err.message}`);
            } finally {
                setTimeout(() => {
                    inFlight = false;
                    buttons.forEach(b => b.disabled = false);
                }, 1500);
            }
        }

        // ----------------------------------------------------------
        // 4. Feedback UI
        // ----------------------------------------------------------
        function showFeedback(type, icon, message) {
            feedback.classList.remove(
                'hidden',
                'bg-emerald-500/15','border-emerald-500/40','text-emerald-200',
                'bg-rose-500/15','border-rose-500/40','text-rose-200',
                'bg-cyan-500/15','border-cyan-500/40','text-cyan-200'
            );
            if (type === 'success') {
                feedback.classList.add('bg-emerald-500/15','border-emerald-500/40','text-emerald-200');
            } else if (type === 'error') {
                feedback.classList.add('bg-rose-500/15','border-rose-500/40','text-rose-200');
            } else {
                feedback.classList.add('bg-cyan-500/15','border-cyan-500/40','text-cyan-200');
            }
            feedbackIcon.textContent = icon;
            feedbackTxt.textContent  = message;

            if (type !== 'loading') {
                setTimeout(() => feedback.classList.add('hidden'), 6000);
            }
        }

        // ----------------------------------------------------------
        // 5. Init
        // ----------------------------------------------------------
        buttons.forEach(b => b.addEventListener('click', () => pointer(b.dataset.type)));
        startCamera();
    })();
    </script>
</body>
</html>
