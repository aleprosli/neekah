<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateAccountPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Someone who signed in with Google has no password yet, so they set one
     * without being asked for a password they never had.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'current_password' => $this->user()->hasPassword() ? ['required', 'current_password'] : ['nullable'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'current_password' => __('fields.kata_laluan_semasa'),
            'password' => __('fields.kata_laluan_baru'),
        ];
    }
}
