<?php

namespace App\Http\Controllers\Vendor;

use App\Actions\ImportVendorIcal;
use App\Http\Controllers\Controller;
use App\Http\Requests\ConnectHerepayRequest;
use App\Http\Requests\UpdateBookingSettingsRequest;
use App\Models\VendorUnavailableDate;
use App\Support\Herepay\HerepayCredentials;
use App\Support\Herepay\HerepayGateway;
use App\Support\VendorAvailability;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * What the online booking forms on the calendar page (CalendarController)
 * post to: the rules couples book under, where the deposit goes (their own
 * Herepay account, or a transfer to their bank) and the Google Calendar.
 * The Herepay keys are written, never shown.
 */
class BookingSettingsController extends Controller
{
    public function update(UpdateBookingSettingsRequest $request): RedirectResponse
    {
        $vendor = $request->user()->vendor;

        // Saving the rules is looking at the calendar too.
        $vendor->bookingSettings()->updateOrCreate([], [...$request->settings(), 'calendar_confirmed_at' => now()]);

        return back()->with('status', __('flash.vendor.booking_settings_saved'));
    }

    /**
     * Turn online booking on or off. Turning it on is refused while couples
     * would have no way to pay the deposit, so the switch never promises a
     * form that cannot take a booking.
     */
    public function toggle(Request $request): RedirectResponse
    {
        $enabled = $request->validate(['enabled' => ['required', 'boolean']])['enabled'];
        $vendor = $request->user()->vendor;

        if ($enabled && VendorAvailability::for($vendor)->paymentChannel() === null) {
            return back()->withErrors(['enabled' => __('flash.vendor.online_needs_payment')]);
        }

        $vendor->bookingSettings()->updateOrCreate([], ['enabled' => (bool) $enabled]);

        return back()->with('status', $enabled ? __('flash.vendor.online_on') : __('flash.vendor.online_off'));
    }

    /**
     * The bank details for a transferred deposit: the fallback for a vendor
     * without Herepay yet.
     */
    public function manual(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            ['manual_instructions' => ['nullable', 'string', 'max:1000']],
            attributes: ['manual_instructions' => __('fields.butiran_bank')],
        );

        $request->user()->vendor->bookingSettings()->updateOrCreate([], ['manual_instructions' => $validated['manual_instructions'] ?? null]);

        return back()->with('status', __('flash.vendor.bank_details_saved'));
    }

    /**
     * Keys are kept only once Herepay has accepted them: a test link is made
     * with them first, so a typo never quietly breaks every booking.
     */
    public function connect(ConnectHerepayRequest $request, HerepayGateway $gateway): RedirectResponse
    {
        $credentials = new HerepayCredentials(
            trim($request->validated('herepay_secret_key')),
            trim($request->validated('herepay_private_key')),
            trim((string) $request->validated('herepay_api_key')),
        );

        if (! $gateway->testConnection($credentials)) {
            return back()->withErrors(['herepay_secret_key' => __('flash.vendor.herepay_test_failed')]);
        }

        $request->user()->vendor->bookingSettings()->updateOrCreate([], [
            'herepay_secret_key' => $credentials->secretKey,
            'herepay_private_key' => $credentials->privateKey,
            'herepay_api_key' => $credentials->apiKey ?: null,
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

    /**
     * Link the vendor's Google Calendar. It is imported straight away, so a
     * wrong address is caught here rather than an hour later.
     */
    public function connectIcal(Request $request, ImportVendorIcal $import): RedirectResponse
    {
        $validated = $request->validate(
            ['ical_url' => ['required', 'string', 'max:2048', 'url:https']],
            attributes: ['ical_url' => __('fields.ical_url')],
        );

        $settings = $request->user()->vendor->bookingSettings()->firstOrCreate();
        $previous = $settings->ical_url;
        $settings->update(['ical_url' => trim($validated['ical_url'])]);

        try {
            $count = $import->handle($settings);
        } catch (RuntimeException $exception) {
            $settings->update(['ical_url' => $previous]);

            return back()->withErrors(['ical_url' => __('pages.booking_settings.ical_errors.'.$exception->getMessage())]);
        }

        return back()->with('status', __('flash.vendor.ical_connected', ['count' => $count]));
    }

    public function syncIcal(Request $request, ImportVendorIcal $import): RedirectResponse
    {
        $settings = $request->user()->vendor->bookingSettings;
        abort_if(blank($settings?->ical_url), 404);

        try {
            $count = $import->handle($settings);
        } catch (RuntimeException $exception) {
            $settings->update(['ical_error' => $exception->getMessage()]);

            return back()->withErrors(['ical_url' => __('pages.booking_settings.ical_errors.'.$exception->getMessage())]);
        }

        return back()->with('status', __('flash.vendor.ical_synced', ['count' => $count]));
    }

    /** Unlink the calendar and reopen the days it had closed; days closed by hand stay. */
    public function disconnectIcal(Request $request): RedirectResponse
    {
        $vendor = $request->user()->vendor;

        $vendor->bookingSettings?->update(['ical_url' => null, 'ical_synced_at' => null, 'ical_error' => null, 'ical_failures' => 0]);
        $vendor->unavailableDates()->where('source', VendorUnavailableDate::SOURCE_ICAL)->delete();

        return back()->with('status', __('flash.vendor.ical_disconnected'));
    }
}
