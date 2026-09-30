<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\BookingStatus;
use App\Enums\DepositType;
use App\Enums\PriceUnit;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\VendorBookingSetting;
use App\Models\VendorUnavailableDate;
use App\Support\Herepay\HerepayGateway;
use App\Support\OnlineBookingSettings;
use App\Support\PaymentSettings;
use App\Support\VendorAvailability;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * A Pro vendor's calendar and online booking on one page: which days are
 * taken and closed, whether couples can book online (and the one thing to
 * fix if not), the rules they book under, where the deposit goes, and the
 * Google Calendar that closes busy days by itself. The forms still post to
 * UnavailableDateController and BookingSettingsController.
 */
class CalendarController extends Controller
{
    public function index(Request $request, HerepayGateway $herepay, OnlineBookingSettings $site, PaymentSettings $payments): View
    {
        $vendor = $request->user()->vendor;
        $availability = VendorAvailability::for($vendor);
        $settings = $availability->settings();
        $state = $availability->onlineState();
        $channel = $availability->paymentChannel();
        $hasRules = $vendor->bookingSettings()->exists();
        $hasPackages = $vendor->packages()->where('is_active', true)->exists();
        $freshDays = $site->calendarFreshDays();

        return view('vendor.calendar.index', [
            'props' => VueProps::for([
                'status' => [
                    'open' => $state->isOpen(),
                    'label' => $state->label(),
                    'confirmed' => $settings->calendar_confirmed_at?->diffForHumans(),
                    'freshDays' => $freshDays,
                    'confirmUrl' => route('vendor.booking-settings.calendar'),
                    'publicUrl' => route('vendors.show', $vendor).'#hubungi',
                ],
                'calendar' => [
                    'storeUrl' => route('vendor.availability.store'),
                    'today' => today()->toDateString(),
                    'capacity' => max(1, (int) $settings->max_per_day),
                    'closed' => $vendor->unavailableDates()
                        ->whereDate('date', '>=', today())
                        ->orderBy('date')
                        ->get()
                        ->map(fn (VendorUnavailableDate $date): array => [
                            'id' => $date->id,
                            'date' => $date->date->toDateString(),
                            'label' => $date->date->translatedFormat('D, j M Y'),
                            'reason' => $date->reason,
                            'slots' => $date->slots,
                            'imported' => $date->source === VendorUnavailableDate::SOURCE_ICAL,
                            'destroy_url' => $date->source === VendorUnavailableDate::SOURCE_MANUAL ? route('vendor.availability.destroy', $date) : null,
                        ])->values(),
                    'booked' => $vendor->bookings()
                        ->with('user')
                        ->whereIn('status', [BookingStatus::PendingPayment, BookingStatus::Confirmed])
                        ->whereDate('event_date', '>=', today())
                        ->orderBy('event_date')
                        ->get()
                        ->map(fn (Booking $booking): array => [
                            'date' => $booking->event_date->toDateString(),
                            'label' => $booking->event_date->translatedFormat('D, j M Y'),
                            'reference' => $booking->reference,
                            'customer' => $booking->user?->name,
                            'url' => route('vendor.bookings.show', $booking),
                            'status_label' => $booking->status->label(),
                            'status_tone' => $booking->status->tone(),
                        ])->values(),
                ],
                // The four steps to online booking, each done or not.
                'steps' => [
                    'deposit' => $channel !== null,
                    'rules' => $hasRules,
                    'calendar' => $settings->calendarIsFresh($freshDays),
                    'live' => (bool) $settings->enabled,
                ],
                'online' => [
                    'enabled' => (bool) $settings->enabled,
                    'toggleUrl' => route('vendor.booking-settings.toggle'),
                    'hasPackages' => $hasPackages,
                    'packagesUrl' => route('vendor.packages.index'),
                ],
                'rules' => [
                    'url' => route('vendor.booking-settings.update'),
                    'deposit_type' => old('deposit_type', $settings->deposit_type->value),
                    'deposit_value' => (float) old('deposit_value', $settings->deposit_value),
                    'weekdays' => array_map('intval', old('available_weekdays', $settings->weekdays())),
                    'max_per_day' => (int) old('max_per_day', $settings->max_per_day),
                    'min_lead_days' => (int) old('min_lead_days', $settings->min_lead_days),
                    'max_advance_months' => (int) old('max_advance_months', $settings->max_advance_months),
                    'deposit_terms' => old('deposit_terms', $settings->deposit_terms),
                    'depositTypes' => array_map(fn (DepositType $type): array => ['value' => $type->value, 'label' => $type->label()], DepositType::cases()),
                    'weekdayOptions' => array_map(fn (int $day): array => [
                        'value' => $day,
                        'label' => now()->startOfWeek()->addDays($day - 1)->translatedFormat('D'),
                        'full' => now()->startOfWeek()->addDays($day - 1)->translatedFormat('l'),
                    ], VendorBookingSetting::ALL_WEEKDAYS),
                    'maxPerDay' => VendorBookingSetting::MAX_PER_DAY,
                    'examplePrice' => (float) ($vendor->packages()->where('is_active', true)->min('price') ?? 3000),
                    'perPax' => $vendor->price_unit === PriceUnit::Pax,
                ],
                'deposit' => [
                    'channel' => $channel?->value,
                    'registerUrl' => config('services.herepay.register_url'),
                    'keysGuideUrl' => config('services.herepay.keys_guide_url'),
                    'manualOffered' => $payments->manualTransferEnabled(),
                    'manualInstructions' => old('manual_instructions', $settings->manual_instructions),
                    'manualUrl' => route('vendor.booking-settings.manual'),
                    'connected' => $settings->hasHerepay(),
                    'environment' => $herepay->environment() ?? '—',
                    'verified' => $settings->herepay_verified_at?->translatedFormat('j M Y'),
                    'connectUrl' => route('vendor.booking-settings.herepay.connect'),
                    'disconnectUrl' => route('vendor.booking-settings.herepay.disconnect'),
                ],
                'ical' => [
                    'connected' => filled($settings->ical_url),
                    'masked' => $settings->maskedIcalUrl(),
                    'synced' => $settings->ical_synced_at?->diffForHumans(),
                    'error' => $settings->ical_error ? __('pages.booking_settings.ical_errors.'.$settings->ical_error) : null,
                    'connectUrl' => route('vendor.booking-settings.ical.connect'),
                    'syncUrl' => route('vendor.booking-settings.ical.sync'),
                    'disconnectUrl' => route('vendor.booking-settings.ical.disconnect'),
                ],
            ]),
        ]);
    }
}
