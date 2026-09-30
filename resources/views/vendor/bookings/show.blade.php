@php use App\Enums\BookingStatus; @endphp

<x-layouts.vendor :title="__('pages.online_booking.booking_title', ['reference' => $booking->reference])" :heading="$booking->reference" :subheading="$booking->user->name.' · '.$booking->event_date->translatedFormat('l, j F Y')">
    <x-slot:actions>
        <x-booking-status :status="$booking->status" class="self-center" />
        @if ($booking->status === BookingStatus::Confirmed && $booking->event_date->isPast())
            {{-- resources/js/components/ui/UiConfirm.vue --}}
            <div data-vue="ui-confirm" data-props="@vueProps([
                'action' => route('vendor.bookings.complete', $booking),
                'csrf' => csrf_token(),
                'title' => __('flash.confirm.complete_title'),
                'message' => __('flash.confirm.complete_message'),
                'confirmLabel' => __('flash.confirm.yes_complete'),
                'label' => __('flash.confirm.complete_label'),
                'triggerClass' => 'rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700',
            ])"></div>
        @endif
    </x-slot:actions>

    {{-- An online booking: where it came from, the deposit it was made with,
         and how long its date is held. --}}
    @if ($booking->isOnline())
        <section class="mb-6 flex flex-wrap items-center gap-x-6 gap-y-2 rounded-2xl border border-line bg-surface-raised px-5 py-4 text-sm">
            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-800">{{ $booking->source->label() }}</span>
            <span>{{ __('pages.online_booking.vendor_deposit', ['amount' => 'RM'.number_format((float) $booking->deposit_amount, 2), 'channel' => $booking->payment_mode?->label() ?? '—']) }}</span>
            @if ($booking->isHeld())
                <span class="text-amber-800">{{ __('pages.online_booking.vendor_held_until', ['deadline' => $booking->hold_expires_at->translatedFormat('j M Y, g:i A')]) }}</span>
            @endif
            @if ($booking->cancelled_reason)
                <span class="text-ink-muted">{{ $booking->cancelled_reason->label() }}</span>
            @endif
        </section>
    @endif

    {{-- resources/js/components/vendor/VendorBookingDetail.vue --}}
    <div data-vue="vendor-booking-detail" data-props="@vueProps($props)"></div>

    {{-- A deposit on a booking that no longer stands is owed back: the vendor
         records it once they have refunded the couple. --}}
    @php
        $toRefund = $booking->status === BookingStatus::Cancelled ? $booking->payments->filter->isPaid() : collect();
    @endphp
    @if ($toRefund->isNotEmpty())
        <section class="mt-6 flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm">
            <p class="font-semibold">{{ __('pages.online_booking.refund_title') }}</p>
            <p class="text-ink-muted">{{ __('pages.online_booking.refund_body') }}</p>
            @foreach ($toRefund as $payment)
                <form method="POST" action="{{ route('vendor.bookings.payments.refunded', [$booking, $payment]) }}" class="flex flex-wrap items-center justify-between gap-3">
                    @csrf
                    <span>{{ $payment->reference }} · RM{{ number_format((float) $payment->amount, 2) }}</span>
                    <button type="submit" class="rounded-full border border-amber-300 bg-surface-raised px-4 py-2 text-sm font-medium transition hover:border-brand-400">{{ __('pages.online_booking.mark_refunded') }}</button>
                </form>
            @endforeach
        </section>
    @endif

    {{-- Calling it off is an ordinary form with a reason, folded away so it is
         never hit by accident. --}}
    @can('vendorCancel', $booking)
        <details class="mt-6 rounded-2xl border border-line bg-surface-raised">
            <summary class="cursor-pointer list-none px-5 py-4 text-sm font-medium text-ink-muted [&::-webkit-details-marker]:hidden">{{ __('pages.online_booking.vendor_cancel_title') }}</summary>
            <form method="POST" action="{{ route('vendor.bookings.cancel', $booking) }}" class="flex flex-col gap-4 border-t border-line p-5">
                @csrf
                <p class="text-sm text-ink-muted">
                    {{ $booking->paidAmount() > 0 ? __('pages.online_booking.vendor_cancel_paid', ['amount' => 'RM'.number_format($booking->paidAmount(), 2)]) : __('pages.online_booking.vendor_cancel_unpaid') }}
                </p>
                <label class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium">{{ __('pages.online_booking.vendor_cancel_reason') }}</span>
                    <textarea name="reason" rows="2" maxlength="200" required class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">{{ old('reason') }}</textarea>
                </label>
                <div><button type="submit" class="rounded-full border border-brand-600 px-5 py-2.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-50">{{ __('pages.online_booking.vendor_cancel_submit') }}</button></div>
            </form>
        </details>
    @endcan
</x-layouts.vendor>
