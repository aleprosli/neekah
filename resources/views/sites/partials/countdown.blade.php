@props(['site'])
@php $target = $site->event_date->copy()->setTimeFromTimeString($site->starts_at ?? '11:00:00')->toIso8601String(); @endphp
<div data-countdown="{{ $target }}" class="mx-auto grid max-w-xs grid-cols-4 gap-2 sm:gap-3">
    @foreach (['days' => 'Hari', 'hours' => 'Jam', 'minutes' => 'Minit', 'seconds' => 'Saat'] as $unit => $caption)
        <div class="nk-panel rounded-2xl px-1 py-3">
            <p class="nk-name text-2xl leading-none tabular-nums sm:text-3xl" data-unit="{{ $unit }}">00</p>
            <p class="nk-muted mt-1.5 text-[10px] tracking-[0.18em] uppercase">{{ $caption }}</p>
        </div>
    @endforeach
</div>
