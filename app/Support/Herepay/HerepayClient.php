<?php

namespace App\Support\Herepay;

use App\Models\User;
use App\Models\VendorSubscription;
use App\Support\HerepaySettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Herepay payment links for Neekah Pro.
 *
 * Only the Create Payment Link API is used: create a single-use link, send the
 * vendor to its pay_url, and let Herepay's pay page do the rest (bank choice,
 * FPX, receipt). The link carries its own redirect_url and callback_url.
 *
 * Herepay's callback does not echo anything of ours back, so the callback_url
 * carries the subscription reference itself, in a signed URL: the reference
 * cannot be swapped for another one, and the body is trusted only when its
 * checksum matches the team private key.
 */
class HerepayClient implements PaymentLinkGateway
{
    /** The .env entry behind each key, which is what an admin is told to fill in. */
    public const KEYS = [
        'base_url' => 'HEREPAY_BASE_URL',
        'secret_key' => 'HEREPAY_SECRET_KEY',
        'private_key' => 'HEREPAY_PRIVATE_KEY',
    ];

    public function __construct(private HerepaySettings $settings) {}

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
        $vendor = $subscription->vendor;

        $response = Http::baseUrl(rtrim((string) config('services.herepay.base_url'), '/'))
            ->withHeaders(['SecretKey' => (string) config('services.herepay.secret_key')])
            ->acceptJson()
            ->asJson()
            ->timeout(15)
            ->post('/api/integration/create-payment-link', array_filter([
                'title' => Str::limit(__('pages.pro.herepay_title', ['plan' => $subscription->plan->label(), 'vendor' => $vendor->name]), 250),
                'amount' => round((float) $subscription->amount, 2),
                'description' => e(__('pages.pro.herepay_description', ['reference' => $subscription->reference])),
                'usage_type' => 'single',
                'expires_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'redirect_url' => route('vendor.pro.done', ['ref' => $subscription->reference]),
                'callback_url' => url(URL::signedRoute('webhooks.herepay', ['ref' => $subscription->reference], absolute: false)),
                'payer_name' => Str::limit($payer->name, 250, ''),
                'payer_email' => $payer->email,
                'payer_phone' => $payer->phone ? Str::limit($payer->phone, 32, '') : null,
            ], fn (mixed $value): bool => $value !== null && $value !== ''))
            ->throw();

        $url = $response->json('data.pay_url');

        if (! is_string($url) || ! str_starts_with($url, 'https://')) {
            throw new RuntimeException('Herepay answered without a pay_url.');
        }

        return $url;
    }

    public function parseCallback(Request $request): ?array
    {
        $reference = $request->query('ref');

        if (! is_string($reference) || ! $request->hasValidRelativeSignature() || ! $this->hasValidChecksum($request)) {
            return null;
        }

        $body = $this->body($request);

        return [
            'reference' => $reference,
            'gateway_reference' => ($body['payment_code'] ?? null) ?: ($body['reference_code'] ?? null) ?: null,
            'status' => match ((string) ($body['status_code'] ?? '')) {
                '00' => 'paid',
                '30' => 'failed',
                default => 'pending',
            },
            'amount' => (float) ($body['amount'] ?? 0),
        ];
    }

    /**
     * Every body field but the checksum, sorted by key, values joined with a
     * comma (arrays JSON-encoded first), HMAC-SHA256 with the private key.
     * Only the body counts: our own ref and signature ride in the query string.
     */
    private function hasValidChecksum(Request $request): bool
    {
        $payload = $this->body($request);
        $checksum = $payload['checksum'] ?? null;
        $privateKey = (string) config('services.herepay.private_key');

        if (! is_string($checksum) || $privateKey === '') {
            return false;
        }

        unset($payload['checksum']);
        ksort($payload);

        $signed = implode(',', array_map(
            fn (mixed $value): string => is_array($value) ? (string) json_encode($value) : (string) $value,
            $payload,
        ));

        return hash_equals(hash_hmac('sha256', $signed, $privateKey), $checksum);
    }

    /**
     * The callback body alone, never the query string. Herepay posts it
     * form-encoded; JSON is read too, in case that changes.
     *
     * @return array<string, mixed>
     */
    private function body(Request $request): array
    {
        return $request->isJson() ? $request->json()->all() : $request->request->all();
    }
}
