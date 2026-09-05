@props(['vendor', 'comparable' => false])

@php use App\Enums\VendorTier; @endphp

<div class="group relative flex flex-col gap-1">
    <a href="{{ route('vendors.show', $vendor) }}" class="flex flex-col gap-1">
        <div class="relative aspect-[4/5] overflow-hidden rounded-2xl bg-linear-to-br transition group-hover:shadow-xl group-hover:shadow-brand-900/10 {{ $vendor->cover_tone }}">
            @if ($vendor->cover_image)
                <img src="{{ Storage::disk('public')->url($vendor->cover_image) }}" alt="" class="absolute inset-0 size-full object-cover">
            @endif
            @if ($vendor->tier === VendorTier::Recommended)
                <span class="absolute top-3 left-3 rounded-full bg-gold-300 px-2.5 py-1 text-xs font-semibold text-brand-900 shadow-sm">🏆 Recommended</span>
            @elseif ($vendor->tier === VendorTier::Top)
                <span class="absolute top-3 left-3 rounded-full bg-white/95 px-2.5 py-1 text-xs font-semibold text-ink shadow-sm">Top vendor</span>
            @endif
            <span class="absolute inset-x-0 bottom-0 h-1/3 bg-linear-to-t from-black/35 to-transparent"></span>
            <span class="absolute bottom-3 left-3 text-3xl drop-shadow" aria-hidden="true">{{ $vendor->category->icon }}</span>
        </div>

        <p class="mt-2 truncate text-[11px] font-semibold tracking-wide text-brand-600 uppercase">{{ $vendor->category->name }} · {{ $vendor->state }}</p>
        <div class="flex items-start justify-between gap-2">
            <h3 class="truncate text-sm font-semibold sm:text-[15px]">{{ $vendor->name }}</h3>
            <span class="flex shrink-0 items-center gap-1 text-sm">
                <svg class="size-3.5 text-gold-500" viewBox="0 0 24 24" fill="currentColor"><path d="m12 2 2.9 6.6 7.1.7-5.3 4.8 1.6 7L12 17.5 5.7 21l1.6-7L2 9.3l7.1-.7Z"/></svg>
                @if ($vendor->reviews_count)
                    {{ number_format($vendor->rating_avg, 1) }}
                    <span class="text-ink-muted">({{ $vendor->reviews_count }})</span>
                @else
                    <span class="text-ink-muted">Baru</span>
                @endif
            </span>
        </div>
        <p class="truncate text-sm text-ink-muted">{{ $vendor->tagline }}</p>
        <p class="mt-1 text-sm text-ink-muted">Dari <span class="font-semibold text-ink">RM{{ number_format($vendor->price_from) }}</span> / {{ $vendor->price_unit->label() }}</p>
    </a>

    @if ($comparable)
        {{-- One icon toggle in the corner, so it never crowds the tier badge. --}}
        <label class="absolute top-2.5 right-2.5 cursor-pointer" title="Tambah ke senarai banding">
            <input type="checkbox" data-compare="{{ $vendor->slug }}" data-compare-name="{{ $vendor->name }}" class="peer sr-only">
            <span class="flex size-9 items-center justify-center rounded-full bg-white/90 text-ink shadow-sm backdrop-blur transition peer-checked:bg-brand-600 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand-400 hover:bg-white">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h11M4 17h11"/><path d="m17 4 3 3-3 3M17 14l3 3-3 3"/></svg>
                <span class="sr-only">Banding {{ $vendor->name }}</span>
            </span>
        </label>
    @endif
</div>
