@props([
    'action',
    'method' => 'POST',
    'title',
    'message' => null,
    'confirm' => 'Teruskan',
    'cancel' => 'Batal',
    'tone' => 'brand',
    'icon' => null,
    'triggerClass' => 'rounded-full border border-line px-3 py-1.5 text-xs font-medium transition hover:border-brand-400 hover:text-brand-700',
])

@php
    $id = 'confirm-'.Str::random(8);
    $spoofed = in_array(strtoupper($method), ['PUT', 'PATCH', 'DELETE'], true);
    $confirmClasses = match ($tone) {
        'danger' => 'bg-red-600 text-white hover:bg-red-700',
        default => 'bg-brand-600 text-white hover:bg-brand-700',
    };
    $iconClasses = match ($tone) {
        'danger' => 'bg-red-50 text-red-600',
        default => 'bg-brand-50 text-brand-600',
    };
@endphp

<button type="button" data-dialog-open="{{ $id }}" {{ $attributes->class($triggerClass) }}>{{ $slot }}</button>

<dialog id="{{ $id }}" class="m-auto w-full max-w-md rounded-3xl bg-surface-raised p-0 text-ink shadow-2xl backdrop:bg-black/40 max-sm:mt-auto max-sm:mb-0 max-sm:rounded-b-none">
    <div class="flex flex-col gap-4 p-6 text-left sm:p-7">
        <div class="flex items-start gap-4">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl {{ $iconClasses }}">
                @if ($icon)
                    {!! $icon !!}
                @elseif ($tone === 'danger')
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                @else
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><circle cx="12" cy="12" r="9"/></svg>
                @endif
            </span>
            <div class="min-w-0">
                <h2 class="font-display text-lg font-semibold">{{ $title }}</h2>
                @if ($message)
                    <p class="mt-1 text-sm text-ink-muted">{{ $message }}</p>
                @endif
            </div>
        </div>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" data-dialog-close class="rounded-full border border-line px-5 py-2.5 text-sm font-medium transition hover:bg-surface-muted">{{ $cancel }}</button>
            <form method="POST" action="{{ $action }}">
                @csrf
                @if ($spoofed)
                    @method($method)
                @endif
                <button type="submit" class="w-full rounded-full px-5 py-2.5 text-sm font-semibold transition sm:w-auto {{ $confirmClasses }}">{{ $confirm }}</button>
            </form>
        </div>
    </div>
</dialog>
