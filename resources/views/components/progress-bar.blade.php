@props(['label' => null, 'caption' => null, 'value' => 0, 'max' => 100, 'over' => false])

@php $percent = $max > 0 ? min(100, round($value / $max * 100)) : 0; @endphp

<div {{ $attributes }}>
    @if ($label || $caption)
        <div class="flex items-center justify-between text-sm">
            @if ($label)<span class="font-medium">{{ $label }}</span>@endif
            @if ($caption)<span class="text-ink-muted">{{ $caption }}</span>@endif
        </div>
    @endif
    <div @class(['h-2.5 overflow-hidden rounded-full bg-surface-muted', 'mt-3' => $label || $caption])>
        <div @class(['h-full rounded-full transition-all', 'bg-amber-500' => $over, 'bg-brand-600' => ! $over]) style="width: {{ $percent }}%"></div>
    </div>
    {{ $slot }}
</div>
