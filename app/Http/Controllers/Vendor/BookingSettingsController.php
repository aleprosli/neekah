<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\DepositType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ConnectHerepayRequest;
use App\Http\Requests\UpdateBookingSettingsRequest;
use App\Models\VendorBookingSetting;
use App\Support\Herepay\DepositGateway;
use App\Support\Herepay\HerepayClient;
use App\Support\Herepay\HerepayCredentials;
use App\Support\OnlineBookingSettings;
use App\Support\VendorAvailability;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * A Pro vendor's online booking: whether it is live and why not, the rules
 * couples book under, and where the deposit goes (their own Herepay account,
 * or a transfer to their bank). The Herepay keys are written, never shown.
 */
class BookingSettingsController extends Controller
{
    public function edit(Request $request, HerepayClient $herepay, OnlineBookingSettings $site): View
    {
        $vendor = $request->user()->vendor;
        $availability = VendorAvailability::for($vendor);
        $settings = $availability->settings();

        return view('vendor.booking-settings.edit', [
            'vendor' => $vendor,
            'settings' => $settings,
            'state' => $availability->onlineState(),
            'channel' => $availability->paymentChannel(),
            'depositTypes' => DepositType::cases(),
            'weekdays' => VendorBookingSetting::ALL_WEEKDAYS,
            'environment' => $herepay->environment(),
            'freshDays' => $site->calendarFreshDays(),
            'examplePrice' => (float) ($vendor->packages()->where('is_active', true)->min('price') ?? 3000),
        ]);
    }

    public function update(UpdateBookingSettingsRequest $request): RedirectResponse
    {
        $vendor = $request->user()->vendor;

        // Saving the rules is looking at the calendar too.
        $vendor->bookingSettings()->updateOrCreate([], [...$request->settings(), 'calendar_confirmed_at' => now()]);

        return back()->with('status', __('flash.vendor.booking_settings_saved'));
    }

    /**
     * Keys are kept only once Herepay has accepted them: a test link is made
     * with them first, so a typo never quietly breaks every booking.
     */
    public function connect(ConnectHerepayRequest $request, DepositGateway $gateway): RedirectResponse
    {
        $credentials = new HerepayCredentials(trim($request->validated('herepay_secret_key')), trim($request->validated('herepay_private_key')));

        if (! $gateway->testConnection($credentials)) {
            return back()->withErrors(['herepay_secret_key' => __('flash.vendor.herepay_test_failed')]);
        }

        $request->user()->vendor->bookingSettings()->updateOrCreate([], [
            'herepay_secret_key' => $credentials->secretKey,
            'herepay_private_key' => $credentials->privateKey,
            'herepay_connected_at' => now(),
            'herepay_verified_at' => null,
        ]);

        return back()->with('status', __('flash.vendor.herepay_connected'));
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $request->user()->vendor->bookingSettings?->update([
            'herepay_secret_key' => null,
            'herepay_private_key' => null,
            'herepay_connected_at' => null,
            'herepay_verified_at' => null,
        ]);

        return back()->with('status', __('flash.vendor.herepay_disconnected'));
    }

    /** "My calendar is up to date": keeps online booking open for another stretch. */
    public function confirmCalendar(Request $request): RedirectResponse
    {
        $request->user()->vendor->bookingSettings()->updateOrCreate([], ['calendar_confirmed_at' => now()]);

        return back()->with('status', __('flash.vendor.calendar_confirmed'));
    }
}
