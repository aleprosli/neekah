<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\AccessCode;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Signing in to the Neekah Pro app. The same checks as the website's form
 * (rate limit per email and IP, the staging access code), minus Turnstile,
 * which an app cannot show; nothing is written to a session.
 */
class ApiLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
            'access_code' => [new AccessCode],
        ];
    }

    /**
     * The user these credentials belong to.
     *
     * @throws ValidationException
     */
    public function authenticatedUser(): User
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            event(new Lockout($this));

            throw ValidationException::withMessages([
                'email' => __('validation.custom.too_many_attempts', ['minutes' => ceil(RateLimiter::availableIn($this->throttleKey()) / 60)]),
            ]);
        }

        $user = User::query()->where('email', $this->string('email')->toString())->first();

        if (! $user || ! $user->password || ! Hash::check($this->string('password')->toString(), $user->password)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages(['email' => __('validation.custom.credentials')]);
        }

        RateLimiter::clear($this->throttleKey());

        return $user;
    }

    private function throttleKey(): string
    {
        return 'api|'.Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
