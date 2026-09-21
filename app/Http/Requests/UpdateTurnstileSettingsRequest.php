<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTurnstileSettingsRequest extends FormRequest
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
            'enabled' => ['nullable', 'boolean'],
            'site_key' => ['nullable', 'string', 'max:120'],
            'secret_key' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * A blank secret means "keep the one already saved", so an admin can edit
     * the rest of the form without the secret ever being rendered in the page.
     *
     * @return array<string, string|bool>
     */
    public function settings(): array
    {
        $values = [
            'enabled' => $this->boolean('enabled'),
            'site_key' => trim((string) $this->validated('site_key')),
        ];

        if (($secret = trim((string) $this->validated('secret_key'))) !== '') {
            $values['secret_key'] = $secret;
        }

        return $values;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'site_key' => __('fields.site_key'),
            'secret_key' => __('fields.secret_key'),
        ];
    }
}
