<x-layouts.app title="Semua Urusan Majlis, Satu Platform">
    <x-site.header />

    <main>
        {{-- Hero --}}
        <section class="relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_top_left,var(--color-brand-100),transparent_55%),radial-gradient(ellipse_at_bottom_right,var(--color-gold-300),transparent_50%)] opacity-70"></div>

            <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 pt-28 pb-16 sm:px-6 lg:grid-cols-2 lg:px-8 lg:pt-36 lg:pb-24">
                <div class="flex flex-col gap-6">
                    <span class="inline-flex w-fit items-center gap-2 rounded-full border border-brand-200 bg-brand-50 px-3 py-1 text-xs font-semibold tracking-wide text-brand-700 uppercase">
                        <span class="size-1.5 rounded-full bg-brand-500"></span>
                        Rangkaian vendor kahwin Malaysia
                    </span>

                    <h1 class="font-display text-4xl leading-tight font-semibold tracking-tight text-balance sm:text-5xl lg:text-6xl">
                        Cari vendor kahwin, <span class="text-brand-600">terus berurusan dengan mereka.</span>
                    </h1>

                    <p class="max-w-xl text-lg text-ink-muted text-pretty">
                        Neekah bantu anda cari vendor yang sesuai ikut kategori, lokasi dan bajet, kemudian hubungi mereka terus. Sambil itu, rancang majlis anda dengan checklist, bajet dan kad jemputan digital, semuanya percuma.
                    </p>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('vendors.index') }}" class="inline-flex items-center justify-center rounded-full bg-brand-600 px-6 py-3 text-base font-semibold text-white shadow-lg shadow-brand-600/20 transition hover:bg-brand-700">
                            Cari Vendor Sekarang
                        </a>
                        <a href="#vendor" class="inline-flex items-center justify-center rounded-full border border-line bg-surface-raised px-6 py-3 text-base font-semibold transition hover:border-brand-300 hover:text-brand-700">
                            Sertai Sebagai Vendor
                        </a>
                    </div>

                    <dl class="grid grid-cols-3 gap-4 border-t border-line pt-6 text-sm">
                        <div>
                            <dt class="text-ink-muted">Kategori vendor</dt>
                            <dd class="font-display text-2xl font-semibold">{{ $categories->count() }}</dd>
                        </div>
                        <div>
                            <dt class="text-ink-muted">Yuran platform</dt>
                            <dd class="font-display text-2xl font-semibold">Percuma</dd>
                        </div>
                        <div>
                            <dt class="text-ink-muted">Orang tengah</dt>
                            <dd class="font-display text-2xl font-semibold">Tiada</dd>
                        </div>
                    </dl>
                </div>

                {{-- Mock wedding dashboard --}}
                <div class="relative">
                    <div class="absolute -top-14 right-8 z-10 hidden rounded-2xl border border-line bg-surface-raised p-4 shadow-xl lg:block">
                        <p class="text-xs font-medium text-ink-muted">Kad jemputan digital</p>
                        <p class="mt-1 flex items-center gap-2 text-sm font-semibold">
                            <span class="size-2 rounded-full bg-emerald-500"></span>
                            186 tetamu sahkan hadir
                        </p>
                    </div>

                    <div class="rounded-3xl border border-line bg-surface-raised p-6 shadow-2xl shadow-brand-900/10 sm:p-8">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold tracking-wide text-brand-600 uppercase">Wedding Dashboard</p>
                                <h2 class="mt-1 font-display text-2xl font-semibold">Aina & Hakim</h2>
                                <p class="mt-1 text-sm text-ink-muted">20 Disember 2026 · Alor Setar</p>
                            </div>
                            <div class="rounded-full bg-gold-300/60 px-3 py-1 text-xs font-semibold text-brand-900">82% siap</div>
                        </div>

                        <dl class="mt-6 grid grid-cols-2 gap-3 text-sm">
                            <div class="rounded-2xl bg-surface-muted p-4">
                                <dt class="text-ink-muted">Bajet</dt>
                                <dd class="mt-1 font-semibold">RM28,200 <span class="font-normal text-ink-muted">/ RM30,000</span></dd>
                            </div>
                            <div class="rounded-2xl bg-surface-muted p-4">
                                <dt class="text-ink-muted">Vendor</dt>
                                <dd class="mt-1 font-semibold">8 <span class="font-normal text-ink-muted">/ 10 sudah deal</span></dd>
                            </div>
                            <div class="rounded-2xl bg-surface-muted p-4">
                                <dt class="text-ink-muted">Checklist</dt>
                                <dd class="mt-1 font-semibold">81 <span class="font-normal text-ink-muted">/ 99 selesai</span></dd>
                            </div>
                            <div class="rounded-2xl bg-surface-muted p-4">
                                <dt class="text-ink-muted">Baki bajet</dt>
                                <dd class="mt-1 font-semibold text-emerald-600">RM1,800</dd>
                            </div>
                        </dl>

                        <div class="mt-6">
                            <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">Akan datang</p>
                            <ul class="mt-3 flex flex-col gap-2 text-sm">
                                <li class="flex items-center justify-between rounded-xl border border-line px-3 py-2">
                                    <span><x-category-icon class="inline-block size-5 shrink-0 align-[-0.3em]" slug="photography" fallback="📸" /> Photographer</span>
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">Sudah deal</span>
                                </li>
                                <li class="flex items-center justify-between rounded-xl border border-line px-3 py-2">
                                    <span><x-category-icon class="inline-block size-5 shrink-0 align-[-0.3em]" slug="catering" fallback="🍽️" /> Catering</span>
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">Sudah deal</span>
                                </li>
                                <li class="flex items-center justify-between rounded-xl border border-line px-3 py-2">
                                    <span><x-category-icon class="inline-block size-5 shrink-0 align-[-0.3em]" slug="makeup" fallback="💄" /> Makeup</span>
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">Sedang berbincang</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Flow strip --}}
        <section id="cara" class="border-y border-line bg-surface-raised">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <ol class="grid grid-cols-2 gap-6 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ($flow as $index => $step)
                        <li class="flex flex-col gap-2">
                            <span class="font-display text-sm font-semibold text-brand-600">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="text-lg font-semibold">{{ $step['label'] }}</span>
                            <span class="text-sm text-ink-muted">{{ $step['description'] }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        {{-- Features --}}
        <section id="ciri" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold tracking-wide text-brand-600 uppercase">Percuma untuk pengantin</p>
                <h2 class="mt-2 font-display text-3xl font-semibold tracking-tight sm:text-4xl">Semua persiapan, satu tempat</h2>
                <p class="mt-4 text-lg text-ink-muted">Cari vendor hanyalah permulaan. Neekah bantu anda bersiap, dari checklist pertama hingga kad jemputan terakhir.</p>
            </div>

            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($features as $feature)
                    <article class="flex flex-col gap-3 rounded-3xl border border-line bg-surface-raised p-6 transition hover:border-brand-300 hover:shadow-lg hover:shadow-brand-900/5">
                        <span class="flex size-11 items-center justify-center rounded-2xl bg-brand-50 text-2xl">{{ $feature['icon'] }}</span>
                        <h3 class="text-lg font-semibold">{{ $feature['title'] }}</h3>
                        <p class="text-sm text-ink-muted">{{ $feature['description'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        {{-- Marketplace preview --}}
        <section id="marketplace" class="bg-surface-muted">
            <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-2xl">
                        <p class="text-sm font-semibold tracking-wide text-brand-600 uppercase">Marketplace</p>
                        <h2 class="mt-2 font-display text-3xl font-semibold tracking-tight sm:text-4xl">Cari vendor ikut kategori, lokasi & bajet</h2>
                        <p class="mt-4 text-lg text-ink-muted">Bandingkan pakej dan portfolio, kemudian hubungi vendor terus. Setiap vendor disemak oleh pasukan Neekah sebelum disenaraikan.</p>
                    </div>

                    {{-- Search --}}
                    <form class="flex w-full flex-col gap-2 rounded-2xl border border-line bg-surface-raised p-2 shadow-sm sm:flex-row lg:w-auto" method="GET" action="{{ route('vendors.index') }}">
                        <label class="sr-only" for="search-category">Kategori</label>
                        <select id="search-category" name="category" class="rounded-xl border-0 bg-transparent px-3 py-2 text-sm font-medium focus:ring-2 focus:ring-brand-400 focus:outline-none">
                            <option value="">Semua kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->slug }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <label class="sr-only" for="search-q">Cari</label>
                        <input id="search-q" name="q" type="search" placeholder="Bandar atau nama vendor" class="rounded-xl border-0 bg-transparent px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400 focus:outline-none">
                        <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">Cari</button>
                    </form>
                </div>

                <ul class="mt-10 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ($categories as $category)
                        <li>
                            <a href="{{ route('vendors.index', ['category' => $category->slug]) }}" class="flex h-full flex-col gap-1 rounded-2xl border border-line bg-surface-raised p-4 transition hover:border-brand-300 hover:shadow-md">
                                <x-category-icon class="size-9" :category="$category" />
                                <span class="font-semibold">{{ $category->name }}</span>
                                <span class="text-xs text-ink-muted">{{ $category->examples }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-14">
                    <h3 class="font-display text-2xl font-semibold">Vendor pilihan</h3>
                </div>

                <ul class="mt-6 grid gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3">
                    @foreach ($vendors as $vendor)
                        <li><x-vendor-card :vendor="$vendor" /></li>
                    @endforeach
                </ul>

                <div class="mt-8 flex flex-col items-center gap-3 text-center">
                    <a href="{{ route('vendors.index') }}" class="inline-flex items-center justify-center rounded-full border border-line bg-surface-raised px-6 py-3 text-sm font-semibold transition hover:border-brand-300 hover:text-brand-700">Lihat semua vendor →</a>
                    @if ($helpUrl)
                        <p class="text-sm text-ink-muted">Tak jumpa vendor yang anda cari? <a href="{{ $helpUrl }}" target="_blank" rel="noopener" class="font-semibold text-brand-600 hover:underline">WhatsApp kami</a>, kami bantu kongsikan kepada vendor lain.</p>
                    @endif
                </div>
            </div>
        </section>

        {{-- Trust / booking policy --}}
        <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="grid gap-10 rounded-3xl border border-line bg-surface-raised p-8 lg:grid-cols-2 lg:p-12">
                <div class="flex flex-col gap-4">
                    <p class="text-sm font-semibold tracking-wide text-brand-600 uppercase">Tanpa orang tengah</p>
                    <h2 class="font-display text-3xl font-semibold tracking-tight">Deal terus dengan vendor. Kami cuma jambatan.</h2>
                    <p class="text-ink-muted">Neekah tidak mengambil sebarang bayaran, sama ada daripada anda atau vendor. Hubungi vendor terus, bincang pakej, buat site visit, dan bayar mengikut cara yang anda berdua setuju. Tugas kami ialah pastikan anda jumpa vendor yang tepat, dengan lebih cepat.</p>
                </div>

                <ol class="flex flex-col gap-3 text-sm">
                    @foreach (['Cari vendor', 'Lihat profil & pakej', 'Hubungi terus', 'Bincang & deal', 'Hari bahagia'] as $index => $stage)
                        <li class="flex items-center gap-3">
                            <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-700">{{ $index + 1 }}</span>
                            <span class="font-medium">{{ $stage }}</span>
                            @if ($loop->last)
                                <span class="ml-auto text-lg" aria-hidden="true">💍</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        {{-- For vendors --}}
        <section id="vendor" class="bg-brand-900 text-white">
            <div class="mx-auto grid max-w-7xl gap-12 px-4 py-20 sm:px-6 lg:grid-cols-2 lg:px-8">
                <div class="flex flex-col gap-5">
                    <p class="text-sm font-semibold tracking-wide text-gold-400 uppercase">Untuk vendor</p>
                    <h2 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">Senarai percuma. Pengantin hubungi anda terus.</h2>
                    <p class="text-brand-100">Tiada komisen, tiada yuran. Lengkapkan profil, pakej dan portfolio, dan pengantin yang mencari di kawasan anda akan WhatsApp anda secara terus. Vendor yang lengkap profilnya dan cepat membalas enquiry dipaparkan lebih tinggi.</p>

                    <ul class="mt-2 flex flex-wrap gap-2 text-sm">
                        @foreach (['New', 'Verified', 'Trusted', 'Top', '🏆 Recommended'] as $tier)
                            <li class="rounded-full border border-brand-700 bg-brand-800 px-3 py-1 font-medium">{{ $tier }}</li>
                        @endforeach
                    </ul>

                    <a href="{{ route('vendor.register') }}" class="mt-4 inline-flex w-fit items-center rounded-full bg-gold-400 px-6 py-3 font-semibold text-brand-900 transition hover:bg-gold-300">Daftar sebagai vendor</a>
                </div>

                <div class="rounded-3xl border border-brand-700 bg-brand-800/60 p-6 sm:p-8">
                    <p class="text-xs font-semibold tracking-wide text-gold-400 uppercase">Apa yang anda dapat</p>
                    <ul class="mt-4 divide-y divide-brand-700 text-sm">
                        @foreach ($vendorBenefits as $benefit)
                            <li class="flex items-center gap-3 py-3">
                                <svg class="size-4 shrink-0 text-gold-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                <span class="min-w-0 text-brand-100">{{ $benefit }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-4 text-xs text-brand-200">Neekah tidak mengambil sebarang komisen. Apa yang anda deal dengan pengantin adalah milik anda sepenuhnya.</p>
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <section id="cta" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-3xl border border-line bg-surface-raised px-6 py-14 text-center sm:px-12">
                <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_center,var(--color-brand-100),transparent_65%)] opacity-70"></div>
                <h2 class="font-display text-3xl font-semibold tracking-tight text-balance sm:text-4xl">Majlis impian bermula dengan vendor yang tepat.</h2>
                <p class="mx-auto mt-4 max-w-xl text-lg text-ink-muted">Daftar percuma untuk lihat nombor vendor, cipta checklist dan buat kad jemputan digital.</p>
                <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-full bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 transition hover:bg-brand-700">Daftar percuma</a>
                    <a href="{{ route('vendors.index') }}" class="inline-flex items-center justify-center rounded-full border border-line bg-surface-raised px-6 py-3 text-sm font-semibold transition hover:border-brand-300 hover:text-brand-700">Cari vendor</a>
                </div>
            </div>
        </section>
    </main>

    <x-site.footer />
    <x-site.mobile-nav />
</x-layouts.app>
