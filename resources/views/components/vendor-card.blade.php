@props(['vendor', 'category'])

<a href="{{ route('vendors.show', $vendor['slug']) }}" class="group flex flex-col gap-1">
    <div class="relative aspect-[4/5] overflow-hidden rounded-2xl bg-linear-to-br transition group-hover:shadow-xl group-hover:shadow-brand-900/10 {{ $vendor['tone'] }}">
        @if ($vendor['tier'] === 'Recommended')
            <span class="absolute top-3 left-3 rounded-full bg-gold-300 px-2.5 py-1 text-xs font-semibold text-brand-900 shadow-sm">🏆 Recommended</span>
        @elseif ($vendor['tier'] === 'Top')
            <span class="absolute top-3 left-3 rounded-full bg-white/95 px-2.5 py-1 text-xs font-semibold text-ink shadow-sm">Top vendor</span>
        @endif
        <span class="absolute top-3 right-3 text-white drop-shadow" aria-hidden="true">
            <svg class="size-6" viewBox="0 0 24 24" fill="rgba(0,0,0,.35)" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="m12 21-7.5-7.5a5 5 0 0 1 7.5-6.6 5 5 0 0 1 7.5 6.6Z"/></svg>
        </span>
        <span class="absolute inset-x-0 bottom-0 h-1/3 bg-linear-to-t from-black/35 to-transparent"></span>
        <span class="absolute bottom-3 left-3 text-3xl drop-shadow" aria-hidden="true">{{ $category['icon'] ?? '' }}</span>
    </div>

    <p class="mt-2 truncate text-[11px] font-semibold tracking-wide text-brand-600 uppercase">{{ $category['name'] ?? $vendor['category'] }} · {{ $vendor['state'] }}</p>
    <div class="flex items-start justify-between gap-2">
        <h3 class="truncate text-sm font-semibold sm:text-[15px]">{{ $vendor['name'] }}</h3>
        <span class="flex shrink-0 items-center gap-1 text-sm">
            <svg class="size-3.5 text-gold-500" viewBox="0 0 24 24" fill="currentColor"><path d="m12 2 2.9 6.6 7.1.7-5.3 4.8 1.6 7L12 17.5 5.7 21l1.6-7L2 9.3l7.1-.7Z"/></svg>
            {{ number_format($vendor['rating'], 1) }}
            <span class="text-ink-muted">({{ $vendor['reviews'] }})</span>
        </span>
    </div>
    <p class="truncate text-sm text-ink-muted">{{ $vendor['tagline'] }}</p>
    <p class="mt-1 text-sm text-ink-muted">Dari <span class="font-semibold text-ink">RM{{ number_format($vendor['price_from']) }}</span> / {{ $vendor['price_unit'] }}</p>
</a>
