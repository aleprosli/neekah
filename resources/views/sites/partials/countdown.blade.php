@props(['site'])
@php $target = $site->event_date->copy()->setTimeFromTimeString($site->starts_at ?? '11:00:00')->toIso8601String(); @endphp
<div data-countdown="{{ $target }}" class="nk-plaque mx-auto grid max-w-xs grid-cols-4 py-5">
    @foreach (['days' => 'Hari', 'hours' => 'Jam', 'minutes' => 'Minit', 'seconds' => 'Saat'] as $unit => $caption)
        <div @class(['px-1 text-center', 'nk-hairline border-l' => ! $loop->first])>
            <p class="nk-name text-3xl leading-none font-medium tabular-nums" data-unit="{{ $unit }}">00</p>
            <p class="nk-muted mt-2 text-[10px] tracking-[0.22em] uppercase">{{ $caption }}</p>
        </div>
    @endforeach
</div>
