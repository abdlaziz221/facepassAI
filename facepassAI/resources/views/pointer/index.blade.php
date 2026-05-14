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

        /* Feedback overlay animations */
        @keyframes fade-in {
            from { opacity: 0; transform: scale(0.95); }
            to   { opacity: 1; transform: scale(1); }
        }
        .fade-in { animation: fade-in 0.25s ease-out; }

        @keyframes success-pop {
            0%   { transform: scale(0.3); opacity: 0; }
            50%  { transform: scale(1.1); opacity: 1; }
            100% { transform: scale(1);   opacity: 1; }
        }
        .success-pop { animation: success-pop 0.5s ease-out; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-950 via-black to-slate-950 text-white antialiased overflow-hidden">

    {{-- Halo de fond --}}
    <div class="fixed inset-0 pointer-events-none">
        <div class="absolute top-1/4 -left-32 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-1/4 -right-32 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl"></div>
    </div>

    <div class="relative h-screen flex flex-col px-6 py-4 max-w-5xl mx-auto">

        {{-- Header compact (sur une ligne) --}}
        <header class="flex items-center justify-center gap-3 flex-shrink-0">
            <div class="relative w-10 h-10">
                <div class="absolute inset-0 rounded-full border-2 border-cyan-400/30 spin-slow"></div>
                <div class="absolute inset-2 rounded-full bg-cyan-400/20"></div>
                <div class="absolute inset-0 flex items-center justify-center text-cyan-400 text-xl">⊙</div>
            </div>
            <div>
                <h1 class="text-2xl font-bold tracking-tight leading-none">
                    Face<span class="text-cyan-400">Pass</span> AI
                </h1>
                <p class="text-slate-400 text-xs uppercase tracking-widest mt-0.5">Pointage biométrique</p>
            </div>
        </header>

        {{-- Caméra (avec tous les états en overlay : init, loading, success, error) --}}
        <div class="flex-1 flex items-center justify-center min-h-0 my-4">
            <div class="relative w-full max-w-3xl">
                <div class="aspect-video relative bg-slate-900 rounded-3xl overflow-hidden ring-1 ring-cyan-500/30 shadow-2xl shadow-cyan-500/10">
                    <video id="camera-preview" autoplay playsinline muted class="w-full h-full object-cover"></video>
                    <canvas id="canvas-capture" class="hidden"></canvas>

                    {{-- Décor scan-line (caméra prête) --}}
                    <div id="overlay-decor" class="hidden absolute inset-0 pointer-events-none">
                        <div class="scan-line absolute w-full h-px bg-cyan-400/70 shadow-[0_0_10px_2px_rgba(34,211,238,0.5)]"></div>
                        <div class="absolute top-3 left-3 w-8 h-8 border-t-2 border-l-2 border-cyan-400/70 rounded-tl-xl"></div>
                        <div class="absolute top-3 right-3 w-8 h-8 border-t-2 border-r-2 border-cyan-400/70 rounded-tr-xl"></div>
                        <div class="absolute bottom-3 left-3 w-8 h-8 border-b-2 border-l-2 border-cyan-400/70 rounded-bl-xl"></div>
                        <div class="absolute bottom-3 right-3 w-8 h-8 border-b-2 border-r-2 border-cyan-400/70 rounded-br-xl"></div>
                    </div>

                    {{-- Overlay init caméra --}}
                    <div id="camera-status" class="absolute inset-0 flex items-center justify-center bg-slate-950/90 backdrop-blur-sm z-10">
                        <div class="text-center px-6">
                            <div class="pulse-ring w-20 h-20 mx-auto mb-4 rounded-full border-4 border-cyan-400"></div>
                            <p id="camera-status-text" class="text-slate-300 font-medium">Initialisation de la caméra…</p>
                        </div>
                    </div>

                    {{-- Overlay scan en cours (reconnaissance) --}}
                    <div id="overlay-loading" class="hidden absolute inset-0 flex items-center justify-center bg-slate-950/85 backdrop-blur-sm z-20 fade-in">
                        <div class="text-center px-6">
                            <div class="relative w-24 h-24 mx-auto mb-5">
                                <div class="absolute inset-0 rounded-full border-4 border-cyan-400/30"></div>
                                <div class="absolute inset-0 rounded-full border-4 border-transparent border-t-cyan-400 spin-slow"
                                     style="animation-duration: 1s;"></div>
                                <div class="absolute inset-0 flex items-center justify-center text-cyan-400 text-3xl">⊙</div>
                            </div>
                            <p class="text-cyan-200 font-medium text-lg">Reconnaissance en cours…</p>
                            <p class="text-cyan-400/60 text-xs mt-2 uppercase tracking-widest">Analyse biométrique</p>
                        </div>
                    </div>

                    {{-- Overlay succès --}}
                    <div id="overlay-success" class="hidden absolute inset-0 flex items-center justify-center bg-emerald-950/90 backdrop-blur-sm z-20 fade-in">
                        <div class="text-center px-6 max-w-md">
                            <div class="success-pop w-24 h-24 mx-auto mb-5 rounded-full bg-emerald-500/20 border-4 border-emerald-400 flex items-center justify-center">
                                <span class="text-emerald-300 text-5xl">✓</span>
                            </div>
                            <p id="success-title" class="text-emerald-200 font-semibold text-xl mb-1">Bonjour</p>
                            <p id="success-detail" class="text-emerald-300/80 text-sm"></p>
                        </div>
                    </div>

                    {{-- Overlay erreur --}}
                    <div id="overlay-error" class="hidden absolute inset-0 flex items-center justify-center bg-rose-950/90 backdrop-blur-sm z-20 fade-in">
                        <div class="text-center px-6 max-w-md">
                            <div class="success-pop w-24 h-24 mx-auto mb-5 rounded-full bg-rose-500/20 border-4 border-rose-400 flex items-center justify-center">
                                <span class="text-rose-300 text-5xl">✕</span>
                            </div>
                            <p id="error-title" class="text-rose-200 font-semibold text-xl mb-1">Échec</p>
                            <p id="error-detail" class="text-rose-300/80 text-sm"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Boutons (toujours visibles en bas) --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 max-w-3xl mx-auto w-full flex-shrink-0">
            <button data-type="arrivee" disabled
                class="action-btn py-4 px-3 rounded-2xl font-medium transition-all duration-200 bg-emerald-600/90 hover:bg-emerald-500 active:scale-95 disabled:opacity-30 disabled:cursor-not-allowed shadow-lg shadow-emerald-500/20">
                <div class="text-2xl mb-1">→</div>
                <div class="text-sm">Arrivée</div>
            </button>
            <button data-type="debut_pause" disabled
                class="action-btn py-4 px-3 rounded-2xl font-medium transition-all duration-200 bg-amber-600/90 hover:bg-amber-500 active:scale-95 disabled:opacity-30 disabled:cursor-not-allowed shadow-lg shadow-amber-500/20">
                <div class="text-2xl mb-1">⏸</div>
                <div class="text-sm">Début pause</div>
            </button>
            <button data-type="fin_pause" disabled
                class="action-btn py-4 px-3 rounded-2xl font-medium transition-all duration-200 bg-blue-600/90 hover:bg-blue-500 active:scale-95 disabled:opacity-30 disabled:cursor-not-allowed shadow-lg shadow-blue-500/20">
                <div class="text-2xl mb-1">▶</div>
                <div class="text-sm">Fin pause</div>
            </button>
            <button data-type="depart" disabled
                class="action-btn py-4 px-3 rounded-2xl font-medium transition-all duration-200 bg-rose-600/90 hover:bg-rose-500 active:scale-95 disabled:opacity-30 disabled:cursor-not-allowed shadow-lg shadow-rose-500/20">
                <div class="text-2xl mb-1">←</div>
                <div class="text-sm">Départ</div>
            </button>
        </div>
    </div>

    <script>
    (function () {
        'use strict';

        const video       = document.getElementById('camera-preview');
        const canvas      = document.getElementById('canvas-capture');
        const statusEl    = document.getElementById('camera-status');
        const statusText  = document.getElementById('camera-status-text');
        const overlay     = document.getElementById('overlay-decor');
        const overlayLoad = document.getElementById('overlay-loading');
        const overlaySucc = document.getElementById('overlay-success');
        const overlayErr  = document.getElementById('overlay-error');
        const successTitle  = document.getElementById('success-title');
        const successDetail = document.getElementById('success-detail');
        const errorTitle    = document.getElementById('error-title');
        const errorDetail   = document.getElementById('error-detail');
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

                // Sprint 4 US-036 : fallback caméra → lien vers le mode manuel
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
        // 3. Cacher tous les overlays
        // ----------------------------------------------------------
        function hideAllOverlays() {
            overlayLoad.classList.add('hidden');
            overlaySucc.classList.add('hidden');
            overlayErr.classList.add('hidden');
        }

        // ----------------------------------------------------------
        // 4. Envoyer au backend
        // ----------------------------------------------------------
        async function pointer(type) {
            if (!cameraReady || inFlight) return;
            inFlight = true;
            buttons.forEach(b => b.disabled = true);

            hideAllOverlays();
            overlayLoad.classList.remove('hidden');

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

                hideAllOverlays();

                if (res.ok && data.success) {
                    const nom  = data.employe?.nom || data.employe?.matricule || 'Employé';
                    const conf = Math.round((data.confidence ?? 0) * 100);
                    const labels = {
                        arrivee: 'Arrivée enregistrée',
                        debut_pause: 'Début de pause',
                        fin_pause: 'Reprise du travail',
                        depart: 'Départ enregistré',
                    };
                    successTitle.textContent = `Bonjour ${nom}`;
                    successDetail.textContent = `${labels[type] || type} · confiance ${conf}%`;
                    overlaySucc.classList.remove('hidden');
                    setTimeout(() => hideAllOverlays(), 3500);
                } else {
                    errorTitle.textContent = (res.status === 429 ? 'Trop de tentatives' : 'Pointage refusé');
                    errorDetail.textContent = data.message || 'Erreur inconnue.';
                    overlayErr.classList.remove('hidden');
                    setTimeout(() => hideAllOverlays(), 4000);
                }
            } catch (err) {
                hideAllOverlays();
                errorTitle.textContent = 'Erreur réseau';
                errorDetail.textContent = err.message;
                overlayErr.classList.remove('hidden');
                setTimeout(() => hideAllOverlays(), 4000);
            } finally {
                setTimeout(() => {
                    inFlight = false;
                    buttons.forEach(b => b.disabled = false);
                }, 1500);
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
