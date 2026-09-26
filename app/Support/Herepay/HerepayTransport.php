<?php

namespace App\Support\Herepay;

use App\Support\Payments\GatewayResult;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * The calls Neekah makes to Herepay, on whichever account: create a
 * single-use payment link, check what Herepay sends back, and ask about a
 * transaction again. See .ai/rules/herepay.md.
 *
 * Herepay signs what it sends (the callback body, and the query it puts on
 * the payer's way back) with the account's private key: every field but the
 * checksum, sorted by key, values joined with a comma (arrays JSON-encoded
 * first), HMAC-SHA256.
 */
class HerepayTransport
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{url: string, response: array<string, mixed>}
     */
    public function createLink(HerepayCredentials $credentials, array $payload): array
    {
        $response = $this->client()
            ->withHeaders(['SecretKey' => $credentials->secretKey])
            ->asJson()
            ->timeout(15)
            ->post('/api/integration/create-payment-link', array_filter($payload, fn (mixed $value): bool => $value !== null && $value !== ''))
            ->throw();

        $url = $response->json('data.pay_url');

        if (! is_string($url) || ! str_starts_with($url, 'https://')) {
            throw new RuntimeException('Herepay answered without a pay_url.');
        }

        return ['url' => $url, 'response' => (array) $response->json()];
    }

    /**
     * Herepay's word on a transaction, asked by its payment_code (HP-PAY-…;
     * see HerepayGateway::lookupCode). Our own authenticated call, so its
     * answer needs no checksum.
     */
    public function transaction(HerepayCredentials $credentials, string $code): GatewayResult
    {
        $response = $this->client()
            ->withHeaders(['SecretKey' => $credentials->secretKey, 'XApiKey' => $credentials->apiKey])
            ->timeout(15)
            ->get('/api/v1/herepay/transactions/'.rawurlencode($code));

        return $this->fromTransaction($response);
    }

    /**
     * Fields Herepay sent, checked against the account's private key.
     *
     * @param  array<string, mixed>  $fields
     */
    public function read(array $fields, HerepayCredentials $credentials): GatewayResult
    {
        if (! $this->hasValidChecksum($fields, $credentials)) {
            return GatewayResult::unverified(isset($fields['checksum']) ? 'Checksum does not match.' : 'No checksum.');
        }

        return new GatewayResult(
            verified: true,
            status: self::statusOf($fields['status_code'] ?? null, $fields['status'] ?? null),
            amount: isset($fields['amount']) ? (float) $fields['amount'] : null,
            reference: self::filled($fields['payment_code'] ?? null),
            invoice: self::filled($fields['reference_code'] ?? null),
            transactionId: self::filled($fields['transaction_id'] ?? $fields['fpx_transaction_id'] ?? null),
            method: self::filled($fields['payment_method'] ?? $fields['fpx_type'] ?? null),
            gatewayStatus: trim(($fields['status_code'] ?? '').' '.($fields['message'] ?? $fields['status'] ?? '')) ?: null,
        );
    }

    /**
     * Herepay's status codes: 00 is paid, 30 failed, anything else still
     * settling. A transaction lookup has been seen answering "1"/"Completed"
     * for a paid one, so the words count too.
     */
    public static function statusOf(mixed $code, mixed $status = null): string
    {
        $code = (string) $code;
        $status = strtolower((string) $status);

        return match (true) {
            in_array($code, ['00', '1'], true), in_array($status, ['success', 'completed', 'paid'], true) => GatewayResult::PAID,
            $code === '30', in_array($status, ['failed', 'fail', 'cancelled', 'expired'], true) => GatewayResult::FAILED,
            default => GatewayResult::PENDING,
        };
    }

    private function fromTransaction(Response $response): GatewayResult
    {
        $raw = (array) $response->json();

        if ($response->status() === 404) {
            return new GatewayResult(verified: true, status: GatewayResult::PENDING, gatewayStatus: '404 '.$response->json('message'), raw: $raw, httpStatus: 404);
        }

        if (! $response->successful() || ! is_array($data = $response->json('data'))) {
            return GatewayResult::unverified('Herepay answered HTTP '.$response->status().'.', $raw, $response->status());
        }

        return new GatewayResult(
            verified: true,
            status: self::statusOf($data['status_code'] ?? null, $data['status'] ?? null),
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            reference: self::filled($data['payment_code'] ?? null),
            invoice: self::filled($data['reference_code'] ?? null),
            transactionId: self::filled($data['fpx_transaction_id'] ?? $data['transaction_id'] ?? null),
            method: self::filled($data['fpx_type'] ?? $data['payment_method'] ?? null),
            gatewayStatus: trim(($data['status_code'] ?? '').' '.($data['status'] ?? '')) ?: null,
            raw: $raw,
            httpStatus: $response->status(),
        );
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function hasValidChecksum(array $fields, HerepayCredentials $credentials): bool
    {
        $checksum = $fields['checksum'] ?? null;

        if (! is_string($checksum) || $credentials->privateKey === '') {
            return false;
        }

        unset($fields['checksum']);
        ksort($fields);

        $signed = implode(',', array_map(
            fn (mixed $value): string => is_array($value) ? (string) json_encode($value) : (string) $value,
            $fields,
        ));

        return hash_equals(hash_hmac('sha256', $signed, $credentials->privateKey), $checksum);
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.herepay.base_url'), '/'))->acceptJson();
    }

    private static function filled(mixed $value): ?string
    {
        return filled($value) ? (string) $value : null;
    }
}
