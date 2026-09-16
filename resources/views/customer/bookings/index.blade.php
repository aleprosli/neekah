<x-layouts.customer title="Tempahan saya" heading="Tempahan saya" subheading="Semua booking dan bayaran anda direkod di sini.">
    <x-slot:actions>
        <a href="{{ route('vendors.index') }}" class="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">Cari vendor</a>
    </x-slot:actions>

    {{-- resources/js/components/customer/CustomerBookingsPage.vue --}}
    <div data-vue="customer-bookings-page" data-props="@vueProps($props)"></div>
</x-layouts.customer>
