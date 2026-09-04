@props(['label', 'value', 'hint' => null, 'href' => null])

@php $tag = $href ? 'a' : 'div'; @endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class(['flex flex-col gap-1 rounded-2xl border border-line bg-surface-raised p-5', 'transition hover:border-brand-300' => $href]) }}>
    <span class="text-xs font-semibold tracking-wide text-ink-muted uppercase">{{ $label }}</span>
    <span class="font-display text-2xl font-semibold">{{ $value }}</span>
    @if ($hint)
        <span class="text-xs text-ink-muted">{{ $hint }}</span>
    @endif
</{{ $tag }}>
