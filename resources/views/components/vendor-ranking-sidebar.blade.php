@props(['vendor'])

{{-- The vendor's rank on every page, in the sidebar: the same burgundy card
     as the couple's countdown. Kept light — no requirement queries here; the
     dashboard card and the points page explain the next rank. --}}
@php
    $rank = $vendor->tier->rank() + 1;
    $tier = $vendor->tier->label();
    $next = \App\Support\TierProgress::next($vendor->tier);
@endphp

<a
    href="{{ route('vendor.points.index') }}"
    data-vendor-ranking-sidebar
    class="group relative mx-4 mt-4 flex items-center gap-3 overflow-hidden rounded-2xl bg-linear-to-br from-brand-700 via-brand-800 to-brand-900 p-3 text-white shadow-md shadow-brand-900/20 transition hover:-translate-y-0.5"
>
    <span class="pointer-events-none absolute -top-8 -right-6 size-24 rounded-full bg-gold-300/20 blur-2xl" aria-hidden="true"></span>

    <x-vendor-rank-badge
        :tier="$vendor->tier"
        :label="__('pages.dash.logo_rank', ['rank' => $rank, 'tier' => $tier])"
        class="relative size-14 shrink-0 transition duration-300 group-hover:scale-105 motion-reduce:transform-none"
    />

    <span class="relative min-w-0 leading-tight">
        <span class="block font-script text-lg leading-none text-gold-300">{{ __('pages.ranking.eyebrow') }}</span>
        <span class="mt-1 block truncate font-display text-base font-semibold">{{ __('pages.dash.rank_tier', ['rank' => $rank, 'tier' => $tier]) }}</span>
        <span class="mt-1 block truncate text-[11px] text-white/75">
            {{ $next ? __('pages.ranking.next_short', ['tier' => $next->label()]) : __('pages.ranking.top_reached') }} →
        </span>
    </span>
</a>
