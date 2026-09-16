<x-layouts.customer title="Kad jemputan" heading="Kad jemputan digital" subheading="Isi maklumat majlis, pilih template dan siarkan pada alamat web anda sendiri.">
    <x-slot:actions>
        <a href="{{ route('sites.templates') }}" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">Lihat semua template</a>
        <a href="{{ route('site.preview') }}" target="_blank" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">Pratonton</a>
    </x-slot:actions>

    {{-- resources/js/components/customer/CustomerSitePage.vue --}}
    <div data-vue="customer-site-page" data-props="@vueProps($props)"></div>
</x-layouts.customer>
