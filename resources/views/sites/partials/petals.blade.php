@props(['colors' => ['#f2c6d4', '#e9b8c6', '#f6dcc2'], 'count' => 14, 'opacity' => '0.7'])

{{-- Petals drifting down the whole card. Purely decorative. --}}
<div class="pointer-events-none fixed inset-0 z-0 overflow-hidden" aria-hidden="true">
    @for ($i = 0; $i < $count; $i++)
        @php
            $size = random_int(7, 16);
            $color = $colors[$i % count($colors)];
        @endphp
        <span class="nk-petal"
              style="left: {{ random_int(0, 98) }}%;
                     width: {{ $size }}px;
                     height: {{ round($size * 0.7) }}px;
                     background: {{ $color }};
                     border-radius: {{ $size }}px {{ round($size * 0.35) }}px;
                     animation-duration: {{ random_int(14, 30) }}s;
                     animation-delay: -{{ random_int(0, 24) }}s;
                     --nk-drift-x: {{ random_int(-14, 14) }}vw;
                     --nk-spin: {{ random_int(240, 720) }}deg;
                     --nk-petal-opacity: {{ $opacity }};"></span>
    @endfor
</div>
