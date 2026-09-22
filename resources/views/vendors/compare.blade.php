@php use App\Actions\StoreOptimizedImage; use App\Enums\VendorTier; @endphp

<x-layouts.app :title="__('pages.compare.banding_vendor')">
    <x-site.header />

    {{-- The same paper and florals as the rest of the public site. The comparison
         itself is a grid, not a table: on a phone every measure becomes a block
         with the vendors side by side under it, which a scrolling table cannot. --}}
    <main class="relative overflow-hidden bg-ivory">
        <x-site.ornament name="corner-peony" class="absolute -top-16 -right-20 size-[18rem] rotate-90 opacity-40 sm:size-[26rem]" color="var(--color-brand-200)" color2="var(--color-gold-300)" />

        <div class="relative mx-auto max-w-6xl px-4 pt-24 pb-24 sm:px-6 lg:px-10 lg:pt-28">
            <header class="flex flex-col gap-1">
                <p class="font-script text-3xl text-brand-600 sm:text-4xl">{{ __('pages.compare.sebelum_anda_pilih') }}</p>
                <h1 class="font-display text-3xl font-semibold tracking-tight">{{ __('pages.compare.banding_vendor_2') }}</h1>
                <x-site.ornament name="divider-floral" class="mt-3 h-5 w-36" color="var(--color-gold-500)" color2="var(--color-gold-300)" />
                <p class="mt-3 text-sm text-ink-muted">
                    @if ($sharedCategory)
                        <x-category-icon class="inline-block size-5 shrink-0 align-[-0.3em]" :category="$sharedCategory" /> {{ $sharedCategory->name }} · {{ $vendors->count() }} vendor dibandingkan
                    @else
                        {{ __('pages.reviews.pilih_sehingga_vendor', ['count' => \App\Http\Controllers\VendorComparisonController::MAX_VENDORS]) }}
                    @endif
                </p>
            </header>

            @if ($vendors->isEmpty())
                <div class="relative mt-10 overflow-hidden rounded-3xl border border-gold-300 bg-surface-raised px-6 py-16 text-center">
                    <span class="pointer-events-none absolute inset-3 rounded-[1.25rem] border border-gold-300/70" aria-hidden="true"></span>
                    <x-site.ornament name="corner-rose" class="absolute -top-8 -left-8 size-36 opacity-50" color="var(--color-brand-300)" color2="var(--color-brand-100)" />
                    <div class="relative flex flex-col items-center gap-3">
                        <x-nav-icon name="layers" class="size-8 text-gold-600" />
                        <h2 class="font-display text-xl font-semibold">{{ __('pages.compare.belum_ada_vendor_dipilih') }}</h2>
                        <p class="max-w-md text-sm text-ink-muted">{{ __('pages.compare.di_marketplace_tandakan_kotak_banding') }}</p>
                        <a href="{{ route('vendors.index') }}" class="rounded-full bg-brand-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">{{ __('pages.compare.cari_vendor') }}</a>
                    </div>
                </div>
            @else
                @php
                    $count = $vendors->count();
                    // Two vendors sit side by side on a phone; three or four share
                    // the width in smaller type rather than running off the screen.
                    // Written out in full so Tailwind can see each class it must build.
                    $vendorColumns = match ($count) { 1 => 'grid-cols-1', 2 => 'grid-cols-2', 3 => 'grid-cols-3', default => 'grid-cols-4' };
                    $wide = match ($count) {
                        1 => 'md:grid-cols-[10rem_repeat(1,minmax(0,1fr))]',
                        2 => 'md:grid-cols-[10rem_repeat(2,minmax(0,1fr))]',
                        3 => 'md:grid-cols-[10rem_repeat(3,minmax(0,1fr))]',
                        default => 'md:grid-cols-[10rem_repeat(4,minmax(0,1fr))]',
                    };
                    $cellText = $count > 2 ? 'text-xs' : 'text-sm';
                @endphp

                {{-- The vendors, each as the front of their own card: cover photo,
                     logo, name. Sticky on a phone so the columns stay labelled while
                     the measures scroll under them. --}}
                <div class="sticky top-[4.5rem] z-20 -mx-4 mt-8 border-b border-gold-300/60 bg-ivory/95 px-4 pt-3 pb-2 backdrop-blur sm:mx-0 sm:px-0 md:static md:border-0 md:bg-transparent md:p-0 md:backdrop-blur-none">
                    <div class="grid gap-2 sm:gap-4 {{ $vendorColumns }} {{ $wide }}">
                        <div class="hidden md:block"></div>
                        @foreach ($vendors as $vendor)
                            <a href="{{ route('vendors.show', $vendor) }}" class="group flex min-w-0 items-center gap-2 md:flex-col md:items-stretch md:gap-3">
                                <span class="relative hidden aspect-[4/3] overflow-hidden rounded-2xl bg-linear-to-br md:block {{ $vendor->cover_tone }}">
                                    @if ($vendor->cover_image)
                                        <img src="{{ StoreOptimizedImage::thumbnailUrl($vendor->cover_image) }}" alt="" loading="lazy" decoding="async" class="absolute inset-0 size-full object-cover transition duration-300 group-hover:scale-[1.03]">
                                    @endif
                                    <span class="absolute inset-x-0 bottom-0 h-1/2 bg-linear-to-t from-black/40 to-transparent"></span>
                                    @if ($vendor->tier === VendorTier::Recommended)
                                        <span class="absolute top-2 left-2 rounded-full bg-gold-300 px-2 py-0.5 text-[11px] font-semibold text-brand-900">{{ __('marketplace.card.recommended') }}</span>
                                    @endif
                                    <span class="absolute bottom-2 left-2 rounded-full bg-white p-0.5 shadow-sm"><x-vendor-avatar :vendor="$vendor" class="size-10 text-base" /></span>
                                </span>
                                <x-vendor-avatar :vendor="$vendor" class="size-9 text-sm md:hidden" />
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold group-hover:text-brand-700">{{ $vendor->name }}</span>
                                    <span class="block truncate text-[11px] text-ink-muted">{{ $vendor->category->name }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <dl class="mt-4 divide-y divide-gold-300/60 rounded-3xl border border-gold-300/60 bg-surface-raised">
                    @foreach ($rows as $row)
                        <div class="grid gap-x-2 gap-y-1 p-3 sm:gap-x-4 sm:p-4 {{ $wide }} md:items-center">
                            <dt class="text-[11px] font-semibold tracking-wide text-ink-muted uppercase md:text-xs md:normal-case md:tracking-normal">{{ $row['label'] }}</dt>
                            <div class="grid gap-2 {{ $vendorColumns }} md:contents">
                                @foreach ($row['values'] as $index => $value)
                                    <dd @class([
                                        'min-w-0 rounded-xl px-2 py-1.5 break-words md:px-3 md:py-2', $cellText,
                                        'bg-emerald-50 font-semibold text-emerald-900' => $row['best'] === $index,
                                    ])>
                                        {{ $value }}
                                        @if ($row['best'] === $index)
                                            <span class="ml-1 text-[11px] font-normal text-emerald-700">terbaik</span>
                                        @endif
                                    </dd>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    {{-- Packages --}}
                    <div class="grid gap-x-2 gap-y-1 p-3 sm:gap-x-4 sm:p-4 {{ $wide }}">
                        <dt class="text-[11px] font-semibold tracking-wide text-ink-muted uppercase md:text-xs md:normal-case md:tracking-normal">{{ __('pages.compare.pakej') }}</dt>
                        <div class="grid gap-2 {{ $vendorColumns }} md:contents">
                            @foreach ($vendors as $vendor)
                                <dd class="min-w-0 px-2 py-1.5 break-words md:px-3 md:py-2 {{ $cellText }}">
                                    @forelse ($vendor->packages as $package)
                                        <div class="mb-2 last:mb-0">
                                            <p class="font-medium">{{ $package->name }}</p>
                                            <p class="text-[11px] text-ink-muted">RM{{ number_format((float) $package->price) }} · {{ $package->duration }}</p>
                                        </div>
                                    @empty
                                        <span class="text-ink-muted">{{ __('pages.compare.tiada_pakej') }}</span>
                                    @endforelse
                                </dd>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid gap-x-2 gap-y-1 p-3 sm:gap-x-4 sm:p-4 {{ $wide }}">
                        <dt class="hidden md:block"></dt>
                        <div class="grid gap-2 {{ $vendorColumns }} md:contents">
                            @foreach ($vendors as $vendor)
                                <dd class="min-w-0 px-2 py-1.5 md:px-3 md:py-2">
                                    <a href="{{ route('vendors.show', $vendor) }}#hubungi" class="inline-flex w-full items-center justify-center rounded-full bg-brand-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-brand-700 md:w-auto">{{ __('pages.compare.hubungi') }}</a>
                                </dd>
                            @endforeach
                        </div>
                    </div>
                </dl>

                <div class="mt-6 flex flex-wrap gap-2">
                    <a href="{{ route('vendors.index', $sharedCategory ? ['category' => $sharedCategory->slug] : []) }}" class="rounded-full border border-gold-400 bg-surface-raised px-5 py-2.5 text-sm font-medium transition hover:border-gold-500 hover:text-brand-700">{{ __('pages.compare.tambah_vendor_lain') }}</a>
                    <a href="{{ route('vendors.compare') }}" class="rounded-full px-5 py-2.5 text-sm font-medium text-ink-muted transition hover:bg-surface-muted">{{ __('pages.compare.kosongkan') }}</a>
                </div>
            @endif
        </div>
    </main>

    <x-site.footer />
    <x-site.mobile-nav />
</x-layouts.app>
