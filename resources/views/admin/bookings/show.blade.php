<x-layouts.admin :title="'Booking '.$booking->reference" :heading="$booking->reference" :subheading="$booking->event_date->translatedFormat('l, j F Y')">
    <x-slot:actions>
        <x-booking-status :status="$booking->status" class="self-center" />
    </x-slot:actions>

    {{-- resources/js/components/admin/AdminBookingDetail.vue --}}
    <div data-vue="admin-booking-detail" data-props="{{ json_encode($props) }}"></div>
</x-layouts.admin>
