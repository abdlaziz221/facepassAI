<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'FacePass.AI')</title>
    <style>
        /* Sprint 5 carte 6 (US-070) — Template de base pour tous les exports PDF.
           dompdf supporte HTML 4 / CSS 2.1, pas de flex/grid.
           On utilise DejaVu Sans pour le support UTF-8 (accents).            */

        @page {
            margin: 110px 40px 70px 40px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1f2937;
            margin: 0;
        }

        /* En-tête fixe en haut de chaque page */
        header {
            position: fixed;
            top: -90px;
            left: 0;
            right: 0;
            height: 70px;
            padding-bottom: 8px;
            border-bottom: 2px solid #6366f1;
        }
        header .brand {
            font-size: 18px;
            font-weight: bold;
            color: #6366f1;
        }
        header .brand .ai {
            color: #8b5cf6;
        }
        header .meta {
            font-size: 9px;
            color: #6b7280;
        }

        /* Pied de page fixe en bas */
        footer {
            position: fixed;
            bottom: -50px;
            left: 0;
            right: 0;
            height: 30px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            font-size: 9px;
            color: #9ca3af;
        }

        h1 {
            font-size: 20px;
            color: #111827;
            margin: 0 0 4px;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        h2 {
            font-size: 14px;
            color: #374151;
            margin: 20px 0 10px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e5e7eb;
        }

        .subtitle {
            color: #6b7280;
            font-size: 11px;
            margin: 0 0 18px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        th, td {
            padding: 7px 9px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            font-size: 11px;
        }
        th {
            background: #f3f4f6;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
            font-weight: 700;
        }
        tr:nth-child(even) td {
            background: #fafbfc;
        }

        .pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 700;
        }
        .pill-success { background: #d1fae5; color: #065f46; }
        .pill-warning { background: #fef3c7; color: #92400e; }
        .pill-danger  { background: #fee2e2; color: #991b1b; }
        .pill-info    { background: #dbeafe; color: #1e40af; }

        .text-muted { color: #9ca3af; }
        .text-right { text-align: right; }
        .mono       { font-variant-numeric: tabular-nums; }
    </style>
</head>
<body>
    <header>
        <table style="width:100%; border:none;">
            <tr>
                <td style="border:none; padding: 0;">
                    <span class="brand">FacePass<span class="ai">.AI</span></span>
                    <div class="meta" style="margin-top:2px;">@yield('subtitle_header', 'Système de pointage biométrique')</div>
                </td>
                <td style="border:none; padding: 0; text-align:right;" class="meta">
                    @yield('header_right', 'Généré le ' . now()->format('d/m/Y à H:i'))
                </td>
            </tr>
        </table>
    </header>

    <footer>
        <table style="width:100%; border:none;">
            <tr>
                <td style="border:none; padding:0;">@yield('footer_left', 'FacePass.AI — Document confidentiel')</td>
                <td style="border:none; padding:0; text-align:right;">
                    Page <span class="pagenum"></span>
                </td>
            </tr>
        </table>
        <script type="text/php">
            if (isset($pdf)) {
                $pdf->page_text(530, 815, "{PAGE_NUM} / {PAGE_COUNT}", null, 9, [0.6, 0.6, 0.6]);
            }
        </script>
    </footer>

    <main>
        @yield('content')
    </main>
</body>
</html>
