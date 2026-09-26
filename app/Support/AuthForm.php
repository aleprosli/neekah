<?php

namespace App\Support;

use App\Enums\AuthAudience;
use App\Rules\AccessCode;
use Illuminate\Http\Request;

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

    /**
     * The access code field, for the forms that sign someone in or up while
     * NEEKAH_ACCESS_CODE closes the site. An empty list when it is open.
     *
     * @return list<array<string, mixed>>
     */
    public static function accessCodeFields(): array
    {
        if (! AccessCode::isRequired()) {
            return [];
        }

        return [
            ['name' => 'access_code', 'label' => __('auth_pages.fields.access_code'), 'type' => 'password', 'autocomplete' => 'off', 'help' => __('auth_pages.fields.access_code_help'), 'required' => true],
        ];
    }

    /**
     * Google sign-in is off when it is not configured, and when an access
     * code closes the site, since signing in through Google never asks for it.
     */
    public static function offersGoogle(): bool
    {
        return filled(config('services.google.client_id')) && ! AccessCode::isRequired();
    }

    /**
     * Where "Teruskan dengan Google" leads, or null to leave the button off.
     */
    public static function googleUrl(): ?string
    {
        return self::offersGoogle() ? route('auth.google') : null;
    }

    /**
     * The props behind components/auth/AuthRoleChooser.vue: one card per
     * side, each linking to that side's own sign-in or sign-up form.
     *
     * @param  array{prefix: string, label: string, url: string}|null  $footer
     * @return array<string, mixed>
     */
    public static function chooser(bool $registering, ?array $footer = null): array
    {
        return [
            'options' => collect(AuthAudience::cases())
                ->map(fn (AuthAudience $audience): array => [
                    'label' => $audience->label(),
                    'description' => $audience->description(),
                    // The same line icons the dashboards draw, rendered once here.
                    'icon' => view('components.nav-icon', ['name' => $audience->icon()])->render(),
                    'url' => $registering ? $audience->registerUrl() : $audience->loginUrl(),
                ])
                ->all(),
            'footer' => $footer,
        ];
    }

    /**
     * The side a visitor picked, from ?as=. Null means they have not chosen
     * yet — unless the form already came back with errors or old input, in
     * which case the form is what they need to see again, not the question.
     */
    public static function audience(Request $request): ?AuthAudience
    {
        return AuthAudience::tryFrom($request->string('as')->toString());
    }

    public static function isReturningWithInput(Request $request): bool
    {
        return $request->session()->has('errors') || $request->session()->hasOldInput();
    }
}
