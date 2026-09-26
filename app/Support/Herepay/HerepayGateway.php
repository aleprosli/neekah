<?php

namespace App\Support\Herepay;

use App\Enums\PaymentMerchant;
use App\Models\Payment;
use App\Support\HerepaySettings;
use App\Support\Payments\GatewayResult;
use App\Support\Payments\PaymentGateway;
use App\Support\Payments\PaymentLink;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

/**
 * Herepay, for every payment Neekah takes: Pro, boost packs and Neekah
 * Kenangan on Neekah's own account, booking deposits on the vendor's.
 *
 * A payment link carries its own callback and return addresses. Herepay signs
 * both what it posts to the callback and what it puts on the payer's return,
 * so either can settle a payment; a requery asks Herepay by the invoice
 * (reference_code) the other two told us.
 */
class HerepayGateway implements PaymentGateway
{
    public const NAME = 'herepay';

    /** The .env entry behind each key, which is what an admin is told to fill in. */
    public const KEYS = [
        'base_url' => 'HEREPAY_BASE_URL',
        'secret_key' => 'HEREPAY_SECRET_KEY',
        'private_key' => 'HEREPAY_PRIVATE_KEY',
    ];

    /** Query keys of our own on the return address, not part of what Herepay signed. */
    private const OWN_QUERY_KEYS = ['ref', 'signature', 'expires', 'cuba'];

    public function __construct(private HerepaySettings $settings, private HerepayTransport $transport) {}

    public function name(): string
    {
        return self::NAME;
    }

    /** Neekah's own account: switched on by an admin and every key in place. */
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

    /** Whether Neekah's payments can be asked about again (HEREPAY_API_KEY set). */
    public function canRequeryNeekah(): bool
    {
        return HerepayCredentials::neekah()->canRequery();
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

    public function isConfiguredFor(Payment $payment): bool
    {
        if ($payment->merchant === PaymentMerchant::NEEKAH) {
            return $this->isConfigured();
        }

        return filled(config('services.herepay.base_url')) && $this->credentialsFor($payment) !== null;
    }

    public function createLink(Payment $payment, PaymentLink $link): array
    {
        return $this->transport->createLink($this->credentialsOrFail($payment), [
            'title' => Str::limit($link->title, 250),
            'amount' => round($link->amount, 2),
            'description' => e($link->description),
            'usage_type' => 'single',
            'expires_at' => $link->expiresAt->format('Y-m-d H:i:s'),
            'redirect_url' => $link->returnUrl,
            'callback_url' => $link->callbackUrl,
            'payer_name' => $link->payerName ? Str::limit($link->payerName, 250, '') : null,
            'payer_email' => $link->payerEmail,
            'payer_phone' => $link->payerPhone ? Str::limit($link->payerPhone, 32, '') : null,
        ]);
    }

    /**
     * The callback body alone, never the query string, which carries our own
     * signed ref. Herepay posts it form-encoded; JSON is read too.
     */
    public function readCallback(Request $request, Payment $payment): GatewayResult
    {
        $credentials = $this->credentialsFor($payment);

        if (! $credentials) {
            return GatewayResult::unverified('The account this payment was made on is no longer connected.');
        }

        return $this->proveVendorKey($payment, $this->transport->read($request->isJson() ? $request->json()->all() : $request->request->all(), $credentials));
    }

    public function readReturn(Request $request, Payment $payment): GatewayResult
    {
        $credentials = $this->credentialsFor($payment);

        if (! $credentials) {
            return GatewayResult::unverified('The account this payment was made on is no longer connected.');
        }

        return $this->proveVendorKey($payment, $this->transport->read(collect($request->query())->except(self::OWN_QUERY_KEYS)->all(), $credentials));
    }

    public function canRequery(Payment $payment): bool
    {
        return filled($payment->gateway_invoice) && (bool) $this->credentialsFor($payment)?->canRequery();
    }

    public function requery(Payment $payment): GatewayResult
    {
        if (! $this->canRequery($payment)) {
            return GatewayResult::unverified(blank($payment->gateway_invoice)
                ? 'No Herepay invoice (reference_code) is known for this payment yet.'
                : 'No Herepay API key for this account.');
        }

        return $this->transport->transaction($this->credentialsOrFail($payment), (string) $payment->gateway_invoice);
    }

    /**
     * Herepay offers no "who am I" call, so the test is a real link: RM1,
     * single use, gone in ten minutes, never paid. A link coming back proves
     * the secret key; the private key is proved by the first real callback.
     */
    public function testConnection(HerepayCredentials $credentials): bool
    {
        try {
            $this->transport->createLink($credentials, [
                'title' => __('pages.online_booking.herepay_test_title'),
                'amount' => 1.00,
                'usage_type' => 'single',
                'expires_at' => now()->addMinutes(10)->format('Y-m-d H:i:s'),
                'collect_phone' => false,
            ]);

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    /**
     * Connecting only proves a vendor's secret key; the first answer that
     * verifies against their private key proves that one too.
     */
    private function proveVendorKey(Payment $payment, GatewayResult $result): GatewayResult
    {
        if ($result->verified && $payment->merchant === PaymentMerchant::VENDOR) {
            $payment->vendor?->bookingSettings()->whereNull('herepay_verified_at')->update(['herepay_verified_at' => now()]);
        }

        return $result;
    }

    private function credentialsFor(Payment $payment): ?HerepayCredentials
    {
        if ($payment->merchant === PaymentMerchant::NEEKAH) {
            return HerepayCredentials::neekah();
        }

        $vendor = $payment->vendor ?? $payment->booking?->vendor;

        return $vendor ? HerepayCredentials::forVendor($vendor->bookingSettingsOrDefault()) : null;
    }

    private function credentialsOrFail(Payment $payment): HerepayCredentials
    {
        return $this->credentialsFor($payment) ?? throw new \RuntimeException("Payment {$payment->reference} has no Herepay account to use.");
    }
}
