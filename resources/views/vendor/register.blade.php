<x-layouts.app title="Daftar sebagai vendor">
    <x-site.header />

    <main class="mx-auto max-w-3xl px-4 pt-24 pb-24 sm:px-6 lg:pt-28">
        <div class="text-center">
            <p class="text-sm font-semibold tracking-wide text-brand-600 uppercase">Untuk vendor</p>
            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight sm:text-4xl">Sertai Neekah sebagai vendor</h1>
            <p class="mx-auto mt-3 max-w-xl text-ink-muted">Daftar percuma. Selepas admin meluluskan profil anda, perniagaan anda akan dipaparkan di marketplace dan pengantin boleh tempah terus.</p>
        </div>

        {{-- resources/js/components/vendor/VendorRegisterForm.vue --}}
        <div data-vue="vendor-register-form" data-props="{{ json_encode($props) }}"></div>
    </main>

    {{-- Turnstile's own script, which finds the widget the component renders. --}}
    <x-turnstile-script />

    <x-site.footer />
</x-layouts.app>
