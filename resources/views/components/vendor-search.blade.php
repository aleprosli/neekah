@props(['filterDialog' => null, 'categories', 'states', 'stateOptions'])

@php
    // Budget left the search bar: a couple knows what they are looking for
    // long before they know what it should cost, and the price filter is still
    // in the toolbar under the results for when they do.
    $current = [
        'q' => request('q'),
        'category' => request('category'),
        'state' => request('state'),
    ];
    $activeCategory = $categories->firstWhere('slug', $current['category']);
    $summary = collect([$current['q'] ? '"'.$current['q'].'"' : null, $activeCategory?->name, $current['state']])->filter();
@endphp

<section class="relative overflow-hidden bg-[radial-gradient(ellipse_at_top_left,var(--color-brand-100),transparent_60%),radial-gradient(ellipse_at_bottom_right,var(--color-gold-300),transparent_55%)] pt-24 pb-8 md:pt-28 md:pb-12">
    <div class="mx-auto flex max-w-[1760px] flex-col gap-5 px-4 sm:px-6 md:items-center md:gap-7 md:text-center lg:px-10">
        <div class="max-w-2xl">
            <h1 class="font-display text-2xl font-semibold tracking-tight sm:text-3xl md:text-4xl">Cari vendor majlis anda</h1>
            <p class="mt-1 text-sm text-ink-muted sm:text-base md:mt-2">Vendor disahkan, harga jelas, booking dan bayaran selamat melalui Neekah.</p>
        </div>

        {{-- Desktop search bar --}}
        <form method="GET" action="{{ route('vendors.index') }}" class="hidden w-full max-w-3xl items-stretch divide-x divide-line rounded-2xl border border-line bg-surface-raised p-1.5 shadow-lg shadow-brand-900/5 md:flex">
            {{-- Whatever the toolbar under the results is already filtering by
                 rides along, so a search here narrows rather than resets. --}}
            @foreach (['min_price', 'max_price', 'min_rating', 'tier', 'sort'] as $carried)
                @if (filled(request($carried)))
                    <input type="hidden" name="{{ $carried }}" value="{{ request($carried) }}">
                @endif
            @endforeach
            <label class="flex flex-[1.8] cursor-text flex-col gap-0.5 px-5 py-2">
                <span class="text-[11px] font-semibold tracking-wide text-ink-muted uppercase">Cari</span>
                <input
                    type="search"
                    name="q"
                    value="{{ $current['q'] }}"
                    placeholder="Nama vendor atau pakej"
                    class="w-full bg-transparent text-sm font-medium placeholder:font-normal placeholder:text-ink-muted focus:outline-none"
                >
            </label>
            <label class="flex flex-1 cursor-pointer flex-col gap-0.5 px-5 py-2">
                <span class="text-[11px] font-semibold tracking-wide text-ink-muted uppercase">Kategori</span>
                <select name="category" class="nk-select w-full bg-transparent pr-6 text-sm font-medium focus:outline-none">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->slug }}" @selected($current['category'] === $category->slug)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>
            {{-- A native select can only hold text, so the flag list is a Vue
                 island over a real select: no JavaScript still gets the field. --}}
            <div
                class="flex flex-1 flex-col justify-center px-5 py-2"
                data-vue="ui-flag-select"
                data-props="@vueProps([
                    'name' => 'state',
                    'label' => 'Negeri',
                    'options' => $stateOptions,
                    'modelValue' => $current['state'] ?? '',
                    'placeholder' => 'Mana-mana negeri',
                    'variant' => 'bare',
                ])"
            >
                <label class="flex cursor-pointer flex-col gap-0.5">
                    <span class="text-[11px] font-semibold tracking-wide text-ink-muted uppercase">Negeri</span>
                    <select name="state" class="nk-select w-full bg-transparent pr-6 text-sm font-medium focus:outline-none">
                        <option value="">Mana-mana negeri</option>
                        @foreach ($states as $state)
                            <option value="{{ $state }}" @selected($current['state'] === $state)>{{ $state }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <div class="flex items-center pl-1.5">
                <button type="submit" class="flex h-full items-center gap-2 rounded-xl bg-brand-600 px-5 text-sm font-semibold text-white transition hover:bg-brand-700">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    Cari
                </button>
            </div>
        </form>

        {{-- Mobile search pill --}}
        <div class="flex w-full items-center gap-2 md:hidden">
            @php
                $pillClasses = 'flex flex-1 items-center gap-3 rounded-2xl border border-line bg-surface-raised px-4 py-3 text-left shadow-lg shadow-brand-900/5';
                $pillContent = '<span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></span><span class="min-w-0"><span class="block text-sm font-semibold">Cari vendor</span><span class="block truncate text-xs text-ink-muted">'.e($summary->isNotEmpty() ? $summary->implode(' · ') : 'Nama, pakej, kategori atau negeri').'</span></span>';
            @endphp
            @if ($filterDialog)
                <button type="button" data-dialog-open="{{ $filterDialog }}" class="{{ $pillClasses }}">{!! $pillContent !!}</button>
                <button type="button" data-dialog-open="{{ $filterDialog }}" class="flex size-12 shrink-0 items-center justify-center rounded-2xl border border-line bg-surface-raised shadow-lg shadow-brand-900/5" aria-label="Filter">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                </button>
            @else
                <a href="{{ route('vendors.index') }}" class="{{ $pillClasses }}">{!! $pillContent !!}</a>
            @endif
        </div>
    </div>
</section>
