@props(['alertes' => collect()])

{{-- Sprint 6 carte 12 (US-102) — Widget alertes priorisées en en-tête.
     Auto-refresh toutes les 60 secondes via meta refresh
     (uniquement si la page contient le widget et qu'il y a des alertes high). --}}

@if ($alertes->isNotEmpty())
    <div style="margin-bottom: 24px;">
        @php
            $hasHigh = $alertes->contains(fn ($a) => $a['level'] === 'high');
        @endphp

        @if ($hasHigh)
            <meta http-equiv="refresh" content="60">
        @endif

        <div style="display: flex; flex-direction: column; gap: 10px;">
            @foreach ($alertes as $a)
                @php
                    $colors = match ($a['level']) {
                        'high'   => ['bg' => 'rgba(239,68,68,0.08)', 'border' => 'rgba(239,68,68,0.35)', 'text' => '#fca5a5', 'title' => '#fecaca'],
                        'medium' => ['bg' => 'rgba(245,158,11,0.08)', 'border' => 'rgba(245,158,11,0.3)', 'text' => '#fcd34d', 'title' => '#fde68a'],
                        default  => ['bg' => 'rgba(99,102,241,0.06)', 'border' => 'rgba(99,102,241,0.25)', 'text' => '#a5b4fc', 'title' => '#c7d2fe'],
                    };
                @endphp
                <div style="padding: 14px 18px; background: {{ $colors['bg'] }};
                            border: 1px solid {{ $colors['border'] }}; border-radius: 10px;
                            display: flex; align-items: center; justify-content: space-between;
                            gap: 14px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 12px; flex: 1; min-width: 240px;">
                        <span style="font-size: 24px;">{{ $a['icon'] }}</span>
                        <div>
                            <div style="color: {{ $colors['title'] }}; font-weight: 700; font-size: 14px;">
                                {{ $a['title'] }}
                            </div>
                            <div style="color: {{ $colors['text'] }}; font-size: 12px; margin-top: 2px;">
                                {{ $a['message'] }}
                            </div>
                        </div>
                    </div>
                    @if (!empty($a['url']))
                        @php
                            try {
                                $href = route($a['url']);
                            } catch (\Throwable $e) {
                                $href = '#';
                            }
                        @endphp
                        @if ($href !== '#')
                            <a href="{{ $href }}"
                               style="padding: 8px 14px; background: rgba(255,255,255,0.06);
                                      border: 1px solid {{ $colors['border'] }}; border-radius: 8px;
                                      color: {{ $colors['title'] }}; font-size: 12px; font-weight: 600;
                                      text-decoration: none; white-space: nowrap;">
                                Traiter →
                            </a>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
