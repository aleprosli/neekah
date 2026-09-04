@props(['filterDialog' => null, 'categories', 'states'])

@php
    $current = [
        'category' => request('category'),
        'state' => request('state'),
        'max_price' => request('max_price'),
    ];
    $budgets = [1000 => 'Bawah RM1,000', 3000 => 'Bawah RM3,000', 5000 => 'Bawah RM5,000', 10000 => 'Bawah RM10,000'];
    $activeCategory = $categories->firstWhere('slug', $current['category']);
    $summary = collect([$activeCategory?->name, $current['state'], $budgets[(int) $current['max_price']] ?? null])->filter();
@endphp

<section class="relative overflow-hidden bg-[radial-gradient(ellipse_at_top_left,var(--color-brand-100),transparent_60%),radial-gradient(ellipse_at_bottom_right,var(--color-gold-300),transparent_55%)] pt-24 pb-8 dark:bg-[radial-gradient(ellipse_at_top_left,var(--color-brand-900),transparent_60%)] md:pt-28 md:pb-12">
    <div class="mx-auto flex max-w-[1760px] flex-col gap-5 px-4 sm:px-6 md:items-center md:gap-7 md:text-center lg:px-10">
        <div class="max-w-2xl">
            <h1 class="font-display text-2xl font-semibold tracking-tight sm:text-3xl md:text-4xl">Cari vendor majlis anda</h1>
            <p class="mt-1 text-sm text-ink-muted sm:text-base md:mt-2">Vendor disahkan, harga jelas, booking dan bayaran selamat melalui Neekah.</p>
        </div>

        {{-- Desktop search bar --}}
        <form method="GET" action="{{ route('vendors.index') }}" class="hidden w-full max-w-3xl items-stretch divide-x divide-line rounded-2xl border border-line bg-surface-raised p-1.5 shadow-lg shadow-brand-900/5 md:flex">
            <label class="flex flex-1 cursor-pointer flex-col gap-0.5 px-5 py-2">
                <span class="text-[11px] font-semibold tracking-wide text-ink-muted uppercase">Kategori</span>
                <select name="category" class="cursor-pointer appearance-none bg-transparent text-sm font-medium focus:outline-none">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->slug }}" @selected($current['category'] === $category->slug)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="flex flex-1 cursor-pointer flex-col gap-0.5 px-5 py-2">
                <span class="text-[11px] font-semibold tracking-wide text-ink-muted uppercase">Negeri</span>
                <select name="state" class="cursor-pointer appearance-none bg-transparent text-sm font-medium focus:outline-none">
                    <option value="">Mana-mana negeri</option>
                    @foreach ($states as $state)
                        <option value="{{ $state }}" @selected($current['state'] === $state)>{{ $state }}</option>
                    @endforeach
                </select>
            </label>
            <label class="flex flex-1 cursor-pointer flex-col gap-0.5 px-5 py-2">
                <span class="text-[11px] font-semibold tracking-wide text-ink-muted uppercase">Bajet</span>
                <select name="max_price" class="cursor-pointer appearance-none bg-transparent text-sm font-medium focus:outline-none">
                    <option value="">Mana-mana bajet</option>
                    @foreach ($budgets as $value => $label)
                        <option value="{{ $value }}" @selected((int) $current['max_price'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
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
                $pillContent = '<span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-600 text-white"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></span><span class="min-w-0"><span class="block text-sm font-semibold">Cari vendor</span><span class="block truncate text-xs text-ink-muted">'.e($summary->isNotEmpty() ? $summary->implode(' · ') : 'Kategori · Negeri · Bajet').'</span></span>';
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
