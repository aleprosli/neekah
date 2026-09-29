{{-- Online booking for this vendor, as an admin needs to see it: live or
     not and why, where deposits go, and when the calendar was last
     confirmed. Never the Herepay keys themselves. --}}
@php
    $availability = App\Support\VendorAvailability::for($vendor);
    $bookingSettings = $availability->settings();
@endphp
<section class="mt-8 flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-6 text-sm">
    <h2 class="font-display text-xl font-semibold">{{ __('pages.booking_settings.title') }}</h2>
    <dl class="grid gap-3 sm:grid-cols-3">
        <div>
            <dt class="text-xs text-ink-muted">{{ __('pages.online_booking.admin_state') }}</dt>
            <dd class="font-medium">{{ $availability->onlineState()->label() }}</dd>
        </div>
        <div>
            <dt class="text-xs text-ink-muted">{{ __('pages.online_booking.admin_channel') }}</dt>
            <dd class="font-medium">
                @if ($bookingSettings->hasHerepay())
                    {{ $bookingSettings->herepay_verified_at ? __('pages.online_booking.admin_herepay_verified') : __('pages.online_booking.admin_herepay_connected') }}
                @else
                    {{ $availability->paymentChannel()?->label() ?? '—' }}
                @endif
            </dd>
        </div>
        <div>
            <dt class="text-xs text-ink-muted">{{ __('pages.online_booking.admin_calendar') }}</dt>
            <dd class="font-medium">{{ $bookingSettings->calendar_confirmed_at?->translatedFormat('j M Y, g:i A') ?? '—' }}</dd>
        </div>
    </dl>
</section>
