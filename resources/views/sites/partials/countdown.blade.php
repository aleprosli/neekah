@props(['site', 'tone' => 'light'])

@php
    $target = $site->event_date->copy()->setTimeFromTimeString($site->starts_at ?? '11:00:00')->toIso8601String();
    $box = $tone === 'dark'
        ? 'border-white/15 bg-white/5 text-white'
        : 'border-black/10 bg-white/70 text-neutral-900';
    $label = $tone === 'dark' ? 'text-white/50' : 'text-neutral-500';
@endphp

<div data-countdown="{{ $target }}" class="mx-auto grid max-w-xs grid-cols-4 gap-2 sm:gap-3">
    @foreach (['days' => 'Hari', 'hours' => 'Jam', 'minutes' => 'Minit', 'seconds' => 'Saat'] as $unit => $caption)
        <div class="rounded-2xl border px-1 py-3 {{ $box }}">
            <p class="font-display text-2xl leading-none tabular-nums sm:text-3xl" data-unit="{{ $unit }}">00</p>
            <p class="mt-1.5 text-[10px] tracking-[0.18em] uppercase {{ $label }}">{{ $caption }}</p>
        </div>
    @endforeach
</div>
