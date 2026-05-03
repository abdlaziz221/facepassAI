{{--
    Logo FacePass AI : casque IA style Iron Man avec :
    - Anneau extérieur rotatif (sens horaire, lent) → effet "tracker / radar"
    - Anneau intérieur rotatif (sens anti-horaire, plus lent) → 2 cercles qui se croisent
    - Yeux qui pulsent (animation .logo-eyes-pulse définie dans le layout)
    - Ligne de scan animée (.scan-line)
    - Glow violet/cyan global (.logo-glow appliqué via .glow autour)
--}}
<svg viewBox="-12 -12 104 104" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}>
    <defs>
        <linearGradient id="mask-stroke" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%"   stop-color="#a5b4fc" />
            <stop offset="60%"  stop-color="#818cf8" />
            <stop offset="100%" stop-color="#67e8f9" />
        </linearGradient>

        <linearGradient id="mask-fill" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%"   stop-color="rgba(129, 140, 248, 0.20)" />
            <stop offset="100%" stop-color="rgba(34, 211, 238, 0.04)" />
        </linearGradient>

        <linearGradient id="eye-grad" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0%"   stop-color="#22d3ee" />
            <stop offset="50%"  stop-color="#818cf8" />
            <stop offset="100%" stop-color="#a5b4fc" />
        </linearGradient>
    </defs>

    {{-- ============================================================
         ANNEAU EXTÉRIEUR (rotation horaire, 16s)
         ============================================================ --}}
    <g>
        <animateTransform attributeName="transform"
                          type="rotate"
                          from="0 40 40"
                          to="360 40 40"
                          dur="16s"
                          repeatCount="indefinite" />

        {{-- Cercle dashé principal --}}
        <circle cx="40" cy="40" r="46"
                fill="none" stroke="url(#mask-stroke)"
                stroke-width="1" stroke-dasharray="3 5"
                opacity="0.45" />

        {{-- Indicateur radar (triangle qui pointe vers le haut) --}}
        <path d="M 40 -10 L 35 -3 L 45 -3 Z"
              fill="url(#mask-stroke)" />

        {{-- Marqueurs cardinaux --}}
        <circle cx="86" cy="40" r="1.4" fill="url(#mask-stroke)" opacity="0.7" />
        <circle cx="40" cy="86" r="1.4" fill="url(#mask-stroke)" opacity="0.7" />
        <circle cx="-6" cy="40" r="1.4" fill="url(#mask-stroke)" opacity="0.7" />

        {{-- Petit arc lumineux (radar sweep) --}}
        <path d="M 40 40 L 40 -6 A 46 46 0 0 1 72 18 Z"
              fill="url(#eye-grad)" opacity="0.12" />
    </g>

    {{-- ============================================================
         ANNEAU INTÉRIEUR (rotation anti-horaire, 22s)
         ============================================================ --}}
    <g>
        <animateTransform attributeName="transform"
                          type="rotate"
                          from="360 40 40"
                          to="0 40 40"
                          dur="22s"
                          repeatCount="indefinite" />

        <circle cx="40" cy="40" r="42"
                fill="none" stroke="url(#mask-stroke)"
                stroke-width="0.6" stroke-dasharray="1 4"
                opacity="0.3" />

        {{-- 3 marqueurs en triangle --}}
        <circle cx="40" cy="-2" r="1" fill="#67e8f9" opacity="0.8" />
        <circle cx="76.4" cy="61" r="1" fill="#818cf8" opacity="0.8" />
        <circle cx="3.6" cy="61" r="1" fill="#a5b4fc" opacity="0.8" />
    </g>

    {{-- ============================================================
         CASQUE / MASQUE (statique)
         ============================================================ --}}
    <path d="M40 5
             L60 11 L66 24
             L66 42 L60 55 L52 65
             L40 73
             L28 65 L20 55 L14 42
             L14 24 L20 11 Z"
          fill="url(#mask-fill)"
          stroke="url(#mask-stroke)"
          stroke-width="2"
          stroke-linejoin="round" />

    {{-- Front horizontal --}}
    <path d="M22 22 L58 22"
          stroke="url(#mask-stroke)" stroke-width="0.8" opacity="0.5" />

    {{-- Capteur central front --}}
    <circle cx="40" cy="14" r="1.4" fill="#a5b4fc" opacity="0.95" />

    {{-- Ligne verticale subtile --}}
    <path d="M40 46 L40 68"
          stroke="url(#mask-stroke)" stroke-width="0.7" opacity="0.3" />

    {{-- Joues --}}
    <path d="M16 42 L24 50"
          stroke="url(#mask-stroke)" stroke-width="0.8" opacity="0.45"
          stroke-linecap="round" />
    <path d="M64 42 L56 50"
          stroke="url(#mask-stroke)" stroke-width="0.8" opacity="0.45"
          stroke-linecap="round" />

    {{-- Bouche / grille --}}
    <g stroke="url(#mask-stroke)" stroke-width="1" opacity="0.55" stroke-linecap="round">
        <line x1="34" y1="56" x2="38" y2="56" />
        <line x1="40" y1="56" x2="44" y2="56" />
        <line x1="46" y1="56" x2="46.5" y2="56" />
    </g>

    {{-- ============================================================
         YEUX (avec animation pulse)
         ============================================================ --}}
    <g class="logo-eyes-pulse">
        <path d="M16 30 L34 28 L34 38 L19 40 Z" fill="url(#eye-grad)" />
        <path d="M64 30 L46 28 L46 38 L61 40 Z" fill="url(#eye-grad)" />
    </g>

    {{-- ============================================================
         LIGNE DE SCAN (déjà animée verticalement via .scan-line)
         ============================================================ --}}
    <line class="scan-line"
          x1="20" y1="34" x2="60" y2="34"
          stroke="url(#mask-stroke)" stroke-width="1"
          stroke-dasharray="2 3" opacity="0.65" />
</svg>
