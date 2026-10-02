@props(['vendor'])

{{-- The vendor's rank at the top of their dashboard, dressed like the
     couple's countdown card: where they stand, what the next rank still
     needs (TierProgress, from VendorTier::requirements), and the ladder of
     badges underneath. --}}
@php
    use App\Enums\VendorTier;
    use App\Support\TierProgress;

    $current = $vendor->tier;
    $next = TierProgress::next($current);
    $awaitingApproval = $current === VendorTier::New;
    $requirements = $next && ! $awaitingApproval ? TierProgress::requirementsFor($vendor, $next) : [];
    $metCount = collect($requirements)->where('met', true)->count();
    $glows = [
        VendorTier::New->value => 'bg-orange-400/50',
        VendorTier::Verified->value => 'bg-emerald-400/50',
        VendorTier::Trusted->value => 'bg-blue-400/50',
        VendorTier::Top->value => 'bg-violet-400/50',
        VendorTier::Recommended->value => 'bg-rose-400/50',
    ];
    $target = function (VendorTier $tier): string {
        $needs = $tier->requirements();

        if ($needs === null) {
            return $tier === VendorTier::New ? __('pages.ranking.target_new') : __('pages.ranking.target_verified');
        }

        return __('pages.ranking.target', ['reviews' => $needs['reviews'], 'rating' => number_format($needs['rating'], 1)])
            .($needs['clean_record'] ? ' · '.__('pages.ranking.target_clean') : '');
    };
    $tiers = collect(VendorTier::cases())->map(fn (VendorTier $tier): array => [
        'tier' => $tier,
        'rank' => $tier->rank() + 1,
        'label' => $tier->label(),
        'reached' => $tier->rank() <= $current->rank(),
        'current' => $tier === $current,
        'message' => $tier->rank() <= $current->rank() ? __('pages.dash.rank_achieved') : $target($tier),
    ]);
    $percent = (int) round($current->rank() / (count(VendorTier::cases()) - 1) * 100);
@endphp

