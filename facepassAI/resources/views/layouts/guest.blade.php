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
        </style>
    </head>
    <body class="bg-aurora">
        {{ $slot }}
    </body>
</html>
