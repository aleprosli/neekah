<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\SubscriptionStatus;
use App\Enums\VendorPlan;
use App\Http\Controllers\Controller;
use App\Models\VendorSubscription;
use App\Support\Herepay\PaymentLinkGateway;
use App\Support\ProSettings;
use App\Support\VendorAnalytics;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Neekah Pro from the vendor's side: what it gives, what it costs, whether it
 * is running, and paying for it.
 */
class ProController extends Controller
{
    public function index(Request $request, ProSettings $settings, PaymentLinkGateway $gateway): View
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
            'subscriptions' => $vendor->subscriptions()->where('status', '!=', SubscriptionStatus::Pending)->limit(12)->get(),
            'totals' => $analytics->totals($vendor->isPro() ? VendorAnalytics::DAYS : VendorAnalytics::TEASER_DAYS),
            'daily' => $vendor->isPro() ? $analytics->dailyViews() : [],
        ]);
    }

    /**
     * Start a purchase and send the vendor to pay. The price is stamped now,
     * so the amount charged is the one they saw.
     */
    public function checkout(Request $request, ProSettings $settings, PaymentLinkGateway $gateway): RedirectResponse
    {
        $validated = $request->validate([
            'plan' => ['required', Rule::enum(VendorPlan::class)],
        ]);

        $vendor = $request->user()->vendor;

        abort_unless($settings->isEnabled() && $gateway->isConfigured() && $vendor->isApproved(), 404);

        $plan = VendorPlan::from($validated['plan']);

        $subscription = $vendor->subscriptions()->create([
            'reference' => VendorSubscription::generateReference(),
            'plan' => $plan,
            'amount' => $settings->price($plan),
            'status' => SubscriptionStatus::Pending,
            'gateway' => VendorSubscription::GATEWAY_HEREPAY,
        ]);

        try {
            $url = $gateway->createPaymentLink($subscription, $request->user());
        } catch (Throwable $exception) {
            report($exception);
            $subscription->update(['status' => SubscriptionStatus::Failed]);

            return back()->withErrors(['plan' => __('flash.vendor.pro_checkout_failed')]);
        }

        $subscription->update(['payment_url' => $url]);

        return redirect()->away($url);
    }

    /**
     * Where the gateway sends the vendor back. The callback, not this page,
     * is what activates Pro, so this only reports what is known so far.
     */
    public function done(Request $request): View
    {
        $vendor = $request->user()->vendor;

        return view('vendor.pro.done', [
            'vendor' => $vendor,
            'subscription' => $vendor->subscriptions()
                ->where('gateway', VendorSubscription::GATEWAY_HEREPAY)
                ->when($request->string('ref')->toString(), fn ($query, string $reference) => $query->where('reference', $reference))
                ->first(),
        ]);
    }
}
