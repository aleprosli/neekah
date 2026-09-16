@props(['status'])

@php
    $classes = match ($status->tone()) {
        'amber' => 'bg-amber-100 text-amber-800',
        'emerald' => 'bg-emerald-100 text-emerald-800',
        'sky' => 'bg-sky-100 text-sky-800',
        default => 'bg-surface-muted text-ink-muted',
    };
@endphp

<span {{ $attributes->class(['inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap', $classes]) }}>{{ $status->label() }}</span>
