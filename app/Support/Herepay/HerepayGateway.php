<?php

namespace App\Support\Herepay;

use App\Enums\PaymentMerchant;
use App\Models\Payment;
use App\Support\HerepaySettings;
use App\Support\Payments\GatewayResult;
use App\Support\Payments\PaymentGateway;
use App\Support\Payments\PaymentRedirect;
use App\Support\Payments\PaymentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

/**
 * Herepay, for every payment Neekah takes: Pro, boost packs and Neekah
 * Kenangan on Neekah's own account, booking deposits on the vendor's.
 *
 * Direct API: the payer picks a bank on our confirmation page from Herepay's
 * channel list, and the initiate (signed, with its own callback and return
 * addresses, payment_code = our reference) answers with the form that takes
 * them to FPX. Herepay signs both what it posts to the callback and what it
 * sends on the payer's return, so either can settle a payment; a requery
 * asks Herepay by our reference.
 */
class HerepayGateway implements PaymentGateway
{
    public const NAME = 'herepay';

    /** The .env entry behind each key, which is what an admin is told to fill in. */
    public const KEYS = [
        'base_url' => 'HEREPAY_BASE_URL',
        'secret_key' => 'HEREPAY_SECRET_KEY',
        'private_key' => 'HEREPAY_PRIVATE_KEY',
        'api_key' => 'HEREPAY_API_KEY',
    ];

    /** The kinds of bank account Herepay takes, exactly as its API names them. */
    public const METHODS = ['Online Banking', 'Corporate Banking'];

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

    public function channels(Payment $payment): array
    {
        return collect($this->transport->channels($this->credentialsOrFail($payment)))
            ->filter(fn (array $group): bool => in_array($group['payment_method'] ?? null, self::METHODS, true))
            ->map(fn (array $group): array => [
                'method' => $group['payment_method'],
                'channels' => collect($group['channels'] ?? [])
                    ->filter(fn (mixed $bank): bool => is_array($bank) && filled($bank['prefix'] ?? null) && strcasecmp((string) ($bank['active'] ?? ''), 'Active') === 0)
                    ->map(fn (array $bank): array => [
                        'code' => (string) $bank['prefix'],
                        'name' => (string) ($bank['name'] ?? $bank['prefix']),
                        'logo' => str_starts_with((string) ($bank['img_url'] ?? ''), 'https://') ? $bank['img_url'] : null,
                    ])
                    ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $group): bool => $group['channels'] !== [])
            ->values()
            ->all();
    }

    /**
     * payment_code is our own reference, so a requery can ask by it before
     * Herepay has told us anything. The amount is signed as the exact text
     * sent ("10.00").
     */
    public function initiate(Payment $payment, PaymentRequest $request): PaymentRedirect
    {
        return $this->transport->initiate($this->credentialsOrFail($payment), self::initiateFields($payment, $request));
    }

    /**
     * What an initiate sends, before the checksum.
     *
     * @return array<string, string>
     */
    public static function initiateFields(Payment $payment, PaymentRequest $request): array
    {
        return [
            'name' => Str::limit($request->payerName, 250, ''),
            'email' => $request->payerEmail,
            'phone' => Str::limit($request->payerPhone, 32, ''),
            'amount' => number_format($request->amount, 2, '.', ''),
            'description' => Str::limit($request->description, 250, ''),
            'payment_code' => $payment->reference,
            'payment_method' => $request->paymentMethod,
            'bank_prefix' => $request->bankCode,
            'callback_url' => $request->callbackUrl,
            'redirect_url' => $request->returnUrl,
        ];
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

        // Herepay may send the payer back with a GET query or a posted form.
        $fields = $request->isMethod('post') ? $request->request->all() : $request->query();

        return $this->proveVendorKey($payment, $this->transport->read(collect($fields)->except(self::OWN_QUERY_KEYS)->all(), $credentials));
    }

    public function canRequery(Payment $payment): bool
    {
        return self::lookupCode($payment) !== null && (bool) $this->credentialsFor($payment)?->canRequery();
    }

    public function requery(Payment $payment): GatewayResult
    {
        if (! $this->canRequery($payment)) {
            return GatewayResult::unverified(self::lookupCode($payment) === null
                ? 'No Herepay payment code (HP-PAY-…) is known for this payment yet.'
                : 'No Herepay API key for this account.');
        }

        return $this->transport->transaction($this->credentialsOrFail($payment), (string) self::lookupCode($payment));
    }

    /**
     * What Herepay's transaction lookup finds a payment by: its payment_code.
     * A direct-API payment's code is our own reference, stamped on
     * gateway_reference when it is initiated; a payment made through the old
     * links has Herepay's HP-PAY-… there. The invoice is only a fallback.
     */
    public static function lookupCode(Payment $payment): ?string
    {
        return $payment->gateway_reference ?: ($payment->gateway_invoice ?: null);
    }

    /**
     * Herepay offers no "who am I" call, so the test is the channel list,
     * which needs both the secret key and the API key. The private key is
     * proved by the first real callback.
     */
    public function testConnection(HerepayCredentials $credentials): bool
    {
        try {
            $this->transport->channels($credentials);

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
