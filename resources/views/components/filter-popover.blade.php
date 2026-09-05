@props(['label', 'active' => null, 'align' => 'left', 'width' => 'w-72'])

<details data-popover class="relative">
    <summary @class([
        'flex cursor-pointer list-none items-center gap-1.5 rounded-full border px-4 py-2 text-sm font-medium whitespace-nowrap transition select-none [&::-webkit-details-marker]:hidden',
        'border-brand-600 bg-brand-600 text-white' => $active,
        'border-line bg-surface-raised hover:border-brand-400' => ! $active,
    ])>
        {{ $active ?? $label }}
        <svg class="size-3.5 opacity-70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
    </summary>
    <div @class(['absolute top-full z-20 mt-2 max-w-[calc(100vw-2rem)] rounded-2xl border border-line bg-surface-raised p-4 shadow-xl shadow-brand-900/10', $width, 'left-0' => $align === 'left', 'left-0 sm:right-0 sm:left-auto' => $align === 'right'])>
        {{ $slot }}
    </div>
</details>
