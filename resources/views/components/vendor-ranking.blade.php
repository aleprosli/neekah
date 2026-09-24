@props(['vendor'])

@php
    $rankIcons = [
        \App\Enums\VendorTier::New->value => 'new.png',
        \App\Enums\VendorTier::Verified->value => 'verified.png',
        \App\Enums\VendorTier::Trusted->value => 'trusted.png',
        \App\Enums\VendorTier::Top->value => 'top.png',
        \App\Enums\VendorTier::Recommended->value => 'elite.png',
    ];
    $rankGlows = [
        \App\Enums\VendorTier::New->value => 'bg-orange-400/45',
        \App\Enums\VendorTier::Verified->value => 'bg-emerald-400/45',
        \App\Enums\VendorTier::Trusted->value => 'bg-blue-500/45',
        \App\Enums\VendorTier::Top->value => 'bg-violet-500/45',
        \App\Enums\VendorTier::Recommended->value => 'bg-rose-600/45',
    ];
    $rankBorders = [
        \App\Enums\VendorTier::New->value => 'border-orange-300/70',
        \App\Enums\VendorTier::Verified->value => 'border-emerald-300/70',
        \App\Enums\VendorTier::Trusted->value => 'border-blue-300/70',
        \App\Enums\VendorTier::Top->value => 'border-violet-300/70',
        \App\Enums\VendorTier::Recommended->value => 'border-rose-300/70',
    ];
    $rankTargets = [
        \App\Enums\VendorTier::Trusted->value => ['completed' => 5, 'rating' => 4.0, 'reviews' => 3, 'response' => 0, 'completion' => 0],
        \App\Enums\VendorTier::Top->value => ['completed' => 15, 'rating' => 4.5, 'reviews' => 8, 'response' => 90, 'completion' => 0],
        \App\Enums\VendorTier::Recommended->value => ['completed' => 30, 'rating' => 4.7, 'reviews' => 15, 'response' => 95, 'completion' => 90],
    ];
    $tiers = collect(\App\Enums\VendorTier::cases())->map(function (\App\Enums\VendorTier $tier) use ($vendor, $rankIcons, $rankGlows, $rankBorders, $rankTargets): array {
        $reached = $tier->rank() <= $vendor->tier->rank();
        $missing = [];

        if (! $reached && $tier === \App\Enums\VendorTier::Verified) {
            $missing[] = __('pages.dash.rank_needs_approval');
        }

        if (! $reached && isset($rankTargets[$tier->value])) {
            $target = $rankTargets[$tier->value];
            $completedRemaining = max(0, $target['completed'] - $vendor->completed_bookings_count);
            $reviewsRemaining = max(0, $target['reviews'] - $vendor->reviews_count);
            $responseRemaining = max(0, $target['response'] - ($vendor->response_rate ?? 0));
            $completionRemaining = max(0, $target['completion'] - $vendor->completion_rate);

            if ($completedRemaining > 0) {
                $missing[] = __('pages.dash.rank_more_bookings', ['count' => $completedRemaining]);
            }

            if ($reviewsRemaining > 0) {
                $missing[] = __('pages.dash.rank_more_reviews', ['count' => $reviewsRemaining]);
            }

            if ((float) $vendor->rating_avg < $target['rating']) {
                $missing[] = __('pages.dash.rank_rating_target', [
                    'current' => number_format((float) $vendor->rating_avg, 1),
                    'target' => number_format($target['rating'], 1),
                ]);
            }

            if ($responseRemaining > 0) {
                $missing[] = __('pages.dash.rank_more_response', ['count' => $responseRemaining]);
            }

            if ($completionRemaining > 0) {
                $missing[] = __('pages.dash.rank_more_completion', ['count' => $completionRemaining]);
            }

            if ($missing === []) {
                $missing[] = __('pages.dash.rank_needs_clean_record');
            }
        }

        return [
            'label' => $tier->label(),
            'rank' => $tier->rank() + 1,
            'icon' => asset('img/vendor-ranks/'.$rankIcons[$tier->value]),
            'glow' => $rankGlows[$tier->value],
            'border' => $rankBorders[$tier->value],
            'reached' => $reached,
            'current' => $tier === $vendor->tier,
            'message' => $reached ? __('pages.dash.rank_achieved') : implode(' · ', $missing),
        ];
    });
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
            <div class="group relative grid size-20 shrink-0 place-items-center sm:size-24">
                <span class="absolute inset-2 scale-75 rounded-full blur-2xl transition duration-300 group-hover:scale-110 group-hover:opacity-100 {{ $currentTier['glow'] }}" aria-hidden="true"></span>
                <img
                    src="{{ $currentTier['icon'] }}"
                    alt="{{ __('pages.dash.logo_rank', ['rank' => $currentTier['rank'], 'tier' => $currentTier['label']]) }}"
                    class="relative size-full object-contain transition duration-300 ease-out group-hover:scale-105 motion-reduce:transform-none"
                    width="96"
                    height="96"
                >
            </div>
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

        <div data-vendor-rank-track class="no-scrollbar snap-x snap-mandatory overflow-x-auto px-1 pb-2 sm:snap-none">
            <ol class="relative grid min-w-[42rem] grid-cols-5 gap-2 px-4 pt-5 pb-4" aria-label="{{ __('pages.dash.tahap_ranking') }}">
                <li class="pointer-events-none absolute inset-0 z-0" aria-hidden="true">
                    <span class="absolute top-[5.25rem] right-[12%] left-[12%] h-2 -translate-y-1/2 overflow-hidden rounded-full bg-line">
                        <span class="block h-full rounded-full bg-brand-600/80 {{ $progressWidth }}"></span>
                    </span>
                </li>

                @foreach ($tiers as $tier)
                    <li
                        class="group relative z-10 flex min-w-0 snap-center flex-col items-center outline-none"
                        tabindex="0"
                        @if ($tier['current']) aria-current="step" data-current-vendor-rank @endif
                    >
                        <div class="relative grid size-32 place-items-center">
                            <span class="absolute inset-5 scale-50 rounded-full opacity-0 blur-xl transition duration-300 group-hover:scale-105 group-hover:opacity-100 group-focus-visible:scale-105 group-focus-visible:opacity-100 {{ $tier['glow'] }}" aria-hidden="true"></span>
                            <img
                                src="{{ $tier['icon'] }}"
                                alt="{{ __('pages.dash.logo_rank', ['rank' => $tier['rank'], 'tier' => $tier['label']]) }}"
                                class="relative z-10 size-24 object-contain transition duration-300 ease-out group-hover:scale-105 group-focus-visible:scale-105 motion-reduce:transform-none sm:size-28 {{ $tier['reached'] ? '' : 'opacity-80 grayscale brightness-75 contrast-75' }}"
                                width="112"
                                height="112"
                                loading="lazy"
                            >
                        </div>

                        <div class="mt-2 w-full rounded-xl border bg-surface-raised/95 px-3 py-2 text-center shadow-sm transition duration-300 group-hover:-translate-y-0.5 group-hover:shadow-md group-focus-visible:-translate-y-0.5 group-focus-visible:shadow-md {{ $tier['border'] }}">
                            <p class="text-[11px] font-semibold text-ink">{{ __('pages.dash.rank_tier', ['rank' => $tier['rank'], 'tier' => $tier['label']]) }}</p>
                            <p class="mt-1 text-[10px] leading-4 {{ $tier['reached'] ? 'font-semibold text-emerald-700' : 'text-ink-muted' }}" data-rank-message="{{ $tier['label'] }}">
                                @if ($tier['reached'])
                                    <span aria-hidden="true">✓</span>
                                @endif
                                {{ $tier['message'] }}
                            </p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
</section>
