<?php

namespace App\Rules;

use App\Support\TurnstileSettings;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Check the token Cloudflare Turnstile put in the form against Cloudflare.
 *
 * The rule passes untouched when Turnstile is switched off, so a form keeps
 * working on a server that has no keys. If Cloudflare itself cannot be reached
 * the submission is let through and logged: a captcha outage must not become
 * an outage of registration.
 */
class Turnstile implements ValidationRule
{
    /** Run even when the field is missing, which is exactly the bot's shortcut. */
    public bool $implicit = true;

    public function __construct(private readonly TurnstileSettings $settings) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->settings->isEnabled()) {
            return;
        }

        if (! is_string($value) || $value === '') {
            $fail('Sila lengkapkan semakan keselamatan.');

            return;
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post(TurnstileSettings::VERIFY_URL, [
                    'secret' => $this->settings->secretKey(),
                    'response' => $value,
                    'remoteip' => request()->ip(),
                ])
                ->throw();
        } catch (\Throwable $exception) {
            Log::warning('Turnstile verification could not be reached.', ['exception' => $exception->getMessage()]);

            return;
        }

        if (! $response->json('success')) {
            $fail('Semakan keselamatan gagal. Sila cuba sekali lagi.');
        }
    }
}
