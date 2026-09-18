@props(['vendor'])

@php $logo = $vendor->logoUrl(); @endphp

@if ($logo)
    <img src="{{ $logo }}" alt="Logo {{ $vendor->name }}" loading="lazy" {{ $attributes->class('shrink-0 rounded-full object-cover') }}>
@else
    {{-- A vendor with no logo keeps the initial in a tinted circle. --}}
    <span {{ $attributes->class(['flex shrink-0 items-center justify-center rounded-full bg-linear-to-br font-semibold text-white', $vendor->cover_tone]) }} aria-hidden="true">{{ mb_substr($vendor->name, 0, 1) }}</span>
@endif
