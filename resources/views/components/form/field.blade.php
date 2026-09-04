@props(['label', 'name', 'type' => 'text', 'value' => null, 'required' => false, 'placeholder' => null, 'autocomplete' => null, 'help' => null])

<label class="flex flex-col gap-1.5">
    <span class="text-sm font-medium">{{ $label }}</span>
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        value="{{ $type === 'password' ? '' : old($name, $value) }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @required($required)
        {{ $attributes->class(['rounded-xl border bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-400/40 focus:outline-none', 'border-brand-400' => $errors->has($name), 'border-line' => ! $errors->has($name)]) }}
    >
    @if ($help)
        <span class="text-xs text-ink-muted">{{ $help }}</span>
    @endif
</label>
