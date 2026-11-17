<?php

namespace App\Support\Herepay;

use App\Support\Payments\GatewayResult;
use App\Support\Payments\PaymentRedirect;
use DOMDocument;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * The calls Neekah makes to Herepay's direct API, on whichever account: list
 * the banks, start a payment with the one the payer picked, check what
 * Herepay sends back, and ask about a transaction again. See .ai/rules/herepay.md.
 *
 * Herepay signs what it sends (the callback body, and the query it puts on
 * the payer's way back) with the account's private key: every field but the
 * checksum, sorted by key, values joined with a comma (arrays JSON-encoded
 * first), HMAC-SHA256.
 */
class HerepayTransport
{
    /**
     * The banks on this account, as Herepay lists them: groups by kind of
     * account ("Online Banking", "Corporate Banking"), each bank with its
     * prefix, name, logo and whether it is up ("Active"/"Inactive").
     *
     * @return list<array<string, mixed>>
     */
    public function channels(HerepayCredentials $credentials): array
    {
        $response = $this->client()
            ->withHeaders(['SecretKey' => $credentials->secretKey, 'XApiKey' => $credentials->apiKey])
            ->timeout(15)
            ->get('/api/v1/herepay/payment/channels')
            ->throw();

        $groups = $response->json('data');

        if (! is_array($groups)) {
            throw new RuntimeException('Herepay answered the channel list without data.');
        }

        return array_values(array_filter($groups, 'is_array'));
    }

    /**
     * Start a payment with the bank the payer picked. Herepay takes the
     * fields form-encoded and signed, and answers with an HTML page whose
     * form posts the payer on to FPX; that form is what comes back.
     *
     * @param  array<string, string>  $fields
     */
    public function initiate(HerepayCredentials $credentials, array $fields): PaymentRedirect
    {
        $fields['checksum'] = self::checksum($fields, $credentials->privateKey);

        $response = $this->client()
            ->withHeaders(['SecretKey' => $credentials->secretKey, 'XApiKey' => $credentials->apiKey])
            ->asForm()
            ->timeout(20)
            ->post('/api/v1/herepay/initiate', $fields)
            ->throw();

        return self::formFrom($response->body());
    }

    /**
     * Herepay's signature over a set of fields: every field but the checksum,
     * sorted by key, values joined with a comma (arrays JSON-encoded first),
     * HMAC-SHA256 with the account's private key, lowercase hex.
     *
     * @param  array<string, mixed>  $fields
     */
    public static function checksum(array $fields, string $privateKey): string
    {
        unset($fields['checksum']);
        ksort($fields);

        return hash_hmac('sha256', implode(',', array_map(
            fn (mixed $value): string => is_array($value) ? (string) json_encode($value) : (string) $value,
            $fields,
        )), $privateKey);
    }

    /**
     * The form in the page Herepay answers an initiate with: where it posts
     * and every named field in it. Only an https address is accepted, so a
     * broken answer never sends the payer somewhere odd.
     */
    public static function formFrom(string $html): PaymentRedirect
    {
        $document = new DOMDocument;

        if (trim($html) === '' || ! @$document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) {
            throw new RuntimeException('Herepay answered the initiate with an empty page.');
        }

        $form = $document->getElementsByTagName('form')->item(0);
        $action = $form?->getAttribute('action') ?? '';

        if (! $form || ! str_starts_with($action, 'https://')) {
            throw new RuntimeException('Herepay answered the initiate without a form to the bank.');
        }

        $fields = [];

        foreach ($form->getElementsByTagName('input') as $input) {
            if ($input->getAttribute('name') !== '') {
                $fields[$input->getAttribute('name')] = $input->getAttribute('value');
            }
        }

        return new PaymentRedirect($action, $fields, strtolower($form->getAttribute('method') ?: 'post'));
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
    /**
     * @param  array<string, mixed>  $fields
     */
    private function hasValidChecksum(array $fields, HerepayCredentials $credentials): bool
    {
        $checksum = $fields['checksum'] ?? null;

        if (! is_string($checksum) || $credentials->privateKey === '') {
            return false;
        }

        return hash_equals(self::checksum($fields, $credentials->privateKey), $checksum);
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
