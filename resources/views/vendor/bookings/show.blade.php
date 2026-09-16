@php use App\Enums\BookingStatus; @endphp

<x-layouts.vendor :title="'Booking '.$booking->reference" :heading="$booking->reference" :subheading="$booking->user->name.' · '.$booking->event_date->translatedFormat('l, j F Y')">
    <x-slot:actions>
        <x-booking-status :status="$booking->status" class="self-center" />
        @if ($booking->status === BookingStatus::Confirmed && $booking->event_date->isPast())
            <form method="POST" action="{{ route('vendor.bookings.complete', $booking) }}">
                @csrf
                <button type="submit" class="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">Tandakan selesai</button>
            </form>
        @endif
    </x-slot:actions>

    {{-- resources/js/components/vendor/VendorBookingDetail.vue --}}
    <div data-vue="vendor-booking-detail" data-props="{{ json_encode($props) }}"></div>
</x-layouts.vendor>
