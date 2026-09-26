@props(['label', 'tone' => 'muted'])

@php
    $classes = match ($tone) {
        'emerald' => 'bg-emerald-100 text-emerald-800',
        'amber' => 'bg-amber-100 text-amber-800',
        'sky' => 'bg-sky-100 text-sky-800',
        'red' => 'bg-red-100 text-red-800',
        'brand' => 'bg-brand-50 text-brand-700',
        default => 'bg-surface-muted text-ink-muted',
    };
@endphp

<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap {{ $classes }}">{{ $label }}</span>
