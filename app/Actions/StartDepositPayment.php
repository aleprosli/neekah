<?php

namespace App\Actions;

use App\Enums\DepositChannel;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Support\Herepay\DepositGateway;
use App\Support\Herepay\HerepayCredentials;
use RuntimeException;
use Throwable;

/**
 * Send a couple to pay the deposit on an online booking, on the vendor's own
 * Herepay account. A link still open is reused, so paying again never opens a
 * second one for the same hold.
 */
class StartDepositPayment
{
    /** The gateway reference a deposit payment is recorded under. */
    public const GATEWAY = 'herepay';

    public function __construct(private DepositGateway $gateway) {}

    /**
     * The URL to send the couple to.
     *
     * @throws RuntimeException when the booking cannot take a payment now, or Herepay refused the link
     */
    public function handle(Booking $booking, User $payer): string
    {
        if (! $booking->isHeld() || $booking->payment_mode !== DepositChannel::Herepay) {
            throw new RuntimeException("Booking {$booking->reference} is not waiting for an online deposit.");
        }

        $open = $booking->payments()
            ->where('gateway', self::GATEWAY)
            ->where('status', PaymentStatus::Pending)
            ->where('expires_at', '>', now())
            ->whereNotNull('payment_url')
            ->latest('id')
            ->first();

        if ($open) {
            return $open->payment_url;
        }

        $credentials = HerepayCredentials::forVendor($booking->vendor->bookingSettingsOrDefault())
            ?? throw new RuntimeException("Vendor {$booking->vendor_id} has no Herepay account connected.");

        $payment = $booking->payments()->create([
            'reference' => Payment::generateReference(),
            'recorded_by' => $payer->id,
            'amount' => $booking->deposit_amount,
            'method' => self::GATEWAY,
            'gateway' => self::GATEWAY,
            'status' => PaymentStatus::Pending,
            'expires_at' => $booking->hold_expires_at,
        ]);

        try {
            $url = $this->gateway->createDepositLink($payment->setRelation('booking', $booking), $payer, $credentials);
        } catch (Throwable $exception) {
            $payment->update(['status' => PaymentStatus::Failed]);

            throw new RuntimeException('Herepay refused the deposit link.', previous: $exception);
        }

        $payment->update(['payment_url' => $url]);

        return $url;
    }
}
