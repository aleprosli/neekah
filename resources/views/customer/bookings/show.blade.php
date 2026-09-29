<x-layouts.customer :title="__('pages.online_booking.booking_title', ['reference' => $booking->reference])" :heading="$booking->vendor->name" :subheading="$booking->package_name.' · '.$booking->event_date->translatedFormat('l, j F Y')">
    <x-slot:actions>
        <x-booking-status :status="$booking->status" class="self-center" />
        <a href="{{ route('vendors.show', $booking->vendor) }}" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">{{ __('pages.dash.lihat_vendor') }}</a>
    </x-slot:actions>

    {{-- An online booking waiting for its deposit: how much, by when, and how.
         The date is released when the hold runs out unpaid. --}}
    @if ($deposit)
        <section @class([
            'mb-6 flex flex-col gap-4 rounded-2xl border p-5 sm:flex-row sm:items-center sm:justify-between',
            'border-amber-200 bg-amber-50' => $deposit['held'],
            'border-line bg-surface-raised' => ! $deposit['held'],
        ])>
            <div class="min-w-0">
                <p class="text-sm font-semibold">
                    @if ($deposit['waiting'])
                        {{ __('pages.online_booking.deposit_waiting_vendor') }}
                    @elseif ($deposit['held'])
                        {{ __('pages.online_booking.deposit_due', ['amount' => $deposit['amount'], 'deadline' => $deposit['deadline']]) }}
                    @else
                        {{ __('pages.online_booking.deposit_lapsed') }}
                    @endif
                </p>
                <p class="mt-1 text-sm text-ink-muted">
                    {{ __('pages.online_booking.deposit_balance', ['balance' => $deposit['balance']]) }}
                    @if (! $deposit['online'] && $deposit['held'] && ! $deposit['waiting'])
                        {{ __('pages.online_booking.deposit_transfer_hint') }}
                    @endif
                </p>
            </div>
            @if ($deposit['pay_url'])
                <form method="POST" action="{{ $deposit['pay_url'] }}" class="shrink-0">
                    @csrf
                    <button type="submit" class="w-full rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 sm:w-auto">{{ __('pages.online_booking.pay_deposit', ['amount' => $deposit['amount']]) }}</button>
                </form>
            @endif
        </section>
    @endif

    {{-- resources/js/components/customer/CustomerBookingDetail.vue --}}
    <div data-vue="customer-booking-detail" data-props="@vueProps($props)"></div>
</x-layouts.customer>
