{{-- A flowered arch over the names, the way a printed kad frames its cover.
     The rules and the blooms are struck from the same circle, so the shape
     reads as one garland instead of flowers scattered near a line. --}}
@php
    // Shoulders exactly on the sheet's edges, crown high enough that the
    // bismillah and the crest sit inside the arch rather than across it.
    $cx = 200; $cy = 235; $r = 200;
    // Shoulder to shoulder over the top, leaving the ends open where the arch
    // would otherwise run into the card's own frame.
    $stops = collect(range(0, 24))->map(fn (int $i): float => 186 + ($i * (168 / 24)));
@endphp
<svg viewBox="0 0 400 250" fill="none" aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 w-full" preserveAspectRatio="xMidYMin meet">
    <path d="M{{ $cx - $r }} {{ $cy }} A {{ $r }} {{ $r }} 0 0 1 {{ $cx + $r }} {{ $cy }}" stroke="var(--nk-accent)" stroke-opacity=".45" stroke-width="1.6" stroke-linecap="round"/>
    <path d="M{{ $cx - $r + 18 }} {{ $cy }} A {{ $r - 18 }} {{ $r - 18 }} 0 0 1 {{ $cx + $r - 18 }} {{ $cy }}" stroke="var(--nk-accent)" stroke-opacity=".22" stroke-width="1.1" stroke-linecap="round"/>

    @foreach ($stops as $i => $deg)
        @php
            $rad = deg2rad($deg);
            $x = round($cx + $r * cos($rad), 1);
            $y = round($cy + $r * sin($rad), 1);
            $tilt = round($deg + 90, 1);
            $bloom = $i % 3 === 0;
            $scale = $bloom ? (($i % 6 === 0) ? 1.35 : 1.0) : 0.85;
        @endphp
        <g transform="translate({{ $x }} {{ $y }}) rotate({{ $tilt }}) scale({{ $scale }})">
            @if ($bloom)
                @for ($p = 0; $p < 6; $p++)
                    <ellipse cx="0" cy="-8" rx="4" ry="8" fill="var(--nk-name)" fill-opacity=".34" transform="rotate({{ $p * 60 }})"/>
                @endfor
                <circle r="3.2" fill="var(--nk-accent)" fill-opacity=".9"/>
            @else
                <ellipse cx="0" cy="-7" rx="5" ry="11" fill="var(--nk-accent)" fill-opacity=".32" transform="rotate(-26)"/>
                <ellipse cx="0" cy="-7" rx="5" ry="11" fill="var(--nk-accent)" fill-opacity=".24" transform="rotate(26)"/>
            @endif
        </g>
    @endforeach
</svg>
