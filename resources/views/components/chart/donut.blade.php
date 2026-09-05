@props(['series' => [], 'format' => null, 'empty' => 'Tiada data untuk tempoh ini.'])

@php
    $rows = collect($series)->values()->filter(fn ($row) => (float) $row['value'] > 0)->values();
    $total = (float) $rows->sum('value');
    $formatValue = $format ?? fn ($value) => number_format((float) $value);
    $shades = ['var(--color-brand-600)', 'var(--color-brand-400)', 'var(--color-gold-500)', 'var(--color-brand-800)', 'var(--color-brand-200)', 'var(--color-gold-300)'];
    $offset = 0.0;
@endphp

@if ($rows->isEmpty() || $total <= 0)
    <p class="py-8 text-center text-sm text-ink-muted">{{ $empty }}</p>
@else
    <div {{ $attributes->class(['flex flex-wrap items-center gap-6']) }}>
        <svg viewBox="0 0 42 42" class="size-32 shrink-0 -rotate-90" role="img">
            @foreach ($rows as $i => $row)
                @php
                    $share = round((float) $row['value'] / $total * 100, 2);
                    $colour = $shades[$i % count($shades)];
                    $dash = $share.' '.(100 - $share);
                    $rotation = $offset;
                    $offset += $share;
                @endphp
                <circle cx="21" cy="21" r="15.915" fill="none" stroke="{{ $colour }}" stroke-width="6"
                    stroke-dasharray="{{ $dash }}" stroke-dashoffset="{{ 100 - $rotation }}">
                    <title>{{ $row['label'] }}: {{ $formatValue($row['value']) }}</title>
                </circle>
            @endforeach
        </svg>
        <ul class="flex min-w-0 flex-1 flex-col gap-1.5 text-sm">
            @foreach ($rows as $i => $row)
                <li class="flex items-center justify-between gap-3">
                    <span class="flex min-w-0 items-center gap-2">
                        <span class="size-2.5 shrink-0 rounded-full" style="background: {{ $shades[$i % count($shades)] }}"></span>
                        <span class="truncate">{{ $row['label'] }}</span>
                    </span>
                    <span class="shrink-0 text-ink-muted">{{ $formatValue($row['value']) }}</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif
