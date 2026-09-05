@props(['tone' => '#c98da5', 'accent' => '#d9b06a', 'position' => 'top-left'])

@php
    $flip = match ($position) {
        'top-right' => 'right-0 top-0 -scale-x-100',
        'bottom-left' => 'left-0 bottom-0 -scale-y-100',
        'bottom-right' => 'right-0 bottom-0 -scale-x-100 -scale-y-100',
        default => 'left-0 top-0',
    };
    $delay = ['top-left' => '0s', 'top-right' => '-1.8s', 'bottom-left' => '-3.4s', 'bottom-right' => '-5s'][$position] ?? '0s';
@endphp

{{-- A hand-drawn corner spray that breathes gently. --}}
<svg viewBox="0 0 220 220" fill="none" aria-hidden="true"
     class="pointer-events-none absolute {{ $flip }} nk-sway w-36 opacity-70 sm:w-52"
     style="animation-delay: {{ $delay }}">
    <path d="M4 6c38 4 72 22 96 50 18 21 28 46 32 74" stroke="{{ $tone }}" stroke-opacity=".55" stroke-width="1.6" stroke-linecap="round"/>
    <path d="M6 44c30 0 56 12 74 34" stroke="{{ $tone }}" stroke-opacity=".4" stroke-width="1.2" stroke-linecap="round"/>
    <path d="M44 6c2 28 12 52 30 70" stroke="{{ $tone }}" stroke-opacity=".4" stroke-width="1.2" stroke-linecap="round"/>

    {{-- Leaves --}}
    @foreach ([[34, 30, -18], [62, 58, 6], [92, 92, 22], [24, 66, -34], [70, 24, 30]] as [$x, $y, $rotate])
        <ellipse cx="{{ $x }}" cy="{{ $y }}" rx="13" ry="5.5" fill="{{ $tone }}" fill-opacity=".28" transform="rotate({{ $rotate }} {{ $x }} {{ $y }})"/>
    @endforeach

    {{-- Blooms --}}
    @foreach ([[26, 20, 13], [78, 74, 16], [112, 116, 10], [56, 104, 8]] as [$cx, $cy, $r])
        <g transform="translate({{ $cx }} {{ $cy }})">
            @for ($petal = 0; $petal < 6; $petal++)
                <ellipse cx="0" cy="{{ -$r * 0.62 }}" rx="{{ $r * 0.34 }}" ry="{{ $r * 0.62 }}" fill="{{ $tone }}" fill-opacity=".5" transform="rotate({{ $petal * 60 }})"/>
            @endfor
            <circle r="{{ $r * 0.3 }}" fill="{{ $accent }}" fill-opacity=".9"/>
        </g>
    @endforeach
</svg>
