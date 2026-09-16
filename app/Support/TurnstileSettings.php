<?php

namespace App\Support;

/**
 * Cloudflare Turnstile, the captcha in front of the public forms. Keys are set
 * under Admin → Tetapan, falling back to the environment so a server can be
 * configured before an admin ever logs in.
 *
 * The check only runs when it is switched on and both keys are present, so a
 * half-filled form can never lock everyone out of registering.
 */
class TurnstileSettings extends SettingGroup
{
    public const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function siteKey(): string
    {
        return $this->string('site_key');
    }

    public function secretKey(): string
    {
        return $this->string('secret_key');
    }

    public function isEnabled(): bool
    {
        return (bool) $this->value('enabled') && $this->siteKey() !== '' && $this->secretKey() !== '';
    }

    /**
     * @return array<string, string|bool>
     */
    public static function defaults(): array
    {
        return [
            'enabled' => (bool) config('services.turnstile.site_key'),
            'site_key' => (string) config('services.turnstile.site_key'),
            'secret_key' => (string) config('services.turnstile.secret_key'),
        ];
    }

    protected static function prefix(): string
    {
        return 'turnstile';
    }
}
