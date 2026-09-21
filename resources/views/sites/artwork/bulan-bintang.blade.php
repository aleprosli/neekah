{{-- A crescent and star medallion behind the crest, for the Islamic designs.
     It sits low in opacity: it frames the names, it does not compete. --}}
<svg viewBox="0 0 400 320" fill="none" aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 w-full opacity-60" preserveAspectRatio="xMidYMin meet">
    <circle cx="200" cy="150" r="118" stroke="var(--nk-accent)" stroke-opacity=".3" stroke-width="1.3"/>
    <circle cx="200" cy="150" r="104" stroke="var(--nk-accent)" stroke-opacity=".16" stroke-width=".9"/>

    {{-- The crescent: one disc with a second cut out of it. --}}
    <path d="M200 46a104 104 0 1 0 54 193 88 88 0 1 1-54-193Z" fill="var(--nk-accent)" fill-opacity=".14"/>

    @foreach ([[200,32,11],[306,86,7],[94,86,7],[330,196,5],[70,196,5]] as [$x,$y,$s])
        <g transform="translate({{ $x }} {{ $y }})">
            <path d="M0 {{ -$s }} L{{ round($s * 0.29, 2) }} {{ round(-$s * 0.31, 2) }} L{{ $s }} {{ round(-$s * 0.31, 2) }} L{{ round($s * 0.4, 2) }} {{ round($s * 0.12, 2) }} L{{ round($s * 0.62, 2) }} {{ $s }} L0 {{ round($s * 0.4, 2) }} L{{ round(-$s * 0.62, 2) }} {{ $s }} L{{ round(-$s * 0.4, 2) }} {{ round($s * 0.12, 2) }} L{{ -$s }} {{ round(-$s * 0.31, 2) }} L{{ round(-$s * 0.29, 2) }} {{ round(-$s * 0.31, 2) }} Z"
                  fill="var(--nk-accent)" fill-opacity=".5"/>
        </g>
    @endforeach
</svg>
