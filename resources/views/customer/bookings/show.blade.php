<x-layouts.customer :title="'Tempahan '.$booking->reference" :heading="$booking->vendor->name" :subheading="$booking->package_name.' · '.$booking->event_date->translatedFormat('l, j F Y')">
    <x-slot:actions>
        <x-booking-status :status="$booking->status" class="self-center" />
        <a href="{{ route('vendors.show', $booking->vendor) }}" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">Lihat vendor</a>
    </x-slot:actions>

    {{-- resources/js/components/customer/CustomerBookingDetail.vue --}}
    <div data-vue="customer-booking-detail" data-props="@vueProps($props)"></div>
</x-layouts.customer>
