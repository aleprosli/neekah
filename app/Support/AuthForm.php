<?php

namespace App\Support;

/**
 * The props behind resources/js/components/auth/AuthForm.vue.
 *
 * Every authentication page is the same form with different fields, so the
 * captcha key and the CSRF token are collected here rather than in five
 * controllers.
 */
class AuthForm
{
    /**
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    public static function for(array $props): array
    {
        $turnstile = app(TurnstileSettings::class);

        return VueProps::for([
            ...$props,
            'turnstileSiteKey' => ($props['captcha'] ?? false) && $turnstile->isEnabled() ? $turnstile->siteKey() : null,
        ]);
    }
}
