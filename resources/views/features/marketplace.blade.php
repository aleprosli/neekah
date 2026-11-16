<x-site.feature-page :title="__('pages.features.marketplace.name')" :explore="$explore">
    <x-slot:hero>
        <x-site.feature-hero icon="store" :name="__('pages.features.marketplace.name')" :title="__('pages.landing.cari_vendor_ikut_kategori_lokasi')" :lead="__('pages.landing.bandingkan_pakej_dan_portfolio_kemudian')">
            <form class="flex w-full flex-col gap-2 rounded-2xl border border-line bg-surface-raised p-2 shadow-sm sm:w-auto sm:flex-row" method="GET" action="{{ route('vendors.index') }}">
                <label class="sr-only" for="search-category">{{ __('pages.landing.kategori') }}</label>
                <select id="search-category" name="category" class="nk-select rounded-xl border-0 bg-transparent px-3 py-2 pr-8 text-sm font-medium focus:ring-2 focus:ring-brand-400 focus:outline-none">
                    <option value="">{{ __('pages.landing.semua_kategori') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->slug }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                <label class="sr-only" for="search-q">{{ __('pages.landing.cari') }}</label>
                <input id="search-q" name="q" type="search" placeholder="{{ __('pages.landing.bandar_atau_nama_vendor') }}" class="rounded-xl border-0 bg-transparent px-3 py-2 text-sm focus:ring-2 focus:ring-brand-400 focus:outline-none">
                <button type="submit" class="rounded-xl bg-brand-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">{{ __('pages.landing.cari_2') }}</button>
            </form>

            <x-slot:note>{{ __('pages.landing.hero_note') }}</x-slot:note>
        </x-site.feature-hero>
    </x-slot:hero>

    <x-site.feature-section id="kategori" tone="raised" :eyebrow="__('pages.landing.kategori')" :title="__('pages.features.marketplace.categories_title')" :body="__('pages.features.marketplace.categories_body')">
        <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            @foreach ($categories as $category)
                <li>
                    <a href="{{ route('vendors.index', ['category' => $category->slug]) }}" class="flex h-full flex-col gap-1 rounded-2xl border border-line bg-ivory p-4 transition hover:border-gold-400 hover:shadow-md">
                        <x-category-icon class="size-9" :category="$category" />
                        <span class="font-semibold">{{ $category->name }}</span>
                        <span class="text-xs text-ink-muted">{{ $category->examples }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </x-site.feature-section>

    <x-site.feature-section id="pilihan" :eyebrow="__('pages.features.marketplace.featured_eyebrow')" :title="__('pages.landing.vendor_pilihan')" :body="__('pages.features.marketplace.featured_body')">
        <ul class="grid gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3">
            @foreach ($vendors as $vendor)
                <li><x-vendor-card :vendor="$vendor" /></li>
            @endforeach
        </ul>

        <div class="mt-10 flex flex-col items-center gap-3 text-center">
            <x-site.button :href="route('vendors.index')" variant="secondary">{{ __('pages.landing.lihat_semua_vendor') }}</x-site.button>
            @if ($helpUrl)
                <p class="text-sm text-ink-muted">{{ __('pages.landing.tak_jumpa_vendor_yang_anda') }} <a href="{{ $helpUrl }}" target="_blank" rel="noopener" class="font-semibold text-brand-600 hover:underline">{{ __('pages.landing.whatsapp_kami') }}</a>{{ __('pages.landing.kami_bantu_kongsikan') }}</p>
            @endif
        </div>
    </x-site.feature-section>

    <x-site.feature-cta :title="__('pages.landing.majlis_impian_bermula_dengan_vendor')" :body="__('pages.landing.daftar_percuma_untuk_lihat_nombor')">
        @guest
            <x-site.button :href="route('register')">{{ __('pages.landing.daftar_percuma') }}</x-site.button>
        @endguest
        <x-site.button :href="route('vendors.index')" :variant="auth()->check() ? 'primary' : 'secondary'">{{ __('pages.landing.cari_vendor') }}</x-site.button>
    </x-site.feature-cta>
</x-site.feature-page>
