<?php

namespace App\Http\Controllers\Vendor;

use App\Actions\StartVendorBoost;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\BoostPurchase;
use App\Models\Category;
use App\Models\VendorBoost;
use App\Models\VendorBoostEntry;
use App\Support\BoostSettings;
use App\Support\Herepay\BoostPaymentGateway;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * A vendor's boost tokens: the balance, lifting a category, the history,
 * and buying more.
 */
class BoostController extends Controller
{
    /** History lines shown on the page, newest first. */
    public const HISTORY = 30;

    public function index(Request $request, BoostSettings $settings, BoostPaymentGateway $gateway): View
    {
        $vendor = $request->user()->vendor;
        $running = $vendor->boosts()->where('ends_at', '>', now())->with('category')->orderBy('ends_at')->get();

        return view('vendor.boost.index', [
            'props' => VueProps::for([
                'balance' => (int) $vendor->boost_tokens,
                'maxDays' => $settings->maxDays(),
                'categories' => $vendor->categories->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'ends_at' => $running->firstWhere('category_id', $category->id)?->ends_at->toIso8601String(),
                ])->values(),
                'running' => $running->map(fn (VendorBoost $boost): array => [
                    'id' => $boost->id,
                    'category' => $boost->category->name,
                    'ends' => $boost->ends_at->translatedFormat('j M Y, g:i A'),
                    'url' => route('vendors.index', ['category' => $boost->category->slug]),
                ])->values(),
                'history' => $vendor->boostEntries()->latest('id')->limit(self::HISTORY)->get()->map(fn (VendorBoostEntry $entry): array => [
                    'id' => $entry->id,
                    'change' => $entry->change,
                    'reason' => $entry->reason->label(),
                    'note' => $entry->note,
                    'date' => $entry->created_at->translatedFormat('j M Y'),
                ])->values(),
                'packs' => $settings->isEnabled() && $gateway->isConfigured()
                    ? collect(BoostSettings::PACKS)->map(fn (string $pack): array => ['id' => $pack, ...$settings->pack($pack)])->values()
                    : [],
                'isPro' => $vendor->isPro(),
                'proTokens' => $settings->proMonthlyTokens(),
                'urls' => [
                    'boost' => route('vendor.boost.store'),
                    'checkout' => route('vendor.boost.checkout'),
                    'pro' => route('vendor.pro.index'),
                ],
            ]),
        ]);
    }

    public function store(Request $request, StartVendorBoost $start, BoostSettings $settings): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'days' => ['required', 'integer', 'min:1', 'max:'.$settings->maxDays()],
        ]);

        $vendor = $request->user()->vendor;

        if ($vendor->boost_tokens < (int) $validated['days']) {
            throw ValidationException::withMessages(['days' => __('validation.custom.boost_not_enough', ['count' => (int) $vendor->boost_tokens])]);
        }

        $category = Category::query()->findOrFail($validated['category_id']);
        $boost = $start->handle($vendor, $category, (int) $validated['days']);

        return back()->with('status', __('flash.vendor.boost_started', [
            'category' => $category->name,
            'date' => $boost->ends_at->translatedFormat('j M Y, g:i A'),
        ]));
    }

    public function checkout(Request $request, BoostSettings $settings, BoostPaymentGateway $gateway): RedirectResponse
    {
        abort_unless($settings->isEnabled() && $gateway->isConfigured(), 404);

        $packName = $request->validate(['pack' => ['required', Rule::in(BoostSettings::PACKS)]])['pack'];
        $pack = $settings->pack($packName);
        $vendor = $request->user()->vendor;

        $purchase = BoostPurchase::query()->create([
            'vendor_id' => $vendor->id,
            'user_id' => $request->user()->id,
            'reference' => BoostPurchase::generateReference(),
            'pack' => $packName,
            'tokens' => $pack['tokens'],
            'amount' => $pack['price'],
            'status' => SubscriptionStatus::Pending,
            'gateway' => BoostPurchase::GATEWAY_HEREPAY,
        ]);

        try {
            $url = $gateway->createPaymentLink($purchase, $request->user());
        } catch (Throwable $exception) {
            report($exception);
            $purchase->update(['status' => SubscriptionStatus::Failed]);

            return back()->withErrors(['pack' => __('flash.vendor.boost_checkout_failed')]);
        }

        $purchase->update(['payment_url' => $url]);

        return redirect()->away($url);
    }

    /**
     * Where Herepay sends the vendor back. The callback, not this visit,
     * credits the tokens; this only reports what is known.
     */
    public function done(Request $request): View
    {
        $purchase = BoostPurchase::query()
            ->where('reference', $request->string('ref')->toString())
            ->where('vendor_id', $request->user()->vendor->id)
            ->firstOrFail();

        return view('vendor.boost.done', [
            'purchase' => $purchase,
            'state' => match ($purchase->status) {
                SubscriptionStatus::Paid => 'paid',
                SubscriptionStatus::Failed => 'failed',
                default => 'waiting',
            },
        ]);
    }
}
