<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'manual_transfer_enabled' => ['nullable', 'boolean'],
            'bank_name' => ['nullable', 'string', 'max:80'],
            'account_holder' => ['nullable', 'string', 'max:120'],
            'account_number' => ['nullable', 'string', 'max:40'],
            'instructions' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string|bool>
     */
    public function settings(): array
    {
        return [
            'manual_transfer_enabled' => $this->boolean('manual_transfer_enabled'),
            'bank_name' => trim((string) $this->validated('bank_name')),
            'account_holder' => trim((string) $this->validated('account_holder')),
            'account_number' => trim((string) $this->validated('account_number')),
            'instructions' => trim((string) $this->validated('instructions')),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'bank_name' => 'nama bank',
            'account_holder' => 'nama pemegang akaun',
            'account_number' => 'nombor akaun',
            'instructions' => 'arahan bayaran',
        ];
    }
}
