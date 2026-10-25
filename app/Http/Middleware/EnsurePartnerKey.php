<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets a partner (config services.partners) read the vendor directory. The key
 * comes in X-Neekah-Key and is compared in constant time; the partner it
 * belongs to rides on the request as the "partner" attribute, so a review
 * knows where it came from and the throttle counts per partner.
 */
class EnsurePartnerKey
{
    public const HEADER = 'X-Neekah-Key';

    public function handle(Request $request, Closure $next): Response
    {
        $partner = self::partnerFor((string) $request->header(self::HEADER));

        if ($partner === null) {
            return response()->json([
                'message' => 'Missing or invalid API key.',
                'code' => 'invalid_key',
            ], 401);
        }

        $request->attributes->set('partner', $partner);

        return $next($request);
    }

    /**
     * The partner whose key this is, or null. A partner with no key set is
     * switched off rather than open to anyone sending an empty header.
     */
    public static function partnerFor(string $key): ?string
    {
        if ($key === '') {
            return null;
        }

        foreach (config('services.partners', []) as $partner => $settings) {
            $expected = (string) ($settings['key'] ?? '');

            if ($expected !== '' && hash_equals($expected, $key)) {
                return $partner;
            }
        }

        return null;
    }

    /**
     * The partner's display name, e.g. "Kampung1998".
     */
    public static function nameOf(string $partner): string
    {
        return (string) config("services.partners.{$partner}.name", $partner);
    }
}
