<?php

namespace App\Actions;

use App\Enums\PaymentPurpose;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\User;
use App\Support\Payments\PaymentGateways;
use App\Support\Payments\PaymentRedirect;
use App\Support\Payments\PaymentRequest;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Throwable;

/**
 * Start a payment with the bank the payer picked on the confirmation page,
 * and return the form that takes them to it. The same for every purpose: the
 * callback comes back to one webhook per gateway, the payer to one return
 * address, and both what we asked for and what the gateway answered are kept
 * in the payment's events.
 */
class StartPayment
{
    public function __construct(private PaymentGateways $gateways) {}

    /**
     * @throws RuntimeException when the gateway refused; the payment stays pending, so another bank can be tried
     */
    public function handle(Payment $payment, User $payer, string $paymentMethod, string $bankCode): PaymentRedirect
    {
        $gateway = $this->gateways->for($payment->gateway);
        $request = new PaymentRequest(
            description: $this->title($payment).' · '.__('pages.payments.link_description', ['reference' => $payment->reference]),
            amount: (float) $payment->amount,
            callbackUrl: url(URL::signedRoute('payments.webhook', ['gateway' => $gateway->name(), 'ref' => $payment->reference], absolute: false)),
            returnUrl: route('payments.return', $payment),
            paymentMethod: $paymentMethod,
            bankCode: $bankCode,
            payerName: $payer->name,
            payerEmail: $payer->email,
            payerPhone: (string) $payer->phone,
        );
        $asked = [
            'amount' => $request->amount,
            'payment_method' => $paymentMethod,
            'bank' => $bankCode,
            'callback_url' => $request->callbackUrl,
            'return_url' => $request->returnUrl,
        ];

        try {
            $redirect = $gateway->initiate($payment, $request);
        } catch (Throwable $exception) {
            PaymentEvent::record($payment, $gateway->name(), PaymentEvent::INITIATE_FAILED, ['request' => $asked], ['error' => $exception->getMessage()], outcome: 'failed');

            throw new RuntimeException("The gateway refused to start {$payment->reference}.", previous: $exception);
        }

        // Our reference is the gateway's payment_code, so a requery can ask by
        // it before the callback or the return has told us anything.
        $payment->update(['gateway_reference' => $payment->gateway_reference ?? $payment->reference]);
        PaymentEvent::record($payment, $gateway->name(), PaymentEvent::INITIATED, ['request' => $asked, 'response' => ['action' => $redirect->action]]);

        return $redirect;
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
