@props(['tier'])

@php
    $tone = match ($tier) {
        \App\Enums\VendorTier::New => 'bg-amber-100/95 text-amber-900 ring-amber-300',
        \App\Enums\VendorTier::Verified => 'bg-emerald-100/95 text-emerald-900 ring-emerald-300',
        \App\Enums\VendorTier::Trusted => 'bg-blue-100/95 text-blue-900 ring-blue-300',
        \App\Enums\VendorTier::Top => 'bg-violet-100/95 text-violet-900 ring-violet-300',
        \App\Enums\VendorTier::Recommended => 'bg-gold-300 text-brand-900 ring-gold-400',
    };
@endphp

<span
    data-vendor-tier="{{ $tier->value }}"
    {{ $attributes->merge(['class' => 'z-10 inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold shadow-sm ring-1 backdrop-blur '.$tone]) }}
>
    <svg class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M8 4h8v3a4 4 0 0 1-8 0V4Z" />
        <path d="M8 6H5v1a4 4 0 0 0 4 4M16 6h3v1a4 4 0 0 1-4 4M12 11v5M9 20h6M10 16h4v4" />
    </svg>
    <span>{{ $tier->label() }}</span>
</span>
