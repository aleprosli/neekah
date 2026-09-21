<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContactSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Every field is optional: a detail that has not been decided yet is simply
     * not shown, rather than blocking the rest of the form from being saved.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'address' => ['nullable', 'string', 'max:300'],
            'hours' => ['nullable', 'string', 'max:120'],
            'facebook' => ['nullable', 'url', 'max:200'],
            'instagram' => ['nullable', 'url', 'max:200'],
            'tiktok' => ['nullable', 'url', 'max:200'],
        ];
    }

    /**
     * Blank inputs are stored as empty strings, which is what the defaults use.
     *
     * @return array<string, string>
     */
    public function settings(): array
    {
        return collect($this->validated())
            ->map(fn (?string $value): string => trim((string) $value))
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'phone' => __('fields.nombor_telefon'),
            'whatsapp' => __('fields.nombor_whatsapp'),
            'email' => __('fields.emel'),
            'address' => __('fields.alamat'),
            'hours' => __('fields.waktu_operasi'),
        ];
    }
}
