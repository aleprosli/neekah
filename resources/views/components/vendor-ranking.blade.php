@props(['vendor'])

{{-- The vendor's ranking beside their dashboard: what the next rank still
     needs (TierProgress, from VendorTier::requirements) and the whole ladder
     of badges. Where they stand is in the hero above (x-vendor-hero), so
     this one stays light with a gold head. --}}
@php
    use App\Enums\VendorTier;
    use App\Support\ProSettings;
    use App\Support\TierProgress;

    $current = $vendor->tier;
    $next = TierProgress::next($current);
    $awaitingApproval = $current === VendorTier::New;
    $requirements = $next && ! $awaitingApproval ? TierProgress::requirementsFor($vendor, $next) : [];
    $metCount = collect($requirements)->where('met', true)->count();
    $target = function (VendorTier $tier): string {
        $needs = $tier->requirements();

        if ($needs === null) {
            return $tier === VendorTier::New ? __('pages.ranking.target_new') : __('pages.ranking.target_verified');
        }

        return __('pages.ranking.target', ['reviews' => $needs['reviews'], 'rating' => number_format($needs['rating'], 1)])
            .($needs['clean_record'] ? ' · '.__('pages.ranking.target_clean') : '');
    };
    $tiers = collect(VendorTier::cases())->reverse()->map(fn (VendorTier $tier): array => [
        'tier' => $tier,
        'rank' => $tier->rank() + 1,
        'label' => $tier->label(),
        'reached' => $tier->rank() <= $current->rank(),
        'current' => $tier === $current,
        'message' => $tier->rank() <= $current->rank() ? __('pages.dash.rank_achieved') : $target($tier),
    ]);
    $elite = app(ProSettings::class)->eliteEnabled() ? match (true) {
        $vendor->isElite() => 'elite',
        $vendor->isPro() => 'need_tier',
        default => 'need_pro',
    } : null;
@endphp

<section data-vendor-ranking class="overflow-hidden rounded-[1.75rem] bg-surface-raised shadow-sm shadow-brand-900/5 ring-1 ring-gold-300/60">
    {{-- The badge and the rank itself sit in the hero (x-vendor-hero); this card is how to climb. --}}
    <div class="flex items-center justify-between gap-3 bg-linear-to-r from-gold-300/40 via-surface-raised to-brand-50 px-5 py-4">
        <p class="font-script text-2xl leading-none text-gold-600">{{ __('pages.ranking.eyebrow') }}</p>
        <p class="text-xs font-medium text-ink-muted">{{ __('pages.ranking.points', ['points' => number_format($vendor->points_total)]) }}</p>
    </div>

    {{-- What the next rank needs --}}
    <div class="border-t border-gold-300/40 p-5">
        @if ($awaitingApproval)
            <p class="font-semibold">{{ __('pages.dash.rank_needs_approval') }}</p>
            <p class="mt-1 text-sm text-ink-muted">{{ __('pages.ranking.approval_body') }}</p>
        @elseif ($next === null)
            <p class="font-semibold">{{ __('pages.ranking.top_reached') }}</p>
            <p class="mt-1 text-sm text-ink-muted">{{ __('pages.ranking.top_reached_body') }}</p>
        @else
            <div class="flex items-baseline justify-between gap-3">
                <p class="font-semibold">{{ __('pages.ranking.next', ['rank' => $next->rank() + 1, 'tier' => $next->label()]) }}</p>
                <p class="shrink-0 text-xs font-medium text-brand-700">{{ __('pages.ranking.met', ['met' => $metCount, 'total' => count($requirements)]) }}</p>
            </div>
            <ul class="mt-3 flex flex-col gap-2.5 text-sm">
                @foreach ($requirements as $row)
                    <li>
                        <div class="flex items-center gap-2.5">
                            <span @class([
                                'flex size-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold',
                                'bg-emerald-500 text-white' => $row['met'],
                                'ring-1 ring-line' => ! $row['met'],
                            ]) aria-hidden="true">{{ $row['met'] ? '✓' : '' }}</span>
                            <span class="min-w-0 flex-1">{{ $row['label'] }}</span>
                            <span class="shrink-0 text-xs text-ink-muted tabular-nums">{{ in_array($row['key'], ['reviews', 'rating'], true) ? $row['current'].' / '.$row['target'] : $row['current'] }}</span>
                        </div>
                        @if (in_array($row['key'], ['reviews', 'rating'], true))
                            <div class="mt-1.5 ml-7.5 h-1 overflow-hidden rounded-full bg-surface-muted">
                                <div class="h-full rounded-full bg-linear-to-r from-brand-500 to-gold-400" style="width: {{ min(100, (float) $row['target'] > 0 ? round((float) $row['current'] / (float) $row['target'] * 100) : 0) }}%"></div>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
            <p class="mt-3 text-xs text-ink-muted">{{ __('pages.ranking.how') }}</p>
        @endif
    </div>

    {{-- The ladder, top rank first --}}
    <div data-vendor-rank-track class="border-t border-line px-3 py-3">
        <ol class="flex flex-col gap-1" aria-label="{{ __('pages.dash.tahap_ranking') }}">
            @foreach ($tiers as $tier)
                <li @class([
                    'flex items-center gap-3 rounded-2xl px-2 py-1.5',
                    'bg-brand-50 ring-1 ring-brand-100' => $tier['current'],
                ]) @if ($tier['current']) aria-current="step" data-current-vendor-rank @endif>
                    <x-vendor-rank-badge
                        :tier="$tier['tier']"
                        :label="__('pages.dash.logo_rank', ['rank' => $tier['rank'], 'tier' => $tier['label']])"
                        :class="Arr::toCssClasses(['size-10 shrink-0', 'opacity-40 grayscale' => ! $tier['reached']])"
                    />
                    <div class="min-w-0 flex-1">
                        <p @class(['text-sm font-semibold', 'text-brand-700' => $tier['current'], 'text-ink-muted' => ! $tier['reached']])>{{ __('pages.dash.rank_tier', ['rank' => $tier['rank'], 'tier' => $tier['label']]) }}</p>
                        <p @class(['truncate text-xs', 'text-emerald-700' => $tier['reached'], 'text-ink-muted' => ! $tier['reached']]) data-rank-message="{{ $tier['label'] }}">
                            @if ($tier['reached'])<span aria-hidden="true">✓</span>@endif
                            {{ $tier['message'] }}
                        </p>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>

    {{-- Pro Elite, and what stands between the vendor and it --}}
    @if ($elite === 'elite')
        <p class="border-t border-line bg-linear-to-r from-ink to-brand-900 px-5 py-3 text-xs text-surface"><span class="font-semibold text-gold-300">✦ {{ __('ui.vendor_home.elite_yes') }}</span> · {{ __('ui.vendor_home.elite_yes_body') }}</p>
    @elseif ($elite === 'need_tier')
        <p class="border-t border-line bg-gold-300/10 px-5 py-3 text-xs text-ink-muted">✦ {{ __('ui.vendor_home.elite_need_tier') }}</p>
    @elseif ($elite === 'need_pro')
        <a href="{{ route('vendor.pro.index') }}" class="block border-t border-line bg-gold-300/10 px-5 py-3 text-xs text-ink-muted hover:text-ink">✦ {{ __('ui.vendor_home.elite_need_pro') }}</a>
    @endif

    <a href="{{ route('vendor.points.index') }}" class="block border-t border-line px-5 py-3 text-center text-sm font-semibold text-brand-700 transition hover:bg-brand-50">{{ __('pages.dash.lihat_point_ranking') }} →</a>
</section>
