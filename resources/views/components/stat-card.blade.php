@props(['label', 'value', 'hint' => null, 'href' => null, 'trend' => null])

@php $tag = $href ? 'a' : 'div'; @endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class(['flex flex-col gap-1 rounded-2xl border border-line bg-surface-raised p-5', 'transition hover:border-brand-300' => $href]) }}>
    <span class="text-xs font-semibold tracking-wide text-ink-muted uppercase">{{ $label }}</span>
    <span class="flex flex-wrap items-baseline gap-2">
        <span class="font-display text-2xl font-semibold">{{ $value }}</span>
        @if ($trend !== null)
            <span @class(['text-xs font-semibold', 'text-emerald-700' => $trend > 0, 'text-amber-700' => $trend < 0, 'text-ink-muted' => $trend == 0])>{{ $trend > 0 ? '+' : '' }}{{ number_format($trend, 1) }}%</span>
        @endif
    </span>
    @if ($hint)
        <span class="text-xs text-ink-muted">{{ $hint }}</span>
    @endif
</{{ $tag }}>
