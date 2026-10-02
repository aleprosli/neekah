@props(['vendor', 'props'])

{{-- The top of the vendor's dashboard: one burgundy card with the rank badge
     first (owner, 2 Oct 2026: the rank is the first thing a vendor should see),
     the greeting, the business and its plan. No view, rating or score numbers
     on purpose: traffic is still low, and a "0" here turns vendors away.
     Blade, not Vue, because the badge is a Blade-drawn SVG. --}}
@php
    $hour = (int) now()->format('G');
    $greeting = match (true) {
        $hour < 12 => 'morning',
        $hour < 15 => 'afternoon',
        $hour < 19 => 'evening',
        default => 'night',
    };
    $info = $props['vendor'];
    $standing = $props['standing'];
    $isPro = $info['plan'] === 'pro';
    $rank = $vendor->tier->rank() + 1;
    $next = \App\Support\TierProgress::next($vendor->tier);
    $glows = [
        \App\Enums\VendorTier::New->value => 'bg-orange-400/50',
        \App\Enums\VendorTier::Verified->value => 'bg-emerald-400/50',
        \App\Enums\VendorTier::Trusted->value => 'bg-blue-400/50',
        \App\Enums\VendorTier::Top->value => 'bg-violet-400/50',
        \App\Enums\VendorTier::Recommended->value => 'bg-rose-400/50',
    ];
@endphp

<section data-vendor-hero class="relative overflow-hidden rounded-[1.75rem] bg-linear-to-br from-brand-700 via-brand-800 to-brand-900 text-white shadow-lg shadow-brand-900/20">
    <div class="pointer-events-none absolute -top-24 -right-16 size-72 rounded-full bg-gold-300/20 blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -bottom-28 -left-20 size-72 rounded-full bg-brand-400/30 blur-3xl" aria-hidden="true"></div>

    <div class="relative p-6 sm:p-8">
        <div class="flex min-w-0 flex-col items-center gap-5 text-center sm:flex-row sm:text-left">
            {{-- The rank, first --}}
            <a href="{{ route('vendor.points.index') }}" class="group relative grid size-28 shrink-0 place-items-center sm:size-32" aria-label="{{ __('pages.dash.lihat_point_ranking') }}">
                <span class="absolute inset-3 rounded-full blur-2xl {{ $glows[$vendor->tier->value] }}" aria-hidden="true"></span>
                <x-vendor-rank-badge
                    :tier="$vendor->tier"
                    :label="__('pages.dash.logo_rank', ['rank' => $rank, 'tier' => $vendor->tier->label()])"
                    class="relative size-full transition duration-300 ease-out group-hover:scale-105 motion-reduce:transform-none"
                />
            </a>

            <div class="min-w-0">
                <p class="font-script text-2xl leading-none text-gold-300 sm:text-3xl">{{ __('ui.dashboard_greeting.'.$greeting, ['name' => $info['firstName']]) }}</p>
                <h1 class="mt-2 font-display text-3xl leading-tight font-semibold break-words sm:text-4xl">{{ $info['name'] }}</h1>
                <div class="mt-3 flex flex-wrap justify-center gap-2 text-xs sm:justify-start">
                    <span class="rounded-full bg-gold-300 px-3 py-1 font-bold text-brand-900">{{ __('pages.dash.rank_tier', ['rank' => $rank, 'tier' => $vendor->tier->label()]) }}</span>
                    <span @class(['rounded-full px-3 py-1 font-semibold', 'bg-white text-brand-900' => $isPro, 'bg-white/10 ring-1 ring-white/20' => ! $isPro])>
                        {{ $isPro ? __('ui.vendor_home.plan_pro', ['date' => $info['proUntil']]) : __('ui.vendor_home.plan_basic') }}
                    </span>
                    @if ($standing['trending'])
                        <span class="rounded-full bg-white/10 px-3 py-1 font-medium ring-1 ring-white/20">🔥 {{ __('ui.vendor_home.trending') }}</span>
                    @endif
                    @if ($standing['boostedUntil'])
                        <span class="rounded-full bg-white/10 px-3 py-1 font-medium ring-1 ring-white/20">🚀 {{ __('ui.vendor_home.boosted_until', ['date' => $standing['boostedUntil']]) }}</span>
                    @endif
                </div>
                @if ($next)
                    <p class="mt-2 text-xs text-white/70">{{ __('pages.ranking.next_short', ['tier' => $next->label()]) }} · <a href="#ranking" class="font-semibold text-gold-300 underline decoration-gold-300/50 underline-offset-4 hover:text-white">{{ __('pages.ranking.see_how') }}</a></p>
                @endif
                <div class="mt-5 flex flex-wrap justify-center gap-2 sm:justify-start">
                    @if ($info['recordBookingUrl'])
                        <a href="{{ $info['recordBookingUrl'] }}" class="rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-brand-800 shadow-sm transition hover:bg-gold-300">+ {{ __('ui.vendor_home.record_booking') }}</a>
                    @else
                        <a href="{{ $info['proUrl'] }}" class="rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-brand-800 shadow-sm transition hover:bg-gold-300">{{ __('ui.vendor_home.upgrade') }}</a>
                    @endif
                    <a href="{{ $info['publicUrl'] }}" target="_blank" rel="noopener" class="rounded-full px-5 py-2.5 text-sm font-semibold text-white ring-1 ring-white/40 transition hover:bg-white/10">{{ __('ui.vendor_home.public_page') }}</a>
                </div>
            </div>
        </div>

    </div>
</section>
