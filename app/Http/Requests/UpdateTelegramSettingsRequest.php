<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTelegramSettingsRequest extends FormRequest
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
            'bot_token' => ['nullable', 'string', 'max:120'],
            'chat_id' => ['nullable', 'string', 'max:60'],
        ];
    }

    /**
     * A blank bot token means "keep the saved one", the same as the Turnstile
     * secret: neither is ever rendered back into the page.
     *
     * @return array<string, string|bool>
     */
    public function settings(): array
    {
        $values = [
            'enabled' => $this->boolean('enabled'),
            'chat_id' => trim((string) $this->validated('chat_id')),
        ];

        if (($token = trim((string) $this->validated('bot_token'))) !== '') {
            $values['bot_token'] = $token;
        }

        return $values;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'bot_token' => 'bot token',
            'chat_id' => 'chat id',
        ];
    }
}
