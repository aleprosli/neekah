<?php

namespace App\Http\Controllers\Payments;

use App\Actions\StartPayment;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Support\Herepay\HerepayGateway;
use App\Support\Payments\PaymentDocument;
use App\Support\Payments\PaymentGateways;
use App\Support\PhoneNumber;
use App\Support\Seo;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

/**
 * The order confirmation every online payment passes through before the
 * bank: what is being paid for, to whom, by whom and how much, and the bank
 * to pay with, from the gateway's live list. A page of its own, without the
 * dashboard around it. Confirming starts the payment with the gateway and
 * hands the payer the form that takes them to their bank.
 */
class CheckoutController extends Controller
{
    public function show(Request $request, Payment $payment, PaymentGateways $gateways, Seo $seo): View|RedirectResponse
    {
        Gate::authorize('pay', $payment);

        if (! self::isOpen($payment) || ! $gateways->has($payment->gateway)) {
            return redirect()->route('payments.show', $payment);
        }

        $seo->noindex();

        try {
            $banks = $gateways->for($payment->gateway)->channels($payment);
        } catch (Throwable $exception) {
            report($exception);
            $banks = [];
        }

        $document = PaymentDocument::for($payment);
        $payer = $request->user();

        return view('payments.checkout', [
            'payment' => $payment,
            'document' => $document,
            'issuer' => $document->issuer(),
            'payer' => $payer,
            'needsPhone' => blank($payer->phone),
            'banks' => $banks,
            'back' => PaymentController::originOf($payment),
        ]);
    }

    public function store(Request $request, Payment $payment, StartPayment $start): View|RedirectResponse
    {
        Gate::authorize('pay', $payment);

        if (! self::isOpen($payment)) {
            return redirect()->route('payments.show', $payment);
        }

        $payer = $request->user();
        $validated = $request->validate([
            'payment_method' => ['required', 'string', Rule::in(HerepayGateway::METHODS)],
            'bank_code' => ['required', 'string', 'regex:/^[A-Za-z0-9]{3,20}$/'],
            'phone' => [Rule::requiredIf(blank($payer->phone)), 'nullable', 'string', 'max:30', self::malaysianPhone()],
        ], attributes: [
            'payment_method' => __('pages.payment_checkout.bank'),
            'bank_code' => __('pages.payment_checkout.bank'),
            'phone' => __('fields.nombor_telefon'),
        ]);

        if (blank($payer->phone) && filled($validated['phone'] ?? null)) {
            $payer->update(['phone' => $validated['phone']]);
        }

        try {
            $redirect = $start->handle($payment, $payer, $validated['payment_method'], $validated['bank_code']);
        } catch (RuntimeException $exception) {
            report($exception);

            return back()->withInput()->withErrors(['bank_code' => __('flash.payment.initiate_failed')]);
        }

        return view('payments.redirect', ['redirect' => $redirect, 'payment' => $payment]);
    }

    /**
     * Still waiting to be paid online: not settled, not run out, and for a
     * deposit, the date still held for the couple.
     */
    public static function isOpen(Payment $payment): bool
    {
        return $payment->status === PaymentStatus::Pending
            && $payment->isOnline()
            && ($payment->expires_at === null || $payment->expires_at->isFuture())
            && ($payment->purpose !== PaymentPurpose::Booking || (bool) $payment->booking?->isHeld());
    }

    private static function malaysianPhone(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (filled($value) && PhoneNumber::normalise((string) $value) === null) {
                $fail(__('validation.custom.malaysian_phone'));
            }
        };
    }
}
