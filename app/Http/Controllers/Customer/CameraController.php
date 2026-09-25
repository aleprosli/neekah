<?php

namespace App\Http\Controllers\Customer;

use App\Enums\CameraTier;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\CameraAlbum;
use App\Models\CameraPurchase;
use App\Models\Wedding;
use App\Support\CameraSettings;
use App\Support\Herepay\CameraPaymentGateway;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Kamera Majlis from the couple's side: what it is and what it costs before
 * they buy, their album once they have, and paying for it.
 */
class CameraController extends Controller
{
    public function index(Request $request, CameraSettings $settings, CameraPaymentGateway $gateway): View
    {
        $wedding = $request->user()->weddings()->latest('event_date')->firstOrFail();
        Gate::authorize('view', $wedding);

        $album = $wedding->cameraAlbum;
        $active = $album?->isActive() ? $album : null;

        return view('customer.camera.index', [
            'props' => VueProps::for([
                'canCheckout' => $settings->isEnabled() && $gateway->isConfigured(),
                'checkoutUrl' => route('camera.checkout', $wedding),
                'tiers' => collect(CameraTier::cases())->map(fn (CameraTier $tier): array => [
                    'value' => $tier->value,
                    'label' => $tier->label(),
                    'price' => $settings->price($tier),
                    'limits' => $settings->limitsFor($tier)->toArray(),
                    'owned' => $active !== null && $active->tier->rank() >= $tier->rank(),
                    'payable' => $this->amountFor($active, $tier, $settings),
                ])->values(),
                'retentionDays' => $settings->retentionDays(),
                'album' => $active ? [
                    'tier' => $active->tier->value,
                    'tier_label' => $active->tier->label(),
                    'url' => $active->url(),
                    'photos' => $active->photos_count,
                    'videos' => $active->videos_count,
                    'max_photos' => $active->limits()->maxPhotos,
                    'bytes' => $active->bytes_used,
                    'expires' => $active->expires_at?->translatedFormat('j F Y'),
                    'restricted' => $active->isRestricted(),
                ] : null,
                'purchases' => $wedding->cameraPurchases()->where('status', SubscriptionStatus::Paid)->latest()->limit(10)->get()
                    ->map(fn (CameraPurchase $purchase): array => [
                        'reference' => $purchase->reference,
                        'tier' => $purchase->tier->label(),
                        'amount' => 'RM'.number_format((float) $purchase->amount, 2),
                        'paid_at' => $purchase->paid_at?->translatedFormat('j M Y'),
                    ])->values(),
            ]),
        ]);
    }

    /**
     * Start a purchase and send the couple to pay. The price is stamped now;
     * an upgrade from Basic costs the difference. An album never goes down a
     * tier, and a tier already owned is not sold twice.
     */
    public function checkout(Request $request, Wedding $wedding, CameraSettings $settings, CameraPaymentGateway $gateway): RedirectResponse
    {
        Gate::authorize('update', $wedding);
        abort_unless($settings->isEnabled() && $gateway->isConfigured(), 404);

        $tier = CameraTier::from($request->validate(['tier' => ['required', Rule::enum(CameraTier::class)]])['tier']);
        $active = $wedding->cameraAlbum?->isActive() ? $wedding->cameraAlbum : null;
        $amount = $this->amountFor($active, $tier, $settings);

        if ($amount === null) {
            throw ValidationException::withMessages(['tier' => __('flash.couple.camera_already_owned')]);
        }

        $purchase = $wedding->cameraPurchases()->create([
            'user_id' => $request->user()->id,
            'reference' => CameraPurchase::generateReference(),
            'tier' => $tier,
            'kind' => $active ? CameraPurchase::KIND_UPGRADE : CameraPurchase::KIND_NEW,
            'amount' => $amount,
            'status' => SubscriptionStatus::Pending,
            'gateway' => CameraPurchase::GATEWAY_HEREPAY,
        ]);

        try {
            $url = $gateway->createPaymentLink($purchase, $request->user());
        } catch (Throwable $exception) {
            report($exception);
            $purchase->update(['status' => SubscriptionStatus::Failed]);

            return back()->withErrors(['tier' => __('flash.couple.camera_checkout_failed')]);
        }

        $purchase->update(['payment_url' => $url]);

        return redirect()->away($url);
    }

    /**
     * Where Herepay sends the couple back. The callback, not this visit,
     * opens the album; this only reports what is known.
     */
    public function done(Request $request): View
    {
        $purchase = CameraPurchase::query()
            ->where('reference', $request->string('ref')->toString())
            ->whereIn('wedding_id', $request->user()->weddings()->pluck('weddings.id'))
            ->firstOrFail();

        return view('customer.camera.done', [
            'purchase' => $purchase,
            'state' => match ($purchase->status) {
                SubscriptionStatus::Paid => 'paid',
                SubscriptionStatus::Failed => 'failed',
                default => 'waiting',
            },
        ]);
    }

    /**
     * What buying $tier costs now: the full price with no album, the
     * difference when upgrading, or null when the album already has it.
     */
    private function amountFor(?CameraAlbum $active, CameraTier $tier, CameraSettings $settings): ?float
    {
        if ($active === null) {
            return $settings->price($tier);
        }

        if ($tier->rank() <= $active->tier->rank()) {
            return null;
        }

        return max(1.0, $settings->price($tier) - $settings->price($active->tier));
    }
}
