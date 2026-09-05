@props(['position' => 'top-left'])
@php
    $flip = ['top-right' => 'right-0 top-0 -scale-x-100', 'bottom-left' => 'left-0 bottom-0 -scale-y-100', 'bottom-right' => 'right-0 bottom-0 -scale-x-100 -scale-y-100'][$position] ?? 'left-0 top-0';
@endphp
<svg viewBox="0 0 200 200" fill="none" aria-hidden="true" class="pointer-events-none absolute {{ $flip }} nk-sway w-32 opacity-60 sm:w-44">
    <path d="M2 2c26 30 44 66 52 106 4 22 6 44 6 66" stroke="var(--nk-accent)" stroke-opacity=".7" stroke-width="1.4" stroke-linecap="round"/>
    @foreach ([[18,24,-40],[30,52,-20],[42,84,0],[52,118,18],[58,152,34],[26,38,140],[38,70,160],[48,104,178]] as [$x,$y,$rot])
        <path d="M0 0c9-6 18-4 22 3-7 6-16 5-22-3Z" fill="var(--nk-accent)" fill-opacity=".38" transform="translate({{ $x }} {{ $y }}) rotate({{ $rot }})"/>
    @endforeach
    @foreach ([[36,66],[54,126]] as [$cx,$cy])
        <circle cx="{{ $cx }}" cy="{{ $cy }}" r="4" fill="var(--nk-name)" fill-opacity=".45"/>
    @endforeach
</svg>
