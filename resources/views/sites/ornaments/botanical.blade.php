@props(['position' => 'top-left'])
@php
    $flip = ['top-right' => 'right-0 top-0 -scale-x-100', 'bottom-left' => 'left-0 bottom-0 -scale-y-100', 'bottom-right' => 'right-0 bottom-0 -scale-x-100 -scale-y-100'][$position] ?? 'left-0 top-0';
    $delay = ['top-left' => '-0.6s', 'top-right' => '-2.6s', 'bottom-left' => '-4.2s', 'bottom-right' => '-1.2s'][$position] ?? '0s';
@endphp
<svg viewBox="0 0 200 200" fill="none" aria-hidden="true" class="pointer-events-none absolute {{ $flip }} nk-sway w-32 opacity-65 sm:w-44" style="animation-delay: {{ $delay }}">
    @foreach ([[0, 1], [22, 0.85], [-18, 0.7]] as [$angle, $scale])
        <g transform="translate(10 8) rotate({{ $angle }}) scale({{ $scale }})">
            <path d="M0 0c14 34 30 66 52 96" stroke="var(--nk-accent)" stroke-opacity=".6" stroke-width="1.3" stroke-linecap="round"/>
            @for ($leaf = 1; $leaf <= 7; $leaf++)
                <ellipse cx="{{ $leaf * 7 }}" cy="{{ $leaf * 13 }}" rx="15" ry="5" fill="var(--nk-accent)" fill-opacity=".3" transform="rotate({{ 40 + $leaf * 3 }} {{ $leaf * 7 }} {{ $leaf * 13 }})"/>
                <ellipse cx="{{ $leaf * 7 - 12 }}" cy="{{ $leaf * 13 + 4 }}" rx="13" ry="4.5" fill="var(--nk-accent)" fill-opacity=".22" transform="rotate({{ -30 - $leaf * 2 }} {{ $leaf * 7 - 12 }} {{ $leaf * 13 + 4 }})"/>
            @endfor
        </g>
    @endforeach
</svg>
