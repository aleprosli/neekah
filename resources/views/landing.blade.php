<x-layouts.app :title="__('pages.landing.semua_urusan_majlis_satu_platform')">
    <x-site.header />

    {{-- A light watercolor botanical keeps the page bridal without competing
         with the real invitation designs shown in the hero. --}}
    <main class="bg-ivory">
        {{-- Hero --}}
        <section class="relative overflow-hidden">
            <img src="{{ asset('img/decor/botanical-corner.svg') }}" alt="" class="absolute -bottom-24 -left-28 w-96 opacity-25 sm:w-[34rem] lg:w-[40rem]" decoding="async">
            <img src="{{ asset('img/decor/botanical-corner.svg') }}" alt="" class="absolute -top-32 -right-36 w-96 scale-x-[-1] opacity-10 sm:w-[32rem]" decoding="async">

            <div class="relative mx-auto grid max-w-7xl items-center gap-14 px-4 pt-28 pb-16 sm:px-6 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] lg:px-8 lg:pt-36 lg:pb-24">
                <div class="flex min-w-0 flex-col gap-6">
                    <p class="font-script text-3xl text-brand-600 sm:text-4xl">{{ __('pages.landing.script_eyebrow') }}</p>

                    <h1 class="font-display text-4xl leading-tight font-semibold tracking-tight text-balance sm:text-5xl lg:text-6xl">{{ __('pages.landing.cari_vendor_kahwin') }} <span class="text-brand-600">{{ __('pages.landing.headline_tail') }}</span>
                    </h1>

                    <p class="max-w-xl text-lg text-ink-muted text-pretty">{{ __('pages.landing.neekah_bantu_anda_cari_vendor') }}</p>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <x-site.button :href="route('vendors.index')">{{ __('pages.landing.cari_vendor_sekarang') }}</x-site.button>
                        <x-site.button href="#vendor" variant="secondary">{{ __('pages.landing.sertai_sebagai_vendor') }}</x-site.button>
                    </div>

                    <p class="text-sm text-ink-muted">{{ __('pages.landing.hero_note') }}</p>

                    <dl class="grid grid-cols-3 gap-4 border-t border-gold-300 pt-6 text-sm">
                        <div>
                            <dt class="text-ink-muted">{{ __('pages.landing.kategori_vendor') }}</dt>
                            <dd class="font-display text-2xl font-semibold">{{ $categoryCount }}</dd>
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

        {{-- How it works: one phone playing each step on a made-up Neekah screen, each step's card sliding out of it in turn (FlowPhoneDemo.vue) --}}
        @php
            $flowSteps = collect($flow)->map(fn (array $step, int $index): array => $step + [
                'number' => __('pages.landing.step', ['number' => $index + 1]),
                'illustration' => Blade::render('<x-site.flow-illustration :name="$name" class="size-full" />', ['name' => $step['key']]),
            ])->all();
        @endphp
        <section id="cara" class="relative overflow-hidden border-y border-gold-300/60 bg-ivory">
            <div class="relative mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-20 lg:px-8">
                <div class="flex flex-col items-center gap-3 text-center">
                    <h2 class="font-script text-3xl text-brand-600 sm:text-4xl">{{ __('pages.landing.cara_ia_berjalan') }}</h2>
                    <x-site.ornament name="divider-floral" class="h-6 w-44" color="var(--color-gold-500)" color2="var(--color-gold-300)" />
                </div>

                <div class="mt-10 sm:mt-12" data-vue="flow-phone-demo" data-props="@vueProps(['steps' => $flowSteps, 'text' => __('pages.landing.flow_demo')])">
                    <ol class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($flowSteps as $step)
                            <li class="flex min-w-0 items-center gap-3 rounded-2xl bg-white/60 p-3">
                                <x-site.flow-illustration :name="$step['key']" class="size-12" />
                                <span class="min-w-0 text-sm">
                                    <span class="block text-[10px] font-semibold tracking-[0.2em] text-brand-600 uppercase">{{ $step['number'] }}</span>
                                    <span class="block font-display text-base font-semibold">{{ $step['label'] }}</span>
                                    <span class="text-ink-muted">{{ $step['description'] }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </section>

        {{-- Features --}}
        <section id="ciri" class="relative overflow-hidden">
            <img src="{{ asset('img/decor/botanical-corner.svg') }}" alt="" class="absolute top-8 -right-28 w-80 scale-x-[-1] opacity-10 sm:w-[28rem]" loading="lazy" decoding="async">

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

        {{-- Marketplace, Kenangan, Pro and Boost each have a page of their own
             (/ciri/*); here only a card for each one on sale. --}}
        <x-site.feature-explore id="terokai" :pages="$explore" :eyebrow="__('pages.features.explore.eyebrow')" :title="__('pages.features.explore.title')" />

        {{-- Direct dealing: a printed card sheet, double gold rule and rose corners --}}
        <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-3xl border border-gold-300 bg-surface-raised p-8 sm:p-12 lg:p-16">
                <span class="pointer-events-none absolute inset-3 rounded-[1.25rem] border border-gold-300/70" aria-hidden="true"></span>
                <img src="{{ asset('img/decor/botanical-corner.svg') }}" alt="" class="absolute -top-20 -left-24 w-72 opacity-15 sm:w-96" loading="lazy" decoding="async">
                <img src="{{ asset('img/decor/botanical-corner.svg') }}" alt="" class="absolute -right-24 -bottom-20 w-72 rotate-180 opacity-15 sm:w-96" loading="lazy" decoding="async">

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

                    <ol class="grid content-center gap-3 text-sm sm:grid-cols-2">
                        @foreach (__('pages.landing.stages') as $index => $stage)
                            <li @class([
                                'group relative flex min-h-24 items-center gap-4 overflow-hidden rounded-2xl border p-4 transition hover:-translate-y-0.5 hover:shadow-md',
                                'border-line bg-surface-raised hover:border-gold-400' => ! $loop->last,
                                'border-brand-800 bg-brand-900 text-white sm:col-span-2' => $loop->last,
                            ])>
                                <span @class([
                                    'flex size-10 shrink-0 items-center justify-center rounded-full border font-display text-sm font-semibold',
                                    'border-gold-400 bg-brand-50 text-brand-700' => ! $loop->last,
                                    'border-gold-400/70 bg-brand-800 text-gold-300' => $loop->last,
                                ])>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="font-display text-base font-semibold">{{ $stage }}</span>
                                @if ($loop->last)
                                    <x-site.ornament name="heart" class="ml-auto size-6" color="var(--color-gold-300)" />
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

                    <ul class="mt-2 flex items-center justify-between pb-2">
                        @foreach (\App\Enums\VendorTier::cases() as $tier)
                            <li>
                                <x-vendor-rank-badge :tier="$tier" :label="$tier->label().' vendor rank'" class="size-12 sm:size-16" />
                            </li>
                            @unless ($loop->last)
                                <li class="text-xs text-gold-400 sm:text-sm" aria-hidden="true">→</li>
                            @endunless
                        @endforeach
                    </ul>
                    <p class="text-xs text-brand-200">{{ __('pages.landing.tier_note') }}</p>
                    @if ($eliteUrl)
                        <a href="{{ $eliteUrl }}" class="inline-flex w-fit items-center gap-2 rounded-full border border-gold-300 bg-linear-to-r from-ink to-brand-800 px-3 py-1 text-sm font-semibold text-gold-300">✦ Pro Elite <span class="text-xs font-normal text-brand-100">{{ __('pages.landing.elite.chip') }}</span></a>
                    @endif

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

        <x-site.feature-cta id="cta" :title="__('pages.landing.majlis_impian_bermula_dengan_vendor')" :body="__('pages.landing.daftar_percuma_untuk_lihat_nombor')">
            <x-site.button :href="route('register')">{{ __('pages.landing.daftar_percuma') }}</x-site.button>
            <x-site.button :href="route('vendors.index')" variant="secondary">{{ __('pages.landing.cari_vendor') }}</x-site.button>
        </x-site.feature-cta>
    </main>

    <x-site.footer />
    <x-site.mobile-nav />
</x-layouts.app>
