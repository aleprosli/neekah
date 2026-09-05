@props(['position' => 'top-left'])
@php
    $flip = ['top-right' => 'right-0 top-0 -scale-x-100', 'bottom-left' => 'left-0 bottom-0 -scale-y-100', 'bottom-right' => 'right-0 bottom-0 -scale-x-100 -scale-y-100'][$position] ?? 'left-0 top-0';
@endphp
{{-- An eight-point star lattice, the geometry used on Islamic invitations. --}}
<svg viewBox="0 0 200 200" fill="none" aria-hidden="true" class="pointer-events-none absolute {{ $flip }} w-32 opacity-40 sm:w-44">
    <path d="M0 0h200M0 0v200" stroke="var(--nk-accent)" stroke-opacity=".5" stroke-width="1"/>
    <path d="M0 14h150M14 0v150" stroke="var(--nk-accent)" stroke-opacity=".35" stroke-width="1"/>
    @foreach ([[46, 46, 26], [104, 46, 18], [46, 104, 18], [92, 92, 12]] as [$cx, $cy, $r])
        <g transform="translate({{ $cx }} {{ $cy }})" stroke="var(--nk-accent)" stroke-opacity=".65" stroke-width="1.1">
            <rect x="{{ -$r }}" y="{{ -$r }}" width="{{ $r * 2 }}" height="{{ $r * 2 }}"/>
            <rect x="{{ -$r }}" y="{{ -$r }}" width="{{ $r * 2 }}" height="{{ $r * 2 }}" transform="rotate(45)"/>
            <circle r="{{ $r * 0.42 }}" fill="var(--nk-accent)" fill-opacity=".18"/>
        </g>
    @endforeach
</svg>
