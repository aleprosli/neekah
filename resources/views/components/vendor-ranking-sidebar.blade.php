@props(['vendor'])

@php
    $rank = $vendor->tier->rank() + 1;
    $tier = $vendor->tier->label();
@endphp

<a
    href="{{ route('vendor.points.index') }}"
    data-vendor-ranking-sidebar
    class="group mx-4 mt-4 flex items-center gap-3 rounded-2xl border border-gold-300/60 bg-surface-raised/90 p-3 shadow-sm transition hover:border-brand-200 hover:bg-brand-50/70"
>
    <x-vendor-rank-badge
        :tier="$vendor->tier"
        :label="__('pages.dash.logo_rank', ['rank' => $rank, 'tier' => $tier])"
        class="size-16"
    />

    <span class="min-w-0 leading-tight">
        <span class="block text-[10px] font-semibold tracking-[0.15em] text-brand-700 uppercase">{{ __('pages.dash.ranking_vendor') }}</span>
        <span class="mt-1 block truncate font-display text-lg font-semibold text-ink">{{ __('pages.dash.rank_tier', ['rank' => $rank, 'tier' => $tier]) }}</span>
        <span class="mt-2 block text-[11px] font-medium text-brand-700 underline decoration-brand-300 underline-offset-4 transition group-hover:text-brand-900">{{ __('pages.dash.lihat_point_ranking') }}</span>
    </span>
</a>
