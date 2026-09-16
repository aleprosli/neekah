@php use App\Enums\BookingStatus; @endphp

<x-layouts.vendor :title="'Booking '.$booking->reference" :heading="$booking->reference" :subheading="$booking->user->name.' · '.$booking->event_date->translatedFormat('l, j F Y')">
    <x-slot:actions>
        <x-booking-status :status="$booking->status" class="self-center" />
        @if ($booking->status === BookingStatus::Confirmed && $booking->event_date->isPast())
            {{-- resources/js/components/ui/UiConfirm.vue --}}
            <div data-vue="ui-confirm" data-props="@vueProps([
                'action' => route('vendor.bookings.complete', $booking),
                'csrf' => csrf_token(),
                'title' => 'Tandakan majlis ini selesai?',
                'message' => 'Pelanggan akan dijemput menulis review, dan booking ini tidak boleh dikembalikan ke status Confirmed.',
                'confirmLabel' => 'Ya, majlis selesai',
                'label' => 'Tandakan selesai',
                'triggerClass' => 'rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700',
            ])"></div>
        @endif
    </x-slot:actions>

    {{-- resources/js/components/vendor/VendorBookingDetail.vue --}}
    <div data-vue="vendor-booking-detail" data-props="@vueProps($props)"></div>
</x-layouts.vendor>