<section data-vendor-ranking class="relative mb-8 overflow-hidden rounded-[1.75rem] bg-linear-to-br from-brand-700 via-brand-800 to-brand-900 text-white shadow-lg shadow-brand-900/20">
    <div class="pointer-events-none absolute -top-24 -right-16 size-72 rounded-full bg-gold-300/20 blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -bottom-28 -left-20 size-72 rounded-full bg-brand-400/30 blur-3xl" aria-hidden="true"></div>

    <div class="relative grid gap-6 p-6 sm:p-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,24rem)] lg:items-center lg:gap-10">
        {{-- Where they stand --}}
        <div class="flex min-w-0 flex-col items-center gap-5 text-center sm:flex-row sm:text-left">
            <div class="group relative grid size-28 shrink-0 place-items-center sm:size-32">
                <span class="absolute inset-3 rounded-full blur-2xl {{ $glows[$current->value] }}" aria-hidden="true"></span>
                <x-vendor-rank-badge
                    :tier="$current"
                    :label="__('pages.dash.logo_rank', ['rank' => $current->rank() + 1, 'tier' => $current->label()])"
                    class="relative size-full transition duration-300 ease-out group-hover:scale-105 motion-reduce:transform-none"
                />
            </div>
            <div class="min-w-0">
                <p class="font-script text-2xl leading-none text-gold-300">{{ __('pages.ranking.eyebrow') }}</p>
                <h2 class="mt-2 font-display text-3xl font-semibold sm:text-4xl">{{ __('pages.dash.rank_tier', ['rank' => $current->rank() + 1, 'tier' => $current->label()]) }}</h2>
                <div class="mt-3 flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold ring-1 ring-white/20">{{ __('pages.ranking.points', ['points' => number_format($vendor->points_total)]) }}</span>
                    <a href="{{ route('vendor.points.index') }}" class="text-xs font-semibold text-gold-300 underline decoration-gold-300/50 underline-offset-4 hover:text-white">{{ __('pages.dash.lihat_point_ranking') }} →</a>
                </div>
            </div>
        </div>

        {{-- What the next rank needs --}}
        <div class="rounded-2xl bg-white/10 p-4 ring-1 ring-white/15 backdrop-blur-sm sm:p-5">
            @if ($awaitingApproval)
                <p class="font-semibold">{{ __('pages.dash.rank_needs_approval') }}</p>
                <p class="mt-1 text-sm text-white/75">{{ __('pages.ranking.approval_body') }}</p>
            @elseif ($next === null)
                <p class="font-semibold">{{ __('pages.ranking.top_reached') }}</p>
                <p class="mt-1 text-sm text-white/75">{{ __('pages.ranking.top_reached_body') }}</p>
            @else
                <div class="flex items-baseline justify-between gap-3">
                    <p class="font-semibold">{{ __('pages.ranking.next', ['rank' => $next->rank() + 1, 'tier' => $next->label()]) }}</p>
                    <p class="shrink-0 text-xs text-gold-300">{{ __('pages.ranking.met', ['met' => $metCount, 'total' => count($requirements)]) }}</p>
                </div>
                <ul class="mt-3 flex flex-col gap-2.5 text-sm">
                    @foreach ($requirements as $row)
                        <li>
                            <div class="flex items-center gap-2.5">
                                <span @class([
                                    'flex size-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold',
                                    'bg-emerald-400 text-emerald-950' => $row['met'],
                                    'ring-1 ring-white/40' => ! $row['met'],
                                ]) aria-hidden="true">{{ $row['met'] ? '✓' : '' }}</span>
                                <span class="min-w-0 flex-1">{{ $row['label'] }}</span>
                                <span class="shrink-0 text-xs text-white/75 tabular-nums">{{ in_array($row['key'], ['reviews', 'rating'], true) ? $row['current'].' / '.$row['target'] : $row['current'] }}</span>
                            </div>
                            @if (in_array($row['key'], ['reviews', 'rating'], true))
                                <div class="mt-1.5 ml-7.5 h-1 overflow-hidden rounded-full bg-white/15">
                                    <div class="h-full rounded-full bg-linear-to-r from-gold-300 to-gold-400" style="width: {{ min(100, (float) $row['target'] > 0 ? round((float) $row['current'] / (float) $row['target'] * 100) : 0) }}%"></div>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
                <p class="mt-3 border-t border-white/10 pt-3 text-xs text-white/70">{{ __('pages.ranking.how') }}</p>
            @endif
        </div>
    </div>

    {{-- The ladder --}}
    <div data-vendor-rank-track class="relative border-t border-white/10 bg-black/15 px-3 pt-5 pb-4 sm:px-8">
        <p class="sr-only">{{ __('pages.dash.tahap_ranking') }}</p>
        <ol class="relative grid grid-cols-5 gap-1 sm:gap-3" aria-label="{{ __('pages.dash.tahap_ranking') }}">
            <li class="pointer-events-none absolute top-6 right-[10%] left-[10%] z-0 h-1 -translate-y-1/2 overflow-hidden rounded-full bg-white/15 sm:top-8" aria-hidden="true">
                <span class="block h-full rounded-full bg-linear-to-r from-gold-300 to-gold-400" style="width: {{ $percent }}%"></span>
            </li>
            @foreach ($tiers as $tier)
                <li class="relative z-10 flex min-w-0 flex-col items-center text-center" @if ($tier['current']) aria-current="step" data-current-vendor-rank @endif>
                    <span @class(['relative grid place-items-center rounded-full', 'ring-2 ring-gold-300 ring-offset-2 ring-offset-brand-900' => $tier['current']])>
                        <x-vendor-rank-badge
                            :tier="$tier['tier']"
                            :label="__('pages.dash.logo_rank', ['rank' => $tier['rank'], 'tier' => $tier['label']])"
                            :class="Arr::toCssClasses(['size-12 sm:size-16', 'opacity-50 grayscale' => ! $tier['reached']])"
                        />
                    </span>
                    <p @class(['mt-2 text-[10px] font-semibold sm:text-xs', 'text-gold-300' => $tier['current'], 'text-white' => ! $tier['current']])>{{ $tier['label'] }}</p>
                    <p class="mt-0.5 hidden text-[10px] leading-snug text-white/65 sm:block" data-rank-message="{{ $tier['label'] }}">
                        @if ($tier['reached'])<span aria-hidden="true">✓</span>@endif
                        {{ $tier['message'] }}
                    </p>
                </li>
            @endforeach
        </ol>
    </div>
</section>
