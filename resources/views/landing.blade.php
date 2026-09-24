<x-layouts.app :title="__('pages.landing.semua_urusan_majlis_satu_platform')">
    <x-site.header />

    {{-- A light watercolor botanical keeps the page bridal without competing
         with the real invitation designs shown in the hero. --}}
    <main class="bg-ivory">
        {{-- Hero --}}
        <section class="relative overflow-hidden">
            <img src="{{ asset('img/decor/botanical-corner.webp') }}" alt="" class="absolute -bottom-24 -left-28 w-96 opacity-25 sm:w-[34rem] lg:w-[40rem]" decoding="async">
            <img src="{{ asset('img/decor/botanical-corner.webp') }}" alt="" class="absolute -top-32 -right-36 w-96 scale-x-[-1] opacity-10 sm:w-[32rem]" decoding="async">

            <div class="relative mx-auto grid max-w-7xl items-center gap-14 px-4 pt-28 pb-16 sm:px-6 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] lg:px-8 lg:pt-36 lg:pb-24">
                <div class="flex min-w-0 flex-col gap-6">
                    <p class="font-script text-3xl text-brand-600 sm:text-4xl">{{ __('pages.landing.script_eyebrow') }}</p>

                    <h1 class="font-display text-4xl leading-tight font-semibold tracking-tight text-balance sm:text-5xl lg:text-6xl">{{ __('pages.landing.cari_vendor_kahwin') }}<span class="text-brand-600">{{ __('pages.landing.headline_tail') }}</span>
                    </h1>

                    <p class="max-w-xl text-lg text-ink-muted text-pretty">{{ __('pages.landing.neekah_bantu_anda_cari_vendor') }}</p>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('vendors.index') }}" class="inline-flex items-center justify-center rounded-full bg-brand-600 px-6 py-3 text-base font-semibold text-white shadow-lg shadow-brand-600/20 transition hover:bg-brand-700">{{ __('pages.landing.cari_vendor_sekarang') }}</a>
                        <a href="#vendor" class="inline-flex items-center justify-center rounded-full border border-gold-400 bg-surface-raised px-6 py-3 text-base font-semibold transition hover:border-gold-500 hover:text-brand-700">{{ __('pages.landing.sertai_sebagai_vendor') }}</a>
                    </div>

                    <p class="text-sm text-ink-muted">{{ __('pages.landing.hero_note') }}</p>

                    <dl class="grid grid-cols-3 gap-4 border-t border-gold-300 pt-6 text-sm">
                        <div>
                            <dt class="text-ink-muted">{{ __('pages.landing.kategori_vendor') }}</dt>
                            <dd class="font-display text-2xl font-semibold">{{ $categories->count() }}</dd>
                        </div>
                        <div>
                            <dt class="text-ink-muted">{{ __('pages.landing.yuran_platform') }}</dt>
                            <dd class="font-display text-2xl font-semibold">{{ __('pages.landing.percuma') }}</dd>
                        </div>
                        <div>
                            <dt class="text-ink-muted">{{ __('pages.landing.orang_tengah') }}</dt>
                            <dd class="font-display text-2xl font-semibold">{{ __('pages.landing.tiada') }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Three real designs, fanned out like cards on a table. Each one
                     is the card renderer at thumbnail size; without JavaScript
                     the design's name and colours still stand in. --}}
                <div class="relative mx-auto w-full max-w-md lg:max-w-none">
                    <div class="relative mx-auto aspect-[4/3] w-full max-w-[26rem] sm:max-w-[30rem]">
                        @foreach ($covers as $index => $cover)
                            @php
                                $pose = [
                                    'left-0 top-8 -rotate-[9deg] z-10 w-[46%]',
                                    'left-1/2 top-0 -translate-x-1/2 z-20 w-[50%]',
                                    'right-0 top-8 rotate-[9deg] z-10 w-[46%]',
                                ][$index] ?? 'left-1/2 top-0 -translate-x-1/2 z-20 w-[50%]';
                            @endphp
                            <a href="{{ route('sites.templates.show', $cover['slug']) }}" class="absolute {{ $pose }} block overflow-hidden rounded-lg border border-line/60 bg-surface-raised p-1.5 shadow-[0_24px_50px_-20px_rgb(0_0_0/0.45)] transition hover:z-30 hover:-translate-y-2 hover:shadow-[0_32px_60px_-20px_rgb(0_0_0/0.5)]" aria-label="{{ $cover['name'] }}">
                                <div class="aspect-[9/16] overflow-hidden rounded" data-vue="card-view" data-props="@vueProps($cover['card'])">
                                    <div class="flex h-full w-full flex-col items-center justify-center gap-1 p-3 text-center" style="background:{{ $cover['card']['vars']['--c-bg'] }};color:{{ $cover['card']['vars']['--c-onbg'] }}">
                                        <span class="text-[0.55rem] tracking-[0.3em] uppercase" style="color:{{ $cover['card']['vars']['--c-acc'] }}">Walimatul Urus</span>
                                        <span class="font-display text-sm">{{ $cover['name'] }}</span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <p class="mt-6 text-center text-sm text-ink-muted">
                        {{ __('pages.landing.covers_caption') }} ·
                        <a href="{{ route('sites.templates') }}" class="font-semibold text-brand-600 underline underline-offset-4 hover:text-brand-700">{{ __('pages.landing.covers_link') }}</a>
                    </p>
                </div>
            </div>
        </section>

        {{-- How it works: six steps on one gold thread --}}
        <section id="cara" class="relative border-y border-gold-300/60 bg-surface-raised">
            <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
                <div class="flex flex-col items-center gap-3 text-center">
                    <h2 class="font-script text-3xl text-brand-600 sm:text-4xl">{{ __('pages.landing.cara_ia_berjalan') }}</h2>
                    <x-site.ornament name="divider-floral" class="h-6 w-44" color="var(--color-gold-500)" color2="var(--color-gold-300)" />
                </div>

                <ol class="relative mt-10 grid grid-cols-2 gap-x-6 gap-y-10 sm:grid-cols-3 lg:grid-cols-6">
                    <span class="absolute top-2.5 right-[9%] left-[9%] hidden h-px bg-gold-300 lg:block" aria-hidden="true"></span>
                    @foreach ($flow as $index => $step)
                        <li class="relative flex min-w-0 flex-col gap-2 lg:items-center lg:text-center">
                            <span class="flex size-5 items-center justify-center rounded-full border border-gold-500 bg-surface-raised" aria-hidden="true"><span class="size-2 rounded-full bg-gold-500"></span></span>
                            <span class="font-script text-2xl leading-none text-brand-600">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="font-display text-lg font-semibold">{{ $step['label'] }}</span>
                            <span class="text-sm text-ink-muted">{{ $step['description'] }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        {{-- Features --}}
        <section id="ciri" class="relative overflow-hidden">
            <img src="{{ asset('img/decor/botanical-corner.webp') }}" alt="" class="absolute top-8 -right-28 w-80 scale-x-[-1] opacity-10 sm:w-[28rem]" loading="lazy" decoding="async">

            <div class="relative mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
                <div class="max-w-2xl">
                    <p class="text-sm font-semibold tracking-[0.2em] text-gold-600 uppercase">{{ __('pages.landing.percuma_untuk_pengantin') }}</p>
                    <h2 class="mt-2 font-display text-3xl font-semibold tracking-tight sm:text-4xl">{{ __('pages.landing.semua_persiapan_satu_tempat') }}</h2>
                    <p class="mt-4 text-lg text-ink-muted">{{ __('pages.landing.cari_vendor_hanyalah_permulaan_neekah') }}</p>
                </div>

                <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($features as $feature)
                        <article class="group relative flex flex-col gap-3 overflow-hidden rounded-3xl border border-line bg-surface-raised p-6 transition hover:border-gold-400 hover:shadow-lg hover:shadow-brand-900/5">
                            <x-site.ornament name="corner-blossom" class="absolute -top-6 -right-6 size-28 opacity-0 transition group-hover:opacity-40" color="var(--color-brand-200)" />
                            <span class="flex size-11 items-center justify-center rounded-full bg-brand-50 text-brand-700 ring-1 ring-gold-300">
                                <x-nav-icon :name="$feature['icon']" class="size-5" />
                            </span>
                            <h3 class="font-display text-lg font-semibold">{{ $feature['title'] }}</h3>
                            <p class="text-sm text-ink-muted">{{ $feature['description'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Marketplace preview --}}
        <section id="marketplace" class="relative overflow-hidden bg-ivory-deep">
            <img src="{{ asset('img/decor/botanical-corner.webp') }}" alt="" class="absolute -top-28 left-1/2 w-80 -translate-x-1/2 rotate-45 opacity-[0.06]" loading="lazy" decoding="async">

            <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-2xl">
                        <p class="text-sm font-semibold tracking-[0.2em] text-gold-600 uppercase">{{ __('pages.landing.marketplace') }}</p>
                        <h2 class="mt-2 font-display text-3xl font-semibold tracking-tight sm:text-4xl">{{ __('pages.landing.cari_vendor_ikut_kategori_lokasi') }}</h2>
                        <p class="mt-4 text-lg text-ink-muted">{{ __('pages.landing.bandingkan_pakej_dan_portfolio_kemudian') }}</p>
                    </div>

                    {{-- Search --}}
                    <form class="flex w-full flex-col gap-2 rounded-2xl border border-line bg-surface-raised p-2 shadow-sm sm:flex-row lg:w-auto" method="GET" action="{{ route('vendors.index') }}">
                        <label class="sr-only" for="search-category">{{ __('pages.landing.kategori') }}</label>
                        <select id="search-category" name="category" class="nk-select rounded-xl border-0 bg-transparent px-3 py-2 pr-8 text-sm font-medium focus:ring-2 focus:ring-brand-400 focus:outline-none">
                            <option value="">{{ __('pages.landing.semua_kategori') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->slug }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <label class="sr-only" for="search-q">{{ __('pages.landing.cari') }}</label>
                        <input id="search-q" name="q" type="search" :placeholder="__('pages.landing.bandar_atau_nama_vendor')" class="rounded-xl border-0 bg-transparent px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400 focus:outline-none">
                        <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">{{ __('pages.landing.cari_2') }}</button>
                    </form>
                </div>

                <ul class="mt-10 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ($categories as $category)
                        <li>
                            <a href="{{ route('vendors.index', ['category' => $category->slug]) }}" class="flex h-full flex-col gap-1 rounded-2xl border border-line bg-surface-raised p-4 transition hover:border-gold-400 hover:shadow-md">
                                <x-category-icon class="size-9" :category="$category" />
                                <span class="font-semibold">{{ $category->name }}</span>
                                <span class="text-xs text-ink-muted">{{ $category->examples }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-14 flex items-center gap-4">
                    <h3 class="font-display text-2xl font-semibold">{{ __('pages.landing.vendor_pilihan') }}</h3>
                    <span class="h-px flex-1 bg-gold-300" aria-hidden="true"></span>
                </div>

                <ul class="mt-6 grid gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3">
                    @foreach ($vendors as $vendor)
                        <li><x-vendor-card :vendor="$vendor" /></li>
                    @endforeach
                </ul>

                <div class="mt-8 flex flex-col items-center gap-3 text-center">
                    <a href="{{ route('vendors.index') }}" class="inline-flex items-center justify-center rounded-full border border-gold-400 bg-surface-raised px-6 py-3 text-sm font-semibold transition hover:border-gold-500 hover:text-brand-700">{{ __('pages.landing.lihat_semua_vendor') }}</a>
                    @if ($helpUrl)
                        <p class="text-sm text-ink-muted">{{ __('pages.landing.tak_jumpa_vendor_yang_anda') }}<a href="{{ $helpUrl }}" target="_blank" rel="noopener" class="font-semibold text-brand-600 hover:underline">{{ __('pages.landing.whatsapp_kami') }}</a>, kami bantu kongsikan kepada vendor lain.</p>
                    @endif
                </div>
            </div>
        </section>

        {{-- Direct dealing: a printed card sheet, double gold rule and rose corners --}}
        <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-3xl border border-gold-300 bg-surface-raised p-8 sm:p-12 lg:p-16">
                <span class="pointer-events-none absolute inset-3 rounded-[1.25rem] border border-gold-300/70" aria-hidden="true"></span>
                <img src="{{ asset('img/decor/botanical-corner.webp') }}" alt="" class="absolute -top-20 -left-24 w-72 opacity-15 sm:w-96" loading="lazy" decoding="async">
                <img src="{{ asset('img/decor/botanical-corner.webp') }}" alt="" class="absolute -right-24 -bottom-20 w-72 rotate-180 opacity-15 sm:w-96" loading="lazy" decoding="async">

                <div class="relative grid gap-10 lg:grid-cols-2">
                    <div class="flex flex-col gap-4">
                        <p class="text-sm font-semibold tracking-[0.2em] text-gold-600 uppercase">{{ __('pages.landing.tanpa_orang_tengah') }}</p>
                        <h2 class="font-display text-3xl font-semibold tracking-tight text-balance">{{ __('pages.landing.deal_terus_dengan_vendor_kami') }}</h2>
                        <p class="text-ink-muted">{{ __('pages.landing.neekah_tidak_mengambil_sebarang_bayaran') }}</p>

                        <div class="mt-2 rounded-2xl bg-brand-50 p-5">
                            <h3 class="font-display text-lg font-semibold text-brand-800">{{ __('pages.landing.direct_title') }}</h3>
                            <p class="mt-2 text-sm text-brand-900/80">{{ __('pages.landing.direct_detail') }}</p>
                            <ul class="mt-4 flex flex-col gap-2 text-sm">
                                @foreach (['direct_point_1', 'direct_point_2', 'direct_point_3'] as $point)
                                    <li class="flex items-start gap-2">
                                        <x-nav-icon name="check" class="mt-0.5 size-4 text-gold-600" />
                                        <span>{{ __('pages.landing.'.$point) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <ol class="relative flex flex-col gap-0 text-sm">
                        <span class="absolute top-4 bottom-4 left-[0.9rem] w-px bg-gold-300" aria-hidden="true"></span>
                        @foreach (__('pages.landing.stages') as $index => $stage)
                            <li class="relative flex items-center gap-4 py-3">
                                <span class="relative z-10 flex size-7 shrink-0 items-center justify-center rounded-full border border-gold-500 bg-surface-raised font-display text-xs font-semibold text-brand-700">{{ $index + 1 }}</span>
                                <span class="font-medium">{{ $stage }}</span>
                                @if ($loop->last)
                                    <x-site.ornament name="heart" class="ml-auto size-5" color="var(--color-brand-500)" />
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </section>

        {{-- For vendors --}}
        <section id="vendor" class="relative overflow-hidden bg-brand-900 text-white">
            <img src="{{ asset('img/decor/songket-cempaka-corner.webp') }}" alt="" class="absolute -top-8 -left-20 w-80 opacity-20 sm:w-[28rem]" loading="lazy" decoding="async">
            <img src="{{ asset('img/decor/songket-cempaka-corner.webp') }}" alt="" class="absolute -right-20 -bottom-8 w-80 rotate-180 opacity-15 sm:w-[28rem]" loading="lazy" decoding="async">

            <div class="relative mx-auto grid max-w-7xl gap-12 px-4 py-20 sm:px-6 lg:grid-cols-2 lg:px-8">
                <div class="flex flex-col gap-5">
                    <p class="text-sm font-semibold tracking-[0.2em] text-gold-400 uppercase">{{ __('pages.landing.untuk_vendor') }}</p>
                    <h2 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">{{ __('pages.landing.senarai_percuma_pengantin_hubungi_anda') }}</h2>
                    <p class="text-brand-100">{{ __('pages.landing.tiada_komisen_tiada_yuran_lengkapkan') }}</p>

                    <ul class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                        @foreach (['New', 'Verified', 'Trusted', 'Top', 'Elite'] as $tier)
                            <li class="rounded-full border border-gold-400/50 bg-brand-800 px-3 py-1 font-medium">{{ $tier }}</li>
                            @unless ($loop->last)
                                <li class="text-gold-400" aria-hidden="true">→</li>
                            @endunless
                        @endforeach
                    </ul>
                    <p class="text-xs text-brand-200">{{ __('pages.landing.tier_note') }}</p>

                    <a href="{{ route('vendor.register') }}" class="mt-4 inline-flex w-fit items-center rounded-full bg-gold-400 px-6 py-3 font-semibold text-brand-900 transition hover:bg-gold-300">{{ __('pages.landing.daftar_sebagai_vendor') }}</a>
                </div>

                <div class="rounded-3xl border border-gold-400/40 bg-brand-800/60 p-6 sm:p-8">
                    <p class="text-xs font-semibold tracking-[0.2em] text-gold-400 uppercase">{{ __('pages.landing.apa_yang_anda_dapat') }}</p>
                    <ul class="mt-4 divide-y divide-brand-700 text-sm">
                        @foreach ($vendorBenefits as $benefit)
                            <li class="flex items-center gap-3 py-3">
                                <x-nav-icon name="check" class="size-4 text-gold-300" />
                                <span class="min-w-0 text-brand-100">{{ $benefit }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-4 text-xs text-brand-200">{{ __('pages.landing.neekah_tidak_mengambil_sebarang_komisen') }}</p>
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <section id="cta" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-3xl border border-gold-300 bg-surface-raised px-6 py-16 text-center sm:px-12">
                <img src="{{ asset('img/decor/botanical-corner.webp') }}" alt="" class="absolute -bottom-28 -left-24 w-80 opacity-15 sm:w-96" loading="lazy" decoding="async">
                <img src="{{ asset('img/decor/botanical-corner.webp') }}" alt="" class="absolute -top-28 -right-24 w-80 scale-x-[-1] opacity-10 sm:w-96" loading="lazy" decoding="async">

                <div class="relative">
                    <x-site.ornament name="divider-floral" class="mx-auto h-6 w-44" color="var(--color-gold-500)" color2="var(--color-gold-300)" />
                    <h2 class="mt-5 font-display text-3xl font-semibold tracking-tight text-balance sm:text-4xl">{{ __('pages.landing.majlis_impian_bermula_dengan_vendor') }}</h2>
                    <p class="mx-auto mt-4 max-w-xl text-lg text-ink-muted">{{ __('pages.landing.daftar_percuma_untuk_lihat_nombor') }}</p>
                    <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-full bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 transition hover:bg-brand-700">{{ __('pages.landing.daftar_percuma') }}</a>
                        <a href="{{ route('vendors.index') }}" class="inline-flex items-center justify-center rounded-full border border-gold-400 bg-surface-raised px-6 py-3 text-sm font-semibold transition hover:border-gold-500 hover:text-brand-700">{{ __('pages.landing.cari_vendor') }}</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <x-site.footer />
    <x-site.mobile-nav />
</x-layouts.app>
