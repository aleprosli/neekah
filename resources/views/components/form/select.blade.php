@props(['label', 'name', 'value' => null, 'required' => false, 'help' => null])

<label class="flex flex-col gap-1.5">
    <span class="text-sm font-medium">{{ $label }}</span>
    <select name="{{ $name }}" @required($required) {{ $attributes->class(['rounded-xl border bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-400/40 focus:outline-none', 'border-brand-400' => $errors->has($name), 'border-line' => ! $errors->has($name)]) }}>
        {{ $slot }}
    </select>
    @if ($help)
        <span class="text-xs text-ink-muted">{{ $help }}</span>
    @endif
</label>
