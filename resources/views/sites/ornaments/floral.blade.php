@props(['position' => 'top-left'])
@php
    $flip = ['top-right' => 'right-0 top-0 -scale-x-100', 'bottom-left' => 'left-0 bottom-0 -scale-y-100', 'bottom-right' => 'right-0 bottom-0 -scale-x-100 -scale-y-100'][$position] ?? 'left-0 top-0';
    $delay = ['top-left' => '0s', 'top-right' => '-1.8s', 'bottom-left' => '-3.4s', 'bottom-right' => '-5s'][$position] ?? '0s';
@endphp
<svg viewBox="0 0 220 220" fill="none" aria-hidden="true" class="pointer-events-none absolute {{ $flip }} nk-sway w-36 opacity-70 sm:w-48" style="animation-delay: {{ $delay }}">
    <path d="M4 6c38 4 72 22 96 50 18 21 28 46 32 74" stroke="var(--nk-accent)" stroke-opacity=".5" stroke-width="1.6" stroke-linecap="round"/>
    <path d="M6 44c30 0 56 12 74 34M44 6c2 28 12 52 30 70" stroke="var(--nk-accent)" stroke-opacity=".35" stroke-width="1.2" stroke-linecap="round"/>
    @foreach ([[34,30,-18],[62,58,6],[92,92,22],[24,66,-34],[70,24,30]] as [$x,$y,$r])
        <ellipse cx="{{ $x }}" cy="{{ $y }}" rx="13" ry="5.5" fill="var(--nk-accent)" fill-opacity=".26" transform="rotate({{ $r }} {{ $x }} {{ $y }})"/>
    @endforeach
    @foreach ([[26,20,13],[78,74,16],[112,116,10],[56,104,8]] as [$cx,$cy,$rad])
        <g transform="translate({{ $cx }} {{ $cy }})">
            @for ($p = 0; $p < 6; $p++)
                <ellipse cx="0" cy="{{ -$rad * 0.62 }}" rx="{{ $rad * 0.34 }}" ry="{{ $rad * 0.62 }}" fill="var(--nk-name)" fill-opacity=".32" transform="rotate({{ $p * 60 }})"/>
            @endfor
            <circle r="{{ $rad * 0.3 }}" fill="var(--nk-accent)"/>
        </g>
    @endforeach
</svg>
