<x-layouts.app :title="$activeCategory?->name ?? 'Cari Vendor'">
    <x-site.header />
    <x-vendor-search filter-dialog="filters" :categories="$categories" :states="$states" />

    <main class="mx-auto max-w-[1760px] px-4 pb-24 sm:px-6 md:pb-10 lg:px-10">
        {{-- Category tiles --}}
        <div class="border-b border-line py-5">
            <div class="no-scrollbar -mx-4 flex overflow-x-auto px-4 sm:mx-0 sm:px-0 md:justify-center">
                <ul class="flex w-max gap-4 sm:gap-6">
                    <li>
                        <a href="{{ route('vendors.index', array_filter(['q' => $filters['q'], 'state' => $filters['state']])) }}" @class(['group flex w-16 flex-col items-center gap-2 text-center text-[11px] font-medium whitespace-nowrap transition', 'text-brand-700' => ! $filters['category'], 'text-ink-muted hover:text-ink' => $filters['category']])>
                            <span @class(['flex size-12 items-center justify-center rounded-full text-2xl transition', 'bg-brand-600 shadow-md shadow-brand-600/30' => ! $filters['category'], 'bg-surface-muted group-hover:bg-brand-50' => $filters['category']])>🎉</span>
                            Semua
                        </a>
                    </li>
                    @foreach ($categories as $category)
                        <li>
                            <a href="{{ route('vendors.index', array_filter(['category' => $category->slug, 'q' => $filters['q'], 'state' => $filters['state']])) }}" @class(['group flex w-16 flex-col items-center gap-2 text-center text-[11px] font-medium whitespace-nowrap transition', 'text-brand-700' => $filters['category'] === $category->slug, 'text-ink-muted hover:text-ink' => $filters['category'] !== $category->slug])>
                                <span @class(['flex size-12 items-center justify-center rounded-full text-2xl transition', 'bg-brand-600 shadow-md shadow-brand-600/30' => $filters['category'] === $category->slug, 'bg-surface-muted group-hover:bg-brand-50' => $filters['category'] !== $category->slug])>{{ $category->icon }}</span>
                                {{ $category->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

        </div>

        {{-- Filter toolbar --}}
        @php
            $priceLabel = match (true) {
                $filters['min_price'] !== null && $filters['max_price'] !== null => 'RM'.number_format($filters['min_price']).' – RM'.number_format($filters['max_price']),
                $filters['max_price'] !== null => 'Bawah RM'.number_format($filters['max_price']),
                $filters['min_price'] !== null => 'Dari RM'.number_format($filters['min_price']),
                default => null,
            };
            $fieldClasses = 'w-full rounded-xl border border-line bg-surface px-3 py-2 text-sm focus:border-brand-400 focus:outline-none';
            $chipOption = 'cursor-pointer rounded-full border border-line px-3 py-1.5 text-sm transition has-checked:border-brand-600 has-checked:bg-brand-600 has-checked:text-white hover:border-brand-400';
        @endphp
        <div class="flex flex-col gap-4 py-5 md:flex-row md:items-center md:justify-between">
            <div class="hidden flex-wrap items-center gap-2 md:flex">
                <x-filter-popover label="Negeri" :active="$filters['state']">
                    <form method="GET" action="{{ route('vendors.index') }}" class="flex flex-col gap-3">
                        <x-filter-hidden :filters="$filters" except="state" />
                        <select name="state" class="{{ $fieldClasses }}" onchange="this.form.requestSubmit()">
                            <option value="">Mana-mana negeri</option>
                            @foreach ($states as $state)
                                <option value="{{ $state }}" @selected($filters['state'] === $state)>{{ $state }}</option>
                            @endforeach
                        </select>
                    </form>
                </x-filter-popover>

                <x-filter-popover label="Harga" :active="$priceLabel" width="w-80">
                    <form method="GET" action="{{ route('vendors.index') }}" class="flex flex-col gap-3">
                        <x-filter-hidden :filters="$filters" :except="['min_price', 'max_price']" />
                        <p class="text-xs text-ink-muted">Harga pakej terendah vendor, dalam RM.</p>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex flex-col gap-1 rounded-xl border border-line px-3 py-2 focus-within:border-brand-400">
                                <span class="text-[11px] text-ink-muted">Minimum</span>
                                <input type="number" name="min_price" min="0" step="50" value="{{ $filters['min_price'] }}" placeholder="0" class="bg-transparent text-sm focus:outline-none">
                            </label>
                            <label class="flex flex-col gap-1 rounded-xl border border-line px-3 py-2 focus-within:border-brand-400">
                                <span class="text-[11px] text-ink-muted">Maksimum</span>
                                <input type="number" name="max_price" min="0" step="50" value="{{ $filters['max_price'] }}" placeholder="Tiada had" class="bg-transparent text-sm focus:outline-none">
                            </label>
                        </div>
                        <div class="flex items-center justify-between">
                            @if ($priceLabel)
                                <a href="{{ route('vendors.index', array_filter(array_merge($filters, ['min_price' => null, 'max_price' => null, 'sort' => $filters['sort'] === 'recommended' ? null : $filters['sort']]), fn ($value) => $value !== null)) }}" class="text-xs font-medium underline underline-offset-4">Buang</a>
                            @else
                                <span></span>
                            @endif
                            <button type="submit" class="rounded-full bg-brand-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-brand-700">Guna</button>
                        </div>
                    </form>
                </x-filter-popover>

                <x-filter-popover label="Rating" :active="$filters['min_rating'] !== null ? number_format($filters['min_rating'], 1).'+' : null" width="w-64">
                    <form method="GET" action="{{ route('vendors.index') }}" class="flex flex-wrap gap-2">
                        <x-filter-hidden :filters="$filters" except="min_rating" />
                        @foreach (['' => 'Semua', '4' => '4.0+', '4.5' => '4.5+', '4.8' => '4.8+'] as $value => $label)
                            <label class="{{ $chipOption }}">
                                <input type="radio" name="min_rating" value="{{ $value }}" class="sr-only" onchange="this.form.requestSubmit()" @checked((string) $filters['min_rating'] === (string) $value)>
                                {{ $label }}
                            </label>
                        @endforeach
                    </form>
                </x-filter-popover>

                <x-filter-popover label="Tahap vendor" :active="$filters['tier'] ? \App\Enums\VendorTier::from($filters['tier'])->label() : null" width="w-80">
                    <form method="GET" action="{{ route('vendors.index') }}" class="flex flex-wrap gap-2">
                        <x-filter-hidden :filters="$filters" except="tier" />
                        <label class="{{ $chipOption }}">
                            <input type="radio" name="tier" value="" class="sr-only" onchange="this.form.requestSubmit()" @checked(! $filters['tier'])>
                            Semua
                        </label>
                        @foreach ($tiers as $tier)
                            <label class="{{ $chipOption }}">
                                <input type="radio" name="tier" value="{{ $tier->value }}" class="sr-only" onchange="this.form.requestSubmit()" @checked($filters['tier'] === $tier->value)>
                                @if ($tier === \App\Enums\VendorTier::Recommended)🏆 @endif{{ $tier->label() }}
                            </label>
                        @endforeach
                    </form>
                </x-filter-popover>

                <button type="button" data-dialog-open="filters" class="flex items-center gap-2 rounded-full border border-dashed border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400 hover:text-brand-700">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                    Semua filter
                    @if ($activeFilterCount)
                        <span class="rounded-full bg-brand-600 px-1.5 text-xs font-semibold text-white">{{ $activeFilterCount }}</span>
                    @endif
                </button>

                @if ($activeFilterCount || $filters['q'] || $filters['category'])
                    <a href="{{ route('vendors.index') }}" class="px-2 text-sm font-medium underline underline-offset-4">Buang semua</a>
                @endif
            </div>

            <div class="flex items-center justify-between gap-3 text-sm md:justify-end">
                <p class="text-ink-muted">
                    <span class="font-display text-lg font-semibold text-ink">{{ $vendors->total() }} vendor</span>
                    @if ($activeCategory) · {{ $activeCategory->name }} @endif
                    @if ($filters['q']) · "{{ $filters['q'] }}" @endif
                    <span class="ml-1 rounded-full bg-gold-300/50 px-2 py-0.5 text-[11px] font-medium text-brand-900 dark:bg-gold-500/20 dark:text-gold-300">Data demo</span>
                </p>

                <x-filter-popover :label="'Susun: '.$sorts[$filters['sort']]" align="right" width="w-60">
                    <form method="GET" action="{{ route('vendors.index') }}" class="flex flex-col gap-1">
                        <x-filter-hidden :filters="$filters" except="sort" />
                        @foreach ($sorts as $value => $label)
                            <label class="flex cursor-pointer items-center gap-2 rounded-xl px-3 py-2 text-sm transition has-checked:bg-brand-50 has-checked:font-semibold has-checked:text-brand-700 hover:bg-surface-muted dark:has-checked:bg-brand-900/40 dark:has-checked:text-brand-200">
                                <input type="radio" name="sort" value="{{ $value }}" class="accent-brand-600" onchange="this.form.requestSubmit()" @checked($filters['sort'] === $value)>
                                {{ $label }}
                            </label>
                        @endforeach
                    </form>
                </x-filter-popover>
            </div>
        </div>

        @if ($vendors->isEmpty())
            <div class="flex flex-col items-center gap-3 py-24 text-center">
                <span class="text-4xl">🔍</span>
                <h1 class="text-lg font-semibold">Tiada vendor sepadan</h1>
                <p class="max-w-sm text-sm text-ink-muted">Cuba longgarkan bajet atau rating, atau pilih negeri lain.</p>
                <a href="{{ route('vendors.index') }}" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Buang semua filter</a>
            </div>
        @else
            <h1 class="sr-only">{{ $activeCategory?->name ?? 'Semua vendor' }}</h1>
            <ul class="grid grid-cols-2 gap-x-3 gap-y-8 sm:gap-x-6 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6">
                @foreach ($vendors as $vendor)
                    <li><x-vendor-card :vendor="$vendor" /></li>
                @endforeach
            </ul>

            @if ($vendors->hasPages())
                <nav class="mt-12 flex items-center justify-center gap-1 text-sm" aria-label="Halaman">
                    @if ($vendors->onFirstPage())
                        <span class="flex size-9 items-center justify-center text-ink-muted/50">‹</span>
                    @else
                        <a href="{{ $vendors->previousPageUrl() }}" class="flex size-9 items-center justify-center rounded-full hover:bg-surface-muted" aria-label="Sebelum">‹</a>
                    @endif
                    @foreach ($vendors->getUrlRange(1, $vendors->lastPage()) as $page => $url)
                        <a href="{{ $url }}" @class(['flex size-9 items-center justify-center rounded-full font-medium', 'bg-brand-600 text-white' => $page === $vendors->currentPage(), 'hover:bg-surface-muted' => $page !== $vendors->currentPage()])>{{ $page }}</a>
                    @endforeach
                    @if ($vendors->hasMorePages())
                        <a href="{{ $vendors->nextPageUrl() }}" class="flex size-9 items-center justify-center rounded-full hover:bg-surface-muted" aria-label="Seterusnya">›</a>
                    @else
                        <span class="flex size-9 items-center justify-center text-ink-muted/50">›</span>
                    @endif
                </nav>
            @endif
        @endif
    </main>

    {{-- Filter dialog --}}
    <dialog id="filters" class="m-auto w-full max-w-lg rounded-3xl bg-surface-raised p-0 text-ink shadow-2xl backdrop:bg-black/40 max-sm:mt-auto max-sm:mb-0 max-sm:max-h-[92dvh] max-sm:rounded-b-none">
        <form method="GET" action="{{ route('vendors.index') }}" class="flex max-h-[92dvh] flex-col">
            @if ($filters['category'])
                <input type="hidden" name="category" value="{{ $filters['category'] }}">
            @endif

            <div class="grid grid-cols-[2.5rem_1fr_2.5rem] items-center border-b border-line px-4 py-4">
                <button type="button" data-dialog-close class="flex size-8 items-center justify-center rounded-full hover:bg-surface-muted" aria-label="Tutup">✕</button>
                <h2 class="text-center font-display text-lg font-semibold">Filter</h2>
            </div>

            <div class="flex flex-col gap-7 overflow-y-auto px-6 py-6">
                <div class="flex flex-col gap-2">
                    <label for="q" class="font-semibold">Cari</label>
                    <input id="q" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Nama vendor, bandar…" class="rounded-xl border border-line bg-surface px-4 py-3 text-sm focus:border-brand-400 focus:outline-none">
                </div>

                <div class="flex flex-col gap-2">
                    <label for="state" class="font-semibold">Negeri</label>
                    <select id="state" name="state" class="rounded-xl border border-line bg-surface px-4 py-3 text-sm focus:border-brand-400 focus:outline-none">
                        <option value="">Mana-mana negeri</option>
                        @foreach ($states as $state)
                            <option value="{{ $state }}" @selected($filters['state'] === $state)>{{ $state }}</option>
                        @endforeach
                    </select>
                </div>

                <fieldset class="flex flex-col gap-3">
                    <legend class="font-semibold">Harga bermula</legend>
                    <p class="text-sm text-ink-muted">Harga pakej terendah vendor, dalam RM.</p>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex flex-col gap-1 rounded-xl border border-line px-4 py-2 focus-within:border-brand-400">
                            <span class="text-xs text-ink-muted">Minimum</span>
                            <input type="number" name="min_price" min="0" step="50" value="{{ $filters['min_price'] }}" placeholder="0" class="bg-transparent text-sm focus:outline-none">
                        </label>
                        <label class="flex flex-col gap-1 rounded-xl border border-line px-4 py-2 focus-within:border-brand-400">
                            <span class="text-xs text-ink-muted">Maksimum</span>
                            <input type="number" name="max_price" min="0" step="50" value="{{ $filters['max_price'] }}" placeholder="Tiada had" class="bg-transparent text-sm focus:outline-none">
                        </label>
                    </div>
                </fieldset>

                <fieldset class="flex flex-col gap-3">
                    <legend class="font-semibold">Rating minimum</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['' => 'Semua', '4' => '4.0+', '4.5' => '4.5+', '4.8' => '4.8+'] as $value => $label)
                            <label class="cursor-pointer rounded-full border border-line px-4 py-2 text-sm transition has-checked:border-brand-600 has-checked:bg-brand-600 has-checked:text-white hover:border-brand-400">
                                <input type="radio" name="min_rating" value="{{ $value }}" class="sr-only" @checked((string) $filters['min_rating'] === (string) $value)>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset class="flex flex-col gap-3">
                    <legend class="font-semibold">Tahap vendor</legend>
                    <div class="flex flex-wrap gap-2">
                        <label class="cursor-pointer rounded-full border border-line px-4 py-2 text-sm transition has-checked:border-brand-600 has-checked:bg-brand-600 has-checked:text-white hover:border-brand-400">
                            <input type="radio" name="tier" value="" class="sr-only" @checked(! $filters['tier'])>
                            Semua
                        </label>
                        @foreach ($tiers as $tier)
                            <label class="cursor-pointer rounded-full border border-line px-4 py-2 text-sm transition has-checked:border-brand-600 has-checked:bg-brand-600 has-checked:text-white hover:border-brand-400">
                                <input type="radio" name="tier" value="{{ $tier->value }}" class="sr-only" @checked($filters['tier'] === $tier->value)>
                                @if ($tier === \App\Enums\VendorTier::Recommended)🏆 @endif{{ $tier->label() }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="flex flex-col gap-2">
                    <label for="sort" class="font-semibold">Susunan</label>
                    <select id="sort" name="sort" class="rounded-xl border border-line bg-surface px-4 py-3 text-sm focus:border-brand-400 focus:outline-none">
                        @foreach ($sorts as $value => $label)
                            <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-between border-t border-line px-6 py-4">
                <a href="{{ route('vendors.index') }}" class="text-sm font-medium underline underline-offset-4">Buang semua</a>
                <button type="submit" class="rounded-full bg-brand-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">Tunjuk vendor</button>
            </div>
        </form>
    </dialog>

    <x-site.footer />
    <x-site.mobile-nav />
</x-layouts.app>
