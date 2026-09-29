<?php

namespace App\Actions;

use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\User;
use App\Support\Payments\PaymentGateways;
use App\Support\Payments\PaymentLink;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Throwable;

/**
 * Ask the payment's gateway for a link and return where to send the payer.
 * The same for every purpose: the callback comes back to one webhook per
 * gateway, the payer to one return address, and both what we asked for and
 * what the gateway answered are kept in the payment's events.
 */
class StartPayment
{
    public function __construct(private PaymentGateways $gateways) {}

    /**
     * @throws RuntimeException when the gateway refused; the payment is then marked failed
     */
    public function handle(Payment $payment, User $payer): string
    {
        $gateway = $this->gateways->for($payment->gateway);
        $link = new PaymentLink(
            title: $this->title($payment),
            description: __('pages.payments.link_description', ['reference' => $payment->reference]),
            amount: (float) $payment->amount,
            expiresAt: $payment->expires_at ?? now()->addDay(),
            callbackUrl: url(URL::signedRoute('payments.webhook', ['gateway' => $gateway->name(), 'ref' => $payment->reference], absolute: false)),
            returnUrl: route('payments.return', $payment),
            payerName: $payer->name,
            payerEmail: $payer->email,
            payerPhone: $payer->phone,
        );
        $request = ['title' => $link->title, 'amount' => $link->amount, 'expires_at' => $link->expiresAt->toIso8601String(), 'callback_url' => $link->callbackUrl, 'return_url' => $link->returnUrl];

        try {
            $created = $gateway->createLink($payment, $link);
        } catch (Throwable $exception) {
            $payment->update(['status' => PaymentStatus::Failed]);
            PaymentEvent::record($payment, $gateway->name(), PaymentEvent::LINK_FAILED, ['request' => $request], ['error' => $exception->getMessage()], outcome: 'failed');

            throw new RuntimeException("The gateway refused a link for {$payment->reference}.", previous: $exception);
        }

        $payment->update(['payment_url' => $created['url'], 'expires_at' => $link->expiresAt]);
        PaymentEvent::record($payment, $gateway->name(), PaymentEvent::LINK_CREATED, ['request' => $request, 'response' => $created['response']]);

        return $created['url'];
    }

    private function title(Payment $payment): string
    {
        return match ($payment->purpose) {
            PaymentPurpose::Booking => __('pages.online_booking.herepay_title', [
                'vendor' => $payment->booking->vendor->name,
                'date' => $payment->booking->event_date->translatedFormat('j M Y'),
            ]),
            PaymentPurpose::VendorPro => __('pages.pro.herepay_title', [
                'plan' => __('enums.vendor_plan.'.$payment->detail('plan')),
                'vendor' => $payment->vendor->name,
            ]),
            PaymentPurpose::BoostTokens => __('pages.boost.herepay_title', [
                'count' => $payment->detail('tokens'),
                'vendor' => $payment->vendor->name,
            ]),
            PaymentPurpose::Kenangan => __('pages.camera.herepay_title', [
                'tier' => __('enums.camera_tier.'.$payment->detail('tier')),
                'wedding' => $payment->detail('album_title') ?: $payment->album?->displayTitle() ?? $payment->wedding->title,
            ]),
        };
    }
}
