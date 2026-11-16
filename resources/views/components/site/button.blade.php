@props([
    'href',
    /** primary: filled rose; secondary: gold outline. */
    'variant' => 'primary',
])

{{-- The two pill buttons of the public pages, so every hero and CTA matches. --}}
<a href="{{ $href }}" {{ $attributes->class([
    'inline-flex w-full items-center justify-center rounded-full px-6 py-3 text-base font-semibold transition sm:w-auto',
    'bg-brand-600 text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700' => $variant === 'primary',
    'border border-gold-400 bg-surface-raised hover:border-gold-500 hover:text-brand-700' => $variant === 'secondary',
]) }}>{{ $slot }}</a>
