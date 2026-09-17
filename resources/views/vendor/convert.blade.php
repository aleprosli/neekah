<x-layouts.app title="Tukar ke akaun vendor">
    <x-site.header />

    <main class="mx-auto max-w-3xl px-4 pt-24 pb-24 sm:px-6 lg:pt-28">
        <div class="text-center">
            <p class="text-sm font-semibold tracking-wide text-brand-600 uppercase">Untuk vendor</p>
            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight sm:text-4xl">Tukar ke akaun vendor</h1>
            <p class="mx-auto mt-3 max-w-xl text-ink-muted">Tersilap daftar sebagai pengantin? Isi maklumat perniagaan anda. Akaun yang sama akan menjadi akaun vendor dan menunggu kelulusan admin.</p>
        </div>

        {{-- resources/js/components/vendor/VendorRegisterForm.vue --}}
        <div data-vue="vendor-register-form" data-props="@vueProps($props)"></div>
    </main>

    <x-site.footer />
</x-layouts.app>
