<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ActivateVendorPro;
use App\Enums\PaymentStatus;
use App\Enums\VendorPlan;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VendorProController extends Controller
{
    /** Paid or manual Pro periods shown on the card, newest first. */
    public const HISTORY = 10;

    /**
     * The plan card on a vendor's admin page: Basic or Pro and until when,
     * what was paid, and the forms to give or end Pro.
     *
     * @return array<string, mixed>
     */
    public static function card(Vendor $vendor): array
    {
        return [
            'isPro' => $vendor->isPro(),
            'until' => $vendor->pro_until?->translatedFormat('j M Y'),
            'expired' => $vendor->pro_until !== null && ! $vendor->isPro(),
            'plans' => array_map(fn (VendorPlan $plan): array => [
                'value' => $plan->value,
                'label' => $plan->label(),
                'price' => $plan->price(),
            ], VendorPlan::cases()),
            // Unpaid checkouts are abandoned carts; the history is what was paid.
            'history' => $vendor->proPayments()->with('recorder')->whereNot('status', PaymentStatus::Pending)->limit(self::HISTORY)->get()
                ->map(fn (Payment $payment): array => [
                    'id' => $payment->id,
                    'reference' => $payment->reference,
                    'plan' => VendorPlan::tryFrom((string) $payment->detail('plan'))?->label(),
                    'amount' => 'RM'.number_format((float) $payment->amount, 2),
                    'status' => $payment->status->label(),
                    'period' => collect([$payment->detail('starts_at'), $payment->detail('ends_at')])->filter()->map(fn (string $date): string => Carbon::parse($date)->translatedFormat('j M Y'))->implode(' – '),
                    'source' => $payment->gateway === Payment::GATEWAY_MANUAL
                        ? __('pages.pro.manual_by', ['name' => $payment->recorder?->name ?? '—'])
                        : Str::headline($payment->gateway),
                    'note' => $payment->note,
                    'url' => route('admin.payments.show', $payment),
                ])->values()->all(),
            'storeUrl' => route('admin.vendors.pro', $vendor),
            'endUrl' => route('admin.vendors.pro.end', $vendor),
        ];
    }

    /**
     * Give a vendor Pro by hand: a bank transfer, a promotion, or free. It
     * extends whatever the vendor has left, like a checkout.
     */
    public function store(Request $request, Vendor $vendor, ActivateVendorPro $activate): RedirectResponse
    {
        $validated = $request->validate([
            'plan' => ['required', Rule::enum(VendorPlan::class)],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $payment = $activate->recordManually(
            $vendor,
            VendorPlan::from($validated['plan']),
            $request->user(),
            $validated['note'] ?? null,
            isset($validated['amount']) ? (float) $validated['amount'] : null,
        );

        return back()->with('status', __('flash.admin.pro_activated', [
            'vendor' => $vendor->name,
            'date' => Carbon::parse($payment->detail('ends_at'))->translatedFormat('j M Y'),
        ]));
    }

    /**
     * End Pro now, back to Basic: the Pro pages lock at once. What was paid
     * stays in the history; any refund is settled outside Neekah.
     */
    public function destroy(Request $request, Vendor $vendor): RedirectResponse
    {
        abort_unless($vendor->isPro(), 404);

        $vendor->update(['pro_until' => now()]);

        Log::warning('Admin ended Neekah Pro early', ['admin_id' => $request->user()->id, 'vendor_id' => $vendor->id]);

        return back()->with('status', __('flash.admin.pro_ended', ['vendor' => $vendor->name]));
    }
}
