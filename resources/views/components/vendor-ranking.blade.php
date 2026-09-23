@props(['vendor'])

@php
    $rankIcons = [
        \App\Enums\VendorTier::New->value => 'new.png',
        \App\Enums\VendorTier::Verified->value => 'verified.png',
        \App\Enums\VendorTier::Trusted->value => 'trusted.png',
        \App\Enums\VendorTier::Top->value => 'top.png',
        \App\Enums\VendorTier::Recommended->value => 'elite.png',
    ];
    $tiers = collect(\App\Enums\VendorTier::cases())->map(fn (\App\Enums\VendorTier $tier): array => [
        'label' => $tier->label(),
        'rank' => $tier->rank() + 1,
        'icon' => asset('img/vendor-ranks/'.$rankIcons[$tier->value]),
        'reached' => $tier->rank() <= $vendor->tier->rank(),
        'current' => $tier === $vendor->tier,
    ]);
    $currentTier = $tiers->firstWhere('current', true);
    $progressWidth = [
        1 => 'w-0',
        2 => 'w-1/4',
        3 => 'w-1/2',
        4 => 'w-3/4',
        5 => 'w-full',
    ][$currentTier['rank']];
@endphp

<section data-vendor-ranking class="mb-8 overflow-hidden rounded-3xl border border-line bg-surface-raised shadow-sm">
    <div class="grid gap-5 p-5 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center sm:p-6">
        <div class="flex min-w-0 items-center gap-4">
            <img
                src="{{ $currentTier['icon'] }}"
                alt="{{ __('pages.dash.logo_rank', ['rank' => $currentTier['rank'], 'tier' => $currentTier['label']]) }}"
                class="size-20 shrink-0 object-contain sm:size-24"
                width="96"
                height="96"
            >
            <div class="min-w-0">
                <p class="text-xs font-semibold tracking-[0.16em] text-brand-700 uppercase">{{ __('pages.dash.ranking_vendor') }}</p>
                <h2 class="mt-1 truncate font-display text-2xl font-semibold sm:text-3xl">
                    {{ __('pages.dash.rank_tier', ['rank' => $currentTier['rank'], 'tier' => $currentTier['label']]) }}
                </h2>
                <a href="{{ route('vendor.points.index') }}" class="mt-2 inline-flex text-sm font-medium text-brand-700 underline decoration-brand-300 underline-offset-4 transition hover:text-brand-900">
                    {{ __('pages.dash.lihat_point_ranking') }}
                </a>
            </div>
        </div>

        <div class="rounded-2xl border border-brand-200 bg-brand-50 px-5 py-4 sm:min-w-40 sm:text-right">
            <p class="text-xs font-semibold tracking-[0.14em] text-brand-700 uppercase">{{ __('pages.dash.point_prestasi') }}</p>
            <p class="mt-1 font-display text-3xl font-semibold text-brand-900">{{ number_format($vendor->points_total) }}</p>
            <p class="text-xs text-brand-700">{{ __('pages.dash.point') }}</p>
        </div>
    </div>

    <div class="border-t border-line bg-surface-muted/50 px-4 py-5 sm:px-6">
        <p class="mb-4 text-xs font-semibold tracking-[0.14em] text-ink-muted uppercase">{{ __('pages.dash.tahap_ranking') }}</p>

        <div data-vendor-rank-track class="no-scrollbar snap-x snap-mandatory overflow-x-auto pb-1 sm:snap-none">
            <ol class="relative grid min-w-[44rem] grid-cols-5" aria-label="{{ __('pages.dash.tahap_ranking') }}">
                <li class="pointer-events-none absolute inset-0 z-0" aria-hidden="true">
                    <span class="absolute top-1/2 right-[10%] left-[10%] h-2 -translate-y-1/2 overflow-hidden rounded-full bg-line">
                        <span class="block h-full rounded-full bg-brand-600/80 {{ $progressWidth }}"></span>
                    </span>
                </li>

                @foreach ($tiers as $tier)
                    <li class="relative flex min-w-0 snap-center items-center justify-center" @if ($tier['current']) aria-current="step" data-current-vendor-rank @endif>
                        <img
                            src="{{ $tier['icon'] }}"
                            alt="{{ __('pages.dash.logo_rank', ['rank' => $tier['rank'], 'tier' => $tier['label']]) }}"
                            class="relative z-10 size-24 object-contain transition sm:size-28 {{ $tier['reached'] ? '' : 'opacity-80 grayscale brightness-75 contrast-75' }}"
                            width="112"
                            height="112"
                            loading="lazy"
                        >
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
</section>
