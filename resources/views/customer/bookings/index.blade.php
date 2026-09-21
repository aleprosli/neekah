<x-layouts.customer :title="__('pages.dash.tempahan_saya')" :heading="__('pages.dash.tempahan_saya_2')" :subheading="__('pages.dash.semua_booking_dan_bayaran_anda')">
    <x-slot:actions>
        <a href="{{ route('vendors.index') }}" class="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">{{ __('pages.dash.cari_vendor') }}</a>
    </x-slot:actions>

    {{-- resources/js/components/customer/CustomerBookingsPage.vue --}}
    <div data-vue="customer-bookings-page" data-props="@vueProps($props)"></div>
</x-layouts.customer>
