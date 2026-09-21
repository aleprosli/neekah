<x-layouts.app :title="__('pages.dash.tukar_ke_akaun_vendor')">
    <x-site.header />

    <main class="mx-auto max-w-3xl px-4 pt-24 pb-24 sm:px-6 lg:pt-28">
        <div class="text-center">
            <p class="text-sm font-semibold tracking-wide text-brand-600 uppercase">{{ __('pages.dash.untuk_vendor') }}</p>
            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight sm:text-4xl">{{ __('pages.dash.tukar_ke_akaun_vendor_2') }}</h1>
            <p class="mx-auto mt-3 max-w-xl text-ink-muted">{{ __('pages.dash.tersilap_daftar_sebagai_pengantin_isi') }}</p>
        </div>

        {{-- resources/js/components/vendor/VendorRegisterForm.vue --}}
        <div data-vue="vendor-register-form" data-props="@vueProps($props)"></div>
    </main>

    <x-site.footer />
</x-layouts.app>
