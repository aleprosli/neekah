{{-- Where Herepay sends the couple back after paying. It reads our own
     records: the callback confirms the booking, usually within seconds, so a
     "waiting" page looks again on its own for a minute. --}}
<x-layouts.customer :title="__('pages.online_booking.done_title')" :heading="$booking->vendor->name" :subheading="$booking->package_name.' · '.$booking->event_date->translatedFormat('l, j F Y')">
    @if ($state === 'waiting' && request()->integer('cuba') < 10)
        <script>
            setTimeout(() => {
                const url = new URL(window.location.href);
                url.searchParams.set('cuba', String({{ request()->integer('cuba') + 1 }}));
                window.location.replace(url.toString());
            }, 6000);
        </script>
    @endif

    <section class="mx-auto flex max-w-lg flex-col items-center gap-4 rounded-2xl border border-line bg-surface-raised px-6 py-10 text-center">
        <span class="text-4xl" aria-hidden="true">{{ ['confirmed' => '🎉', 'waiting' => '⏳', 'lapsed' => '⚠️'][$state] }}</span>
        <h2 class="font-display text-2xl font-semibold">{{ __("pages.online_booking.done_{$state}_title") }}</h2>
        <p class="text-sm text-ink-muted">{{ __("pages.online_booking.done_{$state}_body", ['vendor' => $booking->vendor->name, 'date' => $booking->event_date->translatedFormat('j M Y')]) }}</p>
        <a href="{{ route('bookings.show', $booking) }}" class="mt-2 rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ __('notifications.actions.view_booking') }}</a>
    </section>
</x-layouts.customer>
