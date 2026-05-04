<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'FacePass AI') }} — {{ $title ?? 'Espace de travail' }}</title>

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

            /* === Backgrounds === */
            .bg-aurora {
                background:
                    radial-gradient(circle at 20% 0%, rgba(99, 102, 241, 0.18), transparent 50%),
                    radial-gradient(circle at 80% 100%, rgba(34, 211, 238, 0.10), transparent 50%),
                    #050609;
            }
            .bg-grid {
                background-image:
                    linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
                background-size: 48px 48px;
            }

            /* === Logo glow === */
            .logo-glow {
                filter: drop-shadow(0 0 14px rgba(99, 102, 241, 0.55))
                        drop-shadow(0 0 28px rgba(99, 102, 241, 0.25));
            }
            .scan-line { animation: scan 2.4s ease-in-out infinite; }
            @keyframes scan {
                0%, 100% { transform: translateY(-12px); opacity: 0.3; }
                50%      { transform: translateY(12px);  opacity: 0.95; }
            }
            .logo-eyes-pulse {
                animation: eyesPulse 1.8s ease-in-out infinite;
                transform-origin: center;
                transform-box: fill-box;
            }
            @keyframes eyesPulse {
                0%, 100% { opacity: 0.85; filter: brightness(1); }
                50%      { opacity: 1; filter: brightness(1.6) drop-shadow(0 0 6px #67e8f9); }
            }

            /* === Cartes glassmorphism === */
            .card {
                background: rgba(255, 255, 255, 0.025);
                border: 1px solid rgba(255, 255, 255, 0.06);
                border-radius: 16px;
                backdrop-filter: blur(12px);
                transition: border-color .2s, transform .15s;
            }
            .card:hover {
                border-color: rgba(99, 102, 241, 0.25);
            }
            .card-stat {
                padding: 24px;
            }
            .card-stat .label {
                font-size: 12px;
                color: #9ca3af;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                font-weight: 600;
            }
            .card-stat .value {
                font-size: 32px;
                font-weight: 800;
                color: white;
                line-height: 1.1;
                margin-top: 8px;
                background: linear-gradient(135deg, #fff, #c7d2fe);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
            }
            .card-stat .delta {
                font-size: 12px;
                margin-top: 8px;
                color: #6ee7b7;
            }

            /* === Bouton primaire === */
            .btn-primary {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 11px 20px;
                border-radius: 10px;
                background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
                color: white;
                font-weight: 600;
                font-size: 14px;
                border: none;
                cursor: pointer;
                box-shadow: 0 8px 24px rgba(99, 102, 241, 0.3);
                transition: transform .15s, box-shadow .2s;
                text-decoration: none;
            }
            .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 12px 32px rgba(99, 102, 241, 0.45); }
            .btn-secondary {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 11px 20px;
                border-radius: 10px;
                background: rgba(255, 255, 255, 0.04);
                border: 1px solid rgba(255, 255, 255, 0.08);
                color: #e5e7eb;
                font-weight: 500;
                font-size: 14px;
                text-decoration: none;
                transition: all .15s;
            }
            .btn-secondary:hover {
                background: rgba(255, 255, 255, 0.07);
                border-color: rgba(99, 102, 241, 0.3);
                color: white;
            }

            /* === Pill / badge === */
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
            .pill-success {
                background: rgba(34, 197, 94, 0.12);
                border-color: rgba(34, 197, 94, 0.25);
                color: #86efac;
            }
            .pill-warning {
                background: rgba(234, 179, 8, 0.12);
                border-color: rgba(234, 179, 8, 0.25);
                color: #fde68a;
            }
            .pill-danger {
                background: rgba(239, 68, 68, 0.12);
                border-color: rgba(239, 68, 68, 0.25);
                color: #fca5a5;
            }

            /* === Liens & textes === */
            .link-muted { color: #9ca3af; text-decoration: none; transition: color .2s; }
            .link-muted:hover { color: #a5b4fc; }
            .text-muted { color: #6b7280; }
            .text-soft { color: #9ca3af; }

            /* === Section heading === */
            .section-title {
                font-size: 14px;
                font-weight: 600;
                color: #9ca3af;
                text-transform: uppercase;
                letter-spacing: 0.1em;
                margin: 32px 0 16px;
            }

            /* === Quick action === */
            .quick-action {
                display: flex;
                align-items: center;
                gap: 14px;
                padding: 16px;
                background: rgba(255,255,255,0.025);
                border: 1px solid rgba(255,255,255,0.06);
                border-radius: 12px;
                color: #e5e7eb;
                text-decoration: none;
                transition: all .15s;
            }
            .quick-action:hover {
                border-color: rgba(99, 102, 241, 0.4);
                background: rgba(99, 102, 241, 0.06);
                transform: translateY(-1px);
            }
            .quick-action .icon {
                width: 40px; height: 40px;
                border-radius: 10px;
                background: rgba(99, 102, 241, 0.15);
                display: flex; align-items: center; justify-content: center;
                color: #a5b4fc;
                flex-shrink: 0;
            }
            .quick-action .title { font-weight: 600; color: white; font-size: 14px; }
            .quick-action .subtitle { font-size: 12px; color: #6b7280; margin-top: 2px; }
        </style>
    </head>
    <body class="bg-aurora">

        @include('layouts.navigation')

        <main style="max-width: 1280px; margin: 0 auto; padding: 32px;" class="bg-grid">
            @isset($header)
                <header style="margin-bottom: 32px;">
                    {{ $header }}
                </header>
            @endisset

            {{ $slot }}
        </main>
    </body>
</html>
