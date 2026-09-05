@props(['series' => [], 'format' => null, 'empty' => 'Tiada data untuk tempoh ini.'])

@php
    $rows = collect($series)->values();
    $max = (float) $rows->max('value') ?: 0.0;
    $step = $rows->count() > 1 ? 300 / ($rows->count() - 1) : 0;
    $formatValue = $format ?? fn ($value) => number_format((float) $value, 1);
    $points = $rows->map(fn ($row, $i) => round($i * $step, 1).','.round(100 - ((float) $row['value'] / max($max, 0.001) * 90), 1))->implode(' ');
@endphp

@if ($rows->count() < 2 || $max <= 0)
    <p class="py-8 text-center text-sm text-ink-muted">{{ $empty }}</p>
@else
    <div {{ $attributes }}>
        <svg viewBox="0 0 300 110" class="h-32 w-full" role="img" preserveAspectRatio="none">
            <polyline points="{{ $points }}" fill="none" stroke="var(--color-brand-600)" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />
        </svg>
    </div>
    <div class="flex justify-between text-[0.65rem] text-ink-muted">
        <span>{{ $rows->first()['label'] }}</span>
        <span>{{ $rows->last()['label'] }}</span>
    </div>
    <details class="mt-2 text-xs text-ink-muted">
        <summary class="cursor-pointer">Lihat nombor</summary>
        <ul class="mt-2 flex flex-col gap-1">
            @foreach ($rows as $row)
                <li class="flex justify-between gap-4"><span>{{ $row['label'] }}</span><span>{{ $formatValue($row['value']) }}</span></li>
            @endforeach
        </ul>
    </details>
@endif
