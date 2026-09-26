<?php

namespace App\Support\Herepay;

use App\Models\User;
use App\Models\VendorSubscription;
use App\Support\HerepaySettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Herepay payment links for Neekah Pro.
 *
 * Only the Create Payment Link API is used: create a single-use link, send the
 * vendor to its pay_url, and let Herepay's pay page do the rest (bank choice,
 * FPX, receipt). The link carries its own redirect_url and callback_url.
 *
 * This is Neekah's own account; a vendor's booking deposits go through
 * HerepayDepositClient on the vendor's account. Both share HerepayTransport.
 */
class HerepayClient implements PaymentLinkGateway
{
    /** The .env entry behind each key, which is what an admin is told to fill in. */
    public const KEYS = [
        'base_url' => 'HEREPAY_BASE_URL',
        'secret_key' => 'HEREPAY_SECRET_KEY',
        'private_key' => 'HEREPAY_PRIVATE_KEY',
    ];

    public function __construct(private HerepaySettings $settings, private HerepayTransport $transport) {}

    public function isConfigured(): bool
    {
        return $this->settings->isEnabled() && $this->missingKeys() === [];
    }

    /**
     * The .env entries still empty.
     *
     * @return list<string>
     */
    public function missingKeys(): array
    {
        return array_values(array_filter(
            self::KEYS,
            fn (string $env, string $key): bool => blank(config('services.herepay.'.$key)),
            ARRAY_FILTER_USE_BOTH,
        ));
    }

    /** "UAT" or "Production", read off the base URL, for the admin to see which one is live. */
    public function environment(): ?string
    {
        $host = parse_url((string) config('services.herepay.base_url'), PHP_URL_HOST);

        return match (true) {
            blank($host) => null,
            str_starts_with($host, 'uat.') => 'UAT',
            default => 'Production',
        };
    }

    public function createPaymentLink(VendorSubscription $subscription, User $payer): string
    {
        return $this->transport->createLink(HerepayCredentials::neekah(), [
            'title' => Str::limit(__('pages.pro.herepay_title', ['plan' => $subscription->plan->label(), 'vendor' => $subscription->vendor->name]), 250),
            'amount' => round((float) $subscription->amount, 2),
            'description' => e(__('pages.pro.herepay_description', ['reference' => $subscription->reference])),
            'usage_type' => 'single',
            'expires_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'redirect_url' => route('vendor.pro.done', ['ref' => $subscription->reference]),
            'callback_url' => url(URL::signedRoute('webhooks.herepay', ['ref' => $subscription->reference], absolute: false)),
            'payer_name' => Str::limit($payer->name, 250, ''),
            'payer_email' => $payer->email,
            'payer_phone' => $payer->phone ? Str::limit($payer->phone, 32, '') : null,
        ]);
    }

    public function parseCallback(Request $request): ?array
    {
        return $this->transport->readCallback($request, HerepayCredentials::neekah());
    }
}
