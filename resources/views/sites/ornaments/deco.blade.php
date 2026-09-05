@props(['position' => 'top-left'])
@php
    $flip = ['top-right' => 'right-0 top-0 -scale-x-100', 'bottom-left' => 'left-0 bottom-0 -scale-y-100', 'bottom-right' => 'right-0 bottom-0 -scale-x-100 -scale-y-100'][$position] ?? 'left-0 top-0';
@endphp
{{-- Fine art-deco rays and brackets. --}}
<svg viewBox="0 0 180 180" fill="none" aria-hidden="true" class="pointer-events-none absolute {{ $flip }} w-28 opacity-55 sm:w-40">
    <path d="M4 4h84M4 4v84" stroke="var(--nk-accent)" stroke-width="1.6" stroke-linecap="round"/>
    <path d="M4 16h58M16 4v58" stroke="var(--nk-accent)" stroke-opacity=".6" stroke-width="1"/>
    <path d="M4 28h34M28 4v34" stroke="var(--nk-accent)" stroke-opacity=".4" stroke-width="1"/>
    @foreach ([28, 44, 60] as $i => $r)
        <path d="M4 4 {{ 4 + $r }} 4A{{ $r }} {{ $r }} 0 0 1 4 {{ 4 + $r }}Z" stroke="var(--nk-accent)" stroke-opacity=".{{ 3 - $i }}5" stroke-width="1" fill="none"/>
    @endforeach
    <circle cx="50" cy="50" r="4" fill="var(--nk-accent)"/>
</svg>
