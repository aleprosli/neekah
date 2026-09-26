<?php

namespace App\Http\Controllers\Payments;

use App\Enums\BookingStatus;
use App\Enums\CameraTier;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Support\Payments\PaymentDocument;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/**
 * Where every payment lands once the payer is back from the gateway: the
 * outcome, what it bought and its receipt; and the receipt (or, unpaid, the
 * invoice) as a printable page. The payer, whoever the payment concerns and
 * the admin may open both (PaymentPolicy).
 */
class PaymentController extends Controller
{
    public function show(Request $request, Payment $payment): View
    {
        Gate::authorize('view', $payment);

        $document = PaymentDocument::for($payment);
        $user = $request->user();
        $state = self::state($payment);

        return view('payments.show', [
            'payment' => $payment,
            'document' => $document,
            'state' => $state,
            'layout' => self::layoutFor($user),
            'message' => $this->message($payment, $state),
            'next' => $this->next($payment, $state, $user),
            'payer' => PaymentDocument::payerOf($payment),
        ]);
    }

    public function document(Payment $payment): View
    {
        Gate::authorize('view', $payment);

        return view('payments.document', [
            'payment' => $payment,
            'document' => PaymentDocument::for($payment),
        ]);
    }

    /** paid, waiting, failed or refunded. */
    public static function state(Payment $payment): string
    {
        return match ($payment->status) {
            PaymentStatus::Paid => 'paid',
            PaymentStatus::Refunded => 'refunded',
            PaymentStatus::Failed, PaymentStatus::Expired => 'failed',
            PaymentStatus::Pending, PaymentStatus::AwaitingVerification => 'waiting',
        };
    }

    /** The dashboard of whoever is looking, as the account page does. */
    public static function layoutFor(User $user): string
    {
        return 'layouts.'.match (true) {
            $user->isAdmin() => 'admin',
            $user->isVendor() => 'vendor',
            default => 'customer',
        };
    }

    /** One sentence on what this outcome means for what they bought. */
    private function message(Payment $payment, string $state): string
    {
        $reference = ['reference' => $payment->reference];

        if ($state === 'failed' || $state === 'refunded') {
            return __("pages.payment_page.{$state}_body", $reference);
        }

        if ($state === 'waiting') {
            return $payment->isAwaitingVerification()
                ? __('pages.payment_page.waiting_manual', ['vendor' => $payment->vendor?->name ?? '—'])
                : __('pages.payment_page.waiting_body');
        }

        return match ($payment->purpose) {
            PaymentPurpose::VendorPro => __('pages.payment_page.paid_pro', [
                'date' => Carbon::parse($payment->detail('ends_at') ?? $payment->vendor?->pro_until)->translatedFormat('j F Y'),
            ]),
            PaymentPurpose::BoostTokens => __('pages.payment_page.paid_boost', ['count' => (int) $payment->detail('tokens', 0)]),
            PaymentPurpose::Kenangan => __('pages.payment_page.paid_kenangan', [
                'tier' => CameraTier::tryFrom((string) $payment->detail('tier'))?->label() ?? '',
            ]),
            PaymentPurpose::Booking => __($payment->booking?->status === BookingStatus::Cancelled ? 'pages.payment_page.paid_booking_lapsed' : 'pages.payment_page.paid_booking', [
                'vendor' => $payment->booking?->vendor?->name ?? '—',
                'date' => $payment->booking?->event_date?->translatedFormat('j F Y') ?? '—',
            ]),
        };
    }

    /**
     * The one thing to do next: use what was bought, or try again.
     *
     * @return array{label: string, url: string}
     */
    private function next(Payment $payment, string $state, User $user): array
    {
        if ($user->isAdmin()) {
            return ['label' => __('pages.payment_page.cta_admin'), 'url' => route('admin.payments.show', $payment)];
        }

        $url = match ($payment->purpose) {
            PaymentPurpose::VendorPro => route('vendor.pro.index'),
            PaymentPurpose::BoostTokens => route('vendor.boost.index'),
            PaymentPurpose::Kenangan => $payment->album && $state === 'paid' ? route('camera.album', $payment->album) : route('camera.index'),
            PaymentPurpose::Booking => route('bookings.show', $payment->booking),
        };

        $label = $state === 'failed' && $payment->purpose !== PaymentPurpose::Booking
            ? __('pages.payment_page.retry')
            : __('pages.payment_page.cta_'.$payment->purpose->value);

        return ['label' => $label, 'url' => $url];
    }
}
