<?php

namespace App\Support\Herepay;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * The two things Neekah ever does with Herepay, on whichever account: create a
 * single-use payment link, and read a callback. Only the Create Payment Link
 * API is used (see .ai/rules/herepay.md).
 *
 * Herepay's callback carries nothing of ours, so each link's callback_url is a
 * signed route with our reference in it. A callback is trusted only when that
 * signature holds AND the body's checksum matches the account's private key.
 */
class HerepayTransport
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function createLink(HerepayCredentials $credentials, array $payload): string
    {
        $response = Http::baseUrl(rtrim((string) config('services.herepay.base_url'), '/'))
            ->withHeaders(['SecretKey' => $credentials->secretKey])
            ->acceptJson()
            ->asJson()
            ->timeout(15)
            ->post('/api/integration/create-payment-link', array_filter($payload, fn (mixed $value): bool => $value !== null && $value !== ''))
            ->throw();

        $url = $response->json('data.pay_url');

        if (! is_string($url) || ! str_starts_with($url, 'https://')) {
            throw new RuntimeException('Herepay answered without a pay_url.');
        }

        return $url;
    }

    /**
     * @return array{reference: string, gateway_reference: string|null, status: 'paid'|'failed'|'pending', amount: float}|null
     */
    public function readCallback(Request $request, HerepayCredentials $credentials): ?array
    {
        $reference = $request->query('ref');

        if (! is_string($reference) || ! $request->hasValidRelativeSignature() || ! $this->hasValidChecksum($request, $credentials)) {
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
    private function hasValidChecksum(Request $request, HerepayCredentials $credentials): bool
    {
        $payload = $this->body($request);
        $checksum = $payload['checksum'] ?? null;

        if (! is_string($checksum) || $credentials->privateKey === '') {
            return false;
        }

        unset($payload['checksum']);
        ksort($payload);

        $signed = implode(',', array_map(
            fn (mixed $value): string => is_array($value) ? (string) json_encode($value) : (string) $value,
            $payload,
        ));

        return hash_equals(hash_hmac('sha256', $signed, $credentials->privateKey), $checksum);
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
