<?php

namespace App\Http\Controllers;

use App\Actions\ConfirmOnlineDeposit;
use App\Actions\StartDepositPayment;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Support\Herepay\DepositGateway;
use App\Support\Herepay\HerepayCredentials;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;

/**
 * Herepay's callback for a booking deposit, paid on the vendor's own account.
 *
 * The order matters. The signed URL is checked first, because only then can
 * the reference in it be trusted to pick the vendor whose private key the
 * body's checksum is checked against. A checksum made with any other key,
 * Neekah's included, is refused.
 */
class HerepayBookingWebhookController extends Controller
{
    public function __invoke(Request $request, DepositGateway $gateway, ConfirmOnlineDeposit $confirm): Response
    {
        if (! $request->hasValidRelativeSignature()) {
            return response('Invalid signature', 403);
        }

        $payment = Payment::query()
            ->where('reference', (string) $request->query('ref'))
            ->where('gateway', StartDepositPayment::GATEWAY)
            ->with('booking.vendor.bookingSettings')
            ->first();

        if (! $payment) {
            return response('Unknown reference', 404);
        }

        $settings = $payment->booking->vendor->bookingSettingsOrDefault();
        $credentials = HerepayCredentials::forVendor($settings);

        if (! $credentials) {
            report(new RuntimeException("Herepay called back for {$payment->reference} but the vendor has disconnected Herepay."));

            return response('Account disconnected', 409);
        }

        $result = $gateway->parseCallback($request, $credentials);

        if ($result === null) {
            return response('Invalid checksum', 403);
        }

        // The first callback that verifies proves the vendor's private key.
        if ($settings->exists && $settings->herepay_verified_at === null) {
            $settings->update(['herepay_verified_at' => now()]);
        }

        if ($result['status'] === 'paid' && round($result['amount'], 2) < round((float) $payment->amount, 2)) {
            report(new RuntimeException("Herepay paid {$result['amount']} for deposit {$payment->reference}, which is {$payment->amount}."));

            return response('Amount mismatch', 422);
        }

        match ($result['status']) {
            'paid' => $confirm->handle($payment, $result['gateway_reference']),
            'failed' => $payment->status === PaymentStatus::Pending
                ? $payment->update(['status' => PaymentStatus::Failed, 'gateway_reference' => $result['gateway_reference']])
                : null,
            'pending' => null,
        };

        return response('OK');
    }
}
