<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The code a visitor must type to sign in or sign up while the site is closed
 * to the public, as staging is to anyone outside the team.
 *
 * NEEKAH_ACCESS_CODE sets it. Left empty, as on production, nothing is asked
 * and the rule passes untouched.
 */
class AccessCode implements ValidationRule
{
    /** Run even when the field is missing, so leaving it out is no way round it. */
    public bool $implicit = true;

    public static function isRequired(): bool
    {
        return filled(config('neekah.access_code'));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::isRequired()) {
            return;
        }

        if (! is_string($value) || ! hash_equals((string) config('neekah.access_code'), trim($value))) {
            $fail(__('validation.custom.access_code'));
        }
    }
}
