<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'FacePass AI') }} — Connexion</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            html, body {
                background: #050609;
                color: #e5e7eb;
                min-height: 100vh;
                font-family: 'Figtree', system-ui, -apple-system, sans-serif;
                margin: 0;
            }

            .bg-aurora {
                background:
                    radial-gradient(circle at 20% 0%, rgba(99, 102, 241, 0.18), transparent 50%),
                    radial-gradient(circle at 80% 100%, rgba(34, 211, 238, 0.10), transparent 50%),
                    radial-gradient(circle at 50% 50%, rgba(168, 85, 247, 0.06), transparent 60%),
                    #050609;
            }

            .bg-grid {
                background-image:
                    linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
                background-size: 48px 48px;
            }

            /* Glow général sur le logo */
            .logo-glow {
                filter: drop-shadow(0 0 14px rgba(99, 102, 241, 0.55))
                        drop-shadow(0 0 28px rgba(99, 102, 241, 0.25));
            }
            .logo-glow-strong {
                filter: drop-shadow(0 0 18px rgba(99, 102, 241, 0.7))
                        drop-shadow(0 0 36px rgba(34, 211, 238, 0.35));
            }

            /* Ligne de scan verticale (existait déjà) */
            .scan-line {
                animation: scan 2.4s ease-in-out infinite;
            }
            @keyframes scan {
                0%, 100% { transform: translateY(-12px); opacity: 0.3; }
                50%      { transform: translateY(12px);  opacity: 0.95; }
            }

            /* Yeux qui pulsent (analyse) */
            .logo-eyes-pulse {
                animation: eyesPulse 1.8s ease-in-out infinite;
                transform-origin: center;
                transform-box: fill-box;
            }
            @keyframes eyesPulse {
                0%, 100% {
                    opacity: 0.85;
                    filter: brightness(1);
                }
                50% {
                    opacity: 1;
                    filter: brightness(1.6) drop-shadow(0 0 6px #67e8f9);
                }
            }

            /* Inputs */
            .input-dark {
                background: rgba(255, 255, 255, 0.03);
                border: 1px solid rgba(255, 255, 255, 0.08);
                color: #f3f4f6;
                width: 100%;
                padding: 14px 16px;
                border-radius: 10px;
                font-size: 14px;
                outline: none;
                transition: all .2s;
            }
            .input-dark::placeholder { color: #6b7280; }
            .input-dark:focus {
                border-color: rgba(99, 102, 241, 0.5);
                box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
                background: rgba(255, 255, 255, 0.04);
            }

            /* Bouton primaire */
            .btn-primary {
                width: 100%;
                padding: 14px;
                border-radius: 10px;
                background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
                color: white;
                font-weight: 600;
                font-size: 14px;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                border: none;
                cursor: pointer;
                box-shadow: 0 8px 24px rgba(99, 102, 241, 0.3);
                transition: transform .15s ease, box-shadow .2s ease;
            }
            .btn-primary:hover {
                transform: translateY(-1px);
                box-shadow: 0 12px 32px rgba(99, 102, 241, 0.45);
            }
            .btn-primary:active { transform: translateY(0); }

            .link-muted { color: #9ca3af; text-decoration: none; transition: color .2s; }
            .link-muted:hover { color: #a5b4fc; }

            .feature-dot {
                width: 6px; height: 6px;
                border-radius: 999px;
                background: linear-gradient(135deg, #6366f1, #22d3ee);
                box-shadow: 0 0 8px rgba(99, 102, 241, 0.8);
                flex-shrink: 0;
                margin-top: 8px;
            }

            .glass {
                background: rgba(255, 255, 255, 0.02);
                border: 1px solid rgba(255, 255, 255, 0.06);
                backdrop-filter: blur(12px);
            }

            .pill {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 4px 10px;
                border-radius: 999px;
                background: rgba(99, 102, 241, 0.12);
                border: 1px solid rgba(99, 102, 241, 0.25);
                color: #c7d2fe;
                font-size: 11px;
                font-weight: 600;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .error-text { color: #fca5a5; font-size: 13px; margin-top: 6px; }

            /* === CURSEUR FACE-TRACKER (suit la souris, style face detection) === */
            .cursor-tracker {
                position: fixed;
                top: 0; left: 0;
                width: 56px; height: 56px;
                pointer-events: none;
                z-index: 9999;
                transform: translate(-100px, -100px);
                opacity: 0;
                transition: opacity .25s ease;
                will-change: transform;
                filter: drop-shadow(0 0 10px rgba(99, 102, 241, 0.5));
            }
            .cursor-tracker.active { opacity: 1; }
            .cursor-tracker svg { width: 100%; height: 100%; overflow: visible; }
            .cursor-tracker .frame {
                animation: tracker-rotate 6s linear infinite;
                transform-origin: 28px 28px;
                transform-box: fill-box;
            }
            .cursor-tracker .pulse-ring {
                animation: tracker-pulse 1.6s ease-in-out infinite;
                transform-origin: 28px 28px;
                transform-box: fill-box;
            }
            @keyframes tracker-rotate {
                from { transform: rotate(0deg); }
                to   { transform: rotate(360deg); }
            }
            @keyframes tracker-pulse {
                0%, 100% { opacity: 0.25; transform: scale(1); }
                50%      { opacity: 0.6; transform: scale(1.18); }
            }
            /* Désactivé sur écrans tactiles */
            @media (pointer: coarse) {
                .cursor-tracker { display: none !important; }
            }
        </style>
    </head>
    <body class="bg-aurora">
        {{ $slot }}

        {{-- ====================================================
             CURSEUR FACE-TRACKER : 4 coins de scan rotatifs +
             cercle pulsant + point central. Suit la souris en
             smooth (lerp 18%). Désactivé sur tactile.
             ==================================================== --}}
        <div class="cursor-tracker" id="cursor-tracker" aria-hidden="true">
            <svg viewBox="0 0 56 56" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="cur-grad" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%"  stop-color="#a5b4fc"/>
                        <stop offset="100%" stop-color="#67e8f9"/>
                    </linearGradient>
                </defs>
                <g class="frame">
                    {{-- 4 coins de cadre de scan --}}
                    <g fill="none" stroke="url(#cur-grad)" stroke-width="1.8" stroke-linecap="round">
                        <path d="M4 14 V4 H14" />
                        <path d="M42 4 H52 V14" />
                        <path d="M4 42 V52 H14" />
                        <path d="M42 52 H52 V42" />
                    </g>
                </g>
                {{-- Cercle pulsant (analyse) --}}
                <circle class="pulse-ring" cx="28" cy="28" r="14"
                        fill="none" stroke="url(#cur-grad)"
                        stroke-width="1" opacity="0.4"/>
                {{-- Point central --}}
                <circle cx="28" cy="28" r="2.2" fill="#67e8f9" opacity="0.95"/>
            </svg>
        </div>

        <script>
        (function () {
            // Pas de cursor tracker sur tactile (mobile/tablette)
            if (window.matchMedia('(pointer: coarse)').matches) return;

            const cursor = document.getElementById('cursor-tracker');
            if (!cursor) return;

            let mouseX = -100, mouseY = -100;
            let cursorX = -100, cursorY = -100;

            document.addEventListener('mousemove', (e) => {
                mouseX = e.clientX;
                mouseY = e.clientY;
                cursor.classList.add('active');
            }, { passive: true });

            document.addEventListener('mouseleave', () => {
                cursor.classList.remove('active');
            });

            function animate() {
                // Lerp pour un suivi smooth
                cursorX += (mouseX - cursorX) * 0.18;
                cursorY += (mouseY - cursorY) * 0.18;
                cursor.style.transform = `translate(${cursorX - 28}px, ${cursorY - 28}px)`;
                requestAnimationFrame(animate);
            }
            animate();
        })();
        </script>
    </body>
</html>
