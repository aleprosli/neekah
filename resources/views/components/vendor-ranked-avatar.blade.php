@props(['vendor'])

@php
    $frontRankFrames = [
        \App\Enums\VendorTier::New->value => 'new.png',
        \App\Enums\VendorTier::Verified->value => 'verified.png',
        \App\Enums\VendorTier::Trusted->value => 'trusted.png',
        \App\Enums\VendorTier::Top->value => 'top.png',
        \App\Enums\VendorTier::Recommended->value => 'elite.png',
    ];
    $frontRankFrame = $frontRankFrames[$vendor->tier->value];
    $hasCrownClearance = $vendor->tier === \App\Enums\VendorTier::Recommended;
    $vendorAvatarFit = $hasCrownClearance ? 'top-4 size-16' : 'top-2 size-16';
@endphp

<span
    data-ranked-vendor-avatar="{{ $vendor->tier->value }}"
    data-rank-artwork-layer="front"
    data-vendor-avatar-fit="{{ $hasCrownClearance ? 'elite-expanded' : 'large' }}"
    @if ($hasCrownClearance) data-vendor-avatar-position="crown-label-touch" @endif
    {{ $attributes->class('relative size-24 shrink-0') }}
>
    <x-vendor-avatar
        :vendor="$vendor"
        class="absolute left-1/2 z-10 -translate-x-1/2 border-2 border-white text-lg shadow-sm {{ $vendorAvatarFit }}"
    />
    <img
        src="{{ asset('img/vendor-rank-frames/'.$frontRankFrame) }}"
        alt=""
        aria-hidden="true"
        class="pointer-events-none absolute inset-0 z-20 size-full object-contain"
        width="96"
        height="96"
    >
</span>
