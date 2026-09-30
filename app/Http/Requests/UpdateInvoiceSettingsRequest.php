<?php

namespace App\Http\Requests;

use App\Support\InvoiceSettings;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Every field is optional: a blank one falls back to the contact details,
     * or is left off the document.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['nullable', 'string', 'max:120'],
            'registration_no' => ['nullable', 'string', 'max:60'],
            'tax_no' => ['nullable', 'string', 'max:60'],
            'address' => ['nullable', 'string', 'max:300'],
            'email' => ['nullable', 'email', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            ...collect(InvoiceSettings::localisedKeys('note'))
                ->mapWithKeys(fn (string $key): array => [$key => ['nullable', 'string', 'max:300']])
                ->all(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function settings(): array
    {
        return collect($this->validated())
            ->map(fn (?string $value): string => trim((string) $value))
            ->all();
    }
}
