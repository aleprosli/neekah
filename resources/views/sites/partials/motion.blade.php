@props(['template'])

@php $motion = $template->motion(); @endphp

@if ($motion !== 'none')
    <div class="pointer-events-none fixed inset-0 z-0 overflow-hidden" aria-hidden="true">
        @php
            $colors = $template->petalColors();
            $count = $motion === 'sparkle' ? 20 : 16;
        @endphp
        @for ($i = 0; $i < $count; $i++)
            @php
                $size = $motion === 'sparkle' ? random_int(3, 6) : random_int(7, 16);
                $color = $colors[$i % count($colors)];
                $radius = match ($motion) {
                    'sparkle' => '50%',
                    'leaves' => $size.'px 2px',
                    default => $size.'px '.round($size * 0.35).'px',
                };
            @endphp
            <span class="nk-petal"
                  style="left: {{ random_int(0, 98) }}%;
                         width: {{ $size }}px;
                         height: {{ $motion === 'sparkle' ? $size : round($size * 0.7) }}px;
                         background: {{ $color }};
                         border-radius: {{ $radius }};
                         animation-duration: {{ random_int(14, 30) }}s;
                         animation-delay: -{{ random_int(0, 24) }}s;
                         --nk-drift-x: {{ random_int(-14, 14) }}vw;
                         --nk-spin: {{ random_int(240, 720) }}deg;
                         --nk-petal-opacity: {{ $motion === 'sparkle' ? '0.9' : '0.7' }};"></span>
        @endfor
    </div>
@endif
