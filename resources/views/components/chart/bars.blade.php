@props(['series' => [], 'format' => null, 'empty' => 'Tiada data untuk tempoh ini.'])

@php
    $rows = collect($series)->values();
    $max = (float) $rows->max('value') ?: 0.0;
    $formatValue = $format ?? fn ($value) => number_format((float) $value);
@endphp

@if ($rows->isEmpty() || $max <= 0)
    <p class="py-8 text-center text-sm text-ink-muted">{{ $empty }}</p>
@else
    {{-- Drawn as SVG rather than pulled from a charting library, so the page
         stays dependency free and the bars read from the brand tokens. --}}
    <div {{ $attributes->class(['overflow-x-auto']) }}>
        <svg viewBox="0 0 {{ max(120, $rows->count() * 44) }} 140" class="h-40 w-full min-w-[20rem]" role="img" preserveAspectRatio="none">
            @foreach ($rows as $i => $row)
                @php
                    $height = max(1, round((float) $row['value'] / $max * 100));
                    $x = $i * 44 + 8;
                @endphp
                <rect x="{{ $x }}" y="{{ 110 - $height }}" width="28" height="{{ $height }}" rx="4" fill="var(--color-brand-600)">
                    <title>{{ $row['label'] }}: {{ $formatValue($row['value']) }}</title>
                </rect>
            @endforeach
        </svg>
    </div>
    <ul class="mt-2 flex gap-1 overflow-x-auto text-center text-[0.65rem] text-ink-muted">
        @foreach ($rows as $row)
            <li class="w-11 shrink-0">{{ $row['label'] }}</li>
        @endforeach
    </ul>
    {{-- The same numbers in text, so the chart is never the only way to read them. --}}
    <details class="mt-2 text-xs text-ink-muted">
        <summary class="cursor-pointer">Lihat nombor</summary>
        <ul class="mt-2 flex flex-col gap-1">
            @foreach ($rows as $row)
                <li class="flex justify-between gap-4"><span>{{ $row['label'] }}</span><span>{{ $formatValue($row['value']) }}</span></li>
            @endforeach
        </ul>
    </details>
@endif
