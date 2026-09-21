{{-- A swag hung across the head of the sheet: two curves of leaves meeting at
     a knot, as on the top of a printed invitation. --}}
<svg viewBox="0 0 400 130" fill="none" aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 w-full opacity-80" preserveAspectRatio="xMidYMin meet">
    <path d="M8 18C70 18 128 44 200 78c72-34 130-60 192-60" stroke="var(--nk-accent)" stroke-opacity=".4" stroke-width="1.4" stroke-linecap="round"/>
    <path d="M8 34C68 34 126 58 200 90c74-32 132-56 192-56" stroke="var(--nk-accent)" stroke-opacity=".2" stroke-width="1" stroke-linecap="round"/>

    @foreach ([[44,22,-28],[80,29,-22],[118,40,-16],[156,54,-10],[244,54,10],[282,40,16],[320,29,22],[356,22,28]] as [$x,$y,$rot])
        <g transform="translate({{ $x }} {{ $y }}) rotate({{ $rot }})">
            <ellipse cx="0" cy="8" rx="5" ry="11" fill="var(--nk-accent)" fill-opacity=".28" transform="rotate(-20)"/>
            <ellipse cx="0" cy="8" rx="5" ry="11" fill="var(--nk-accent)" fill-opacity=".2" transform="rotate(20)"/>
        </g>
    @endforeach

    {{-- The knot where the two halves meet. --}}
    <g transform="translate(200 82)">
        @for ($p = 0; $p < 8; $p++)
            <ellipse cx="0" cy="-9" rx="4" ry="9" fill="var(--nk-name)" fill-opacity=".28" transform="rotate({{ $p * 45 }})"/>
        @endfor
        <circle r="3.4" fill="var(--nk-accent)" fill-opacity=".9"/>
    </g>
</svg>
