<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * The email is the key to a password reset, so changing it asks for the
     * current password. Someone who walks up to an unlocked browser must not
     * be able to move the account to their own inbox.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $user = $this->user();
        $emailChanged = $this->string('email')->toString() !== $user->email;

        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($user)],
            'current_password' => $emailChanged && $user->hasPassword()
                ? ['required', 'current_password']
                : ['nullable'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('fields.nama'),
            'phone' => __('fields.nombor_telefon'),
            'email' => __('fields.emel'),
            'current_password' => __('fields.kata_laluan_semasa'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Masukkan kata laluan semasa anda untuk menukar emel.',
        ];
    }
}
