<?php

namespace App\Support\Herepay;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

/**
 * Booking deposits on the vendor's own Herepay account. The money goes to the
 * vendor; Neekah only makes the link and reads the callback.
 */
class HerepayDepositClient implements DepositGateway
{
    public function __construct(private HerepayTransport $transport) {}

    public function createDepositLink(Payment $payment, User $payer, HerepayCredentials $credentials): string
    {
        $booking = $payment->booking;

        return $this->transport->createLink($credentials, [
            'title' => Str::limit(__('pages.online_booking.herepay_title', ['vendor' => $booking->vendor->name, 'date' => $booking->event_date->translatedFormat('j M Y')]), 250),
            'amount' => round((float) $payment->amount, 2),
            'description' => e(__('pages.online_booking.herepay_description', ['package' => $booking->package_name, 'reference' => $booking->reference])),
            'usage_type' => 'single',
            'expires_at' => ($payment->expires_at ?? now()->addDay())->format('Y-m-d H:i:s'),
            'redirect_url' => route('bookings.payment.done', $booking),
            'callback_url' => url(URL::signedRoute('webhooks.herepay.booking', ['ref' => $payment->reference], absolute: false)),
            'payer_name' => Str::limit($payer->name, 250, ''),
            'payer_email' => $payer->email,
            'payer_phone' => $payer->phone ? Str::limit($payer->phone, 32, '') : null,
        ]);
    }

    public function parseCallback(Request $request, HerepayCredentials $credentials): ?array
    {
        return $this->transport->readCallback($request, $credentials);
    }

    /**
     * Herepay offers no "who am I" call, and only link creation is used, so the
     * test is a real link: RM1, single use, gone in ten minutes, never paid. A
     * link coming back proves the secret key; the private key is proved by the
     * first real callback.
     */
    public function testConnection(HerepayCredentials $credentials): bool
    {
        try {
            $this->transport->createLink($credentials, [
                'title' => __('pages.online_booking.herepay_test_title'),
                'amount' => 1.00,
                'usage_type' => 'single',
                'expires_at' => now()->addMinutes(10)->format('Y-m-d H:i:s'),
                'collect_phone' => false,
            ]);

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }
}
