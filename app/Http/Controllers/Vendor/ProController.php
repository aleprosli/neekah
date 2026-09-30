<?php

namespace App\Http\Controllers\Vendor;

use App\Actions\StartPayment;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\VendorPlan;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Support\Herepay\HerepayGateway;
use App\Support\ProSettings;
use App\Support\VendorAnalytics;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Neekah Pro from the vendor's side: what it gives, what it costs, whether it
 * is running, and paying for it.
 */
class ProController extends Controller
{
    public function index(Request $request, ProSettings $settings, HerepayGateway $gateway): View
    {
        $vendor = $request->user()->vendor;
        $analytics = new VendorAnalytics($vendor);

        return view('vendor.pro.index', [
            'vendor' => $vendor,
            'plans' => collect(VendorPlan::cases())->map(fn (VendorPlan $plan): array => [
                'plan' => $plan,
                'price' => $settings->price($plan),
            ]),
            'canCheckout' => $settings->isEnabled() && $gateway->isConfigured() && $vendor->isApproved(),
            // Unpaid checkouts are abandoned carts; the history is what happened.
            'history' => $vendor->proPayments()->whereNot('status', PaymentStatus::Pending)->limit(12)->get()
                ->map(fn (Payment $payment): array => [
                    'reference' => $payment->reference,
                    'plan' => VendorPlan::tryFrom((string) $payment->detail('plan'))?->label(),
                    'amount' => number_format((float) $payment->amount, 2),
                    'status' => $payment->status->label(),
                    'until' => $payment->detail('ends_at') ? Carbon::parse($payment->detail('ends_at'))->translatedFormat('j M Y') : null,
                    'url' => route('payments.show', $payment),
                    'document_url' => $payment->isPaid() ? route('payments.document', $payment) : null,
                ]),
            'totals' => $analytics->totals($vendor->isPro() ? VendorAnalytics::DAYS : VendorAnalytics::TEASER_DAYS),
            'daily' => $vendor->isPro() ? $analytics->dailyViews() : [],
        ]);
    }

    /**
     * Start a purchase and send the vendor to pay, on Neekah's account. The
     * price is stamped now, so the amount charged is the one they saw.
     */
    public function checkout(Request $request, ProSettings $settings, HerepayGateway $gateway, StartPayment $start): RedirectResponse
    {
        $validated = $request->validate([
            'plan' => ['required', Rule::enum(VendorPlan::class)],
        ]);

        $vendor = $request->user()->vendor;

        abort_unless($settings->isEnabled() && $gateway->isConfigured() && $vendor->isApproved(), 404);

        $plan = VendorPlan::from($validated['plan']);
        $payment = Payment::query()->create([
            'purpose' => PaymentPurpose::VendorPro,
            'vendor_id' => $vendor->id,
            'recorded_by' => $request->user()->id,
            'amount' => $settings->price($plan),
            'status' => PaymentStatus::Pending,
            'gateway' => $gateway->name(),
            'details' => ['plan' => $plan->value],
        ]);

        try {
            return redirect()->away($start->handle($payment, $request->user()));
        } catch (RuntimeException $exception) {
            report($exception);

            return back()->withErrors(['plan' => __('flash.vendor.pro_checkout_failed')]);
        }
    }

    /**
     * Where the vendor lands after paying. The gateway's callback, or its
     * signed return, is what activates Pro; this only reports it.
     */
    /** Links made before the shared payment page still land somewhere useful. */
    public function done(Request $request): RedirectResponse
    {
        $payment = $request->user()->vendor->proPayments()
            ->when($request->string('ref')->toString(), fn ($query, string $reference) => $query->where('reference', $reference))
            ->first();

        return $payment ? redirect()->route('payments.show', $payment) : redirect()->route('vendor.pro.index');
    }
}
