{{-- Deep corner sprays, larger than the ornament set: these carry the top of
     the sheet on their own when the layout has no cover photo. --}}
@php
    $corners = [
        'left-0 top-0' => '',
        'right-0 top-0' => '-scale-x-100',
    ];
@endphp
@foreach ($corners as $place => $flip)
    <svg viewBox="0 0 240 240" fill="none" aria-hidden="true" class="pointer-events-none absolute {{ $place }} {{ $flip }} w-44 opacity-75 sm:w-56">
        <path d="M0 10c52 6 98 30 132 68 26 29 42 64 48 104" stroke="var(--nk-accent)" stroke-opacity=".42" stroke-width="1.5" stroke-linecap="round"/>
        <path d="M0 52c40 2 74 18 100 46M52 0c2 38 16 70 40 94" stroke="var(--nk-accent)" stroke-opacity=".28" stroke-width="1.1" stroke-linecap="round"/>

        @foreach ([[30,26,16],[86,80,20],[132,140,13],[64,124,10],[150,74,11]] as [$cx,$cy,$rad])
            <g transform="translate({{ $cx }} {{ $cy }})">
                @for ($p = 0; $p < 6; $p++)
                    <ellipse cx="0" cy="{{ -$rad * 0.6 }}" rx="{{ round($rad * 0.33, 2) }}" ry="{{ round($rad * 0.6, 2) }}" fill="var(--nk-name)" fill-opacity=".3" transform="rotate({{ $p * 60 }})"/>
                @endfor
                <circle r="{{ round($rad * 0.28, 2) }}" fill="var(--nk-accent)" fill-opacity=".85"/>
            </g>
        @endforeach

        @foreach ([[54,52,-20],[104,36,14],[38,96,-40],[118,104,24],[164,126,34]] as [$x,$y,$r])
            <ellipse cx="{{ $x }}" cy="{{ $y }}" rx="14" ry="6" fill="var(--nk-accent)" fill-opacity=".24" transform="rotate({{ $r }} {{ $x }} {{ $y }})"/>
        @endforeach
    </svg>
@endforeach
