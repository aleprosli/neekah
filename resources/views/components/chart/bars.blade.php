@props(['series' => [], 'format' => null, 'empty' => 'Tiada data untuk tempoh ini.'])

@php
    $rows = collect($series)->values();
    $max = (float) $rows->max('value') ?: 0.0;
    $formatValue = $format ?? fn ($value) => number_format((float) $value);
    $width = max(120, $rows->count() * 44);
@endphp

@if ($rows->isEmpty() || $max <= 0)
    <p class="py-8 text-center text-sm text-ink-muted">{{ $empty }}</p>
@else
    {{-- Drawn as SVG rather than pulled from a charting library, so the page
         stays dependency free and the bars read from the brand tokens.

         The bars and their month labels share one scroll container and one
         width, so a label can never drift away from the bar it names. --}}
    <div {{ $attributes->class(['overflow-x-auto']) }}>
        <div class="min-w-full" style="width: {{ $width }}px">
            <svg viewBox="0 0 {{ $width }} 120" class="h-40 w-full" role="img" preserveAspectRatio="none">
                @foreach ($rows as $i => $row)
                    @php $height = max(1, round((float) $row['value'] / $max * 100)); @endphp
                    <rect x="{{ $i * 44 + 8 }}" y="{{ 110 - $height }}" width="28" height="{{ $height }}" rx="4" fill="var(--color-brand-600)">
                        <title>{{ $row['label'] }}: {{ $formatValue($row['value']) }}</title>
                    </rect>
                @endforeach
            </svg>
            <ul class="mt-1 flex text-center text-[0.65rem] text-ink-muted">
                @foreach ($rows as $row)
                    <li class="min-w-0 flex-1 truncate">{{ $row['label'] }}</li>
                @endforeach
            </ul>
        </div>
    </div>
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
