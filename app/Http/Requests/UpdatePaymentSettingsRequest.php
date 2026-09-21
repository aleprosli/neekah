<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
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
            ...collect(PaymentMethod::cases())
                ->mapWithKeys(fn (PaymentMethod $method): array => [$method->settingKey() => ['nullable', 'boolean']])
                ->all(),
            'instructions' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string|bool>
     */
    public function settings(): array
    {
        return [
            ...collect(PaymentMethod::cases())
                ->mapWithKeys(fn (PaymentMethod $method): array => [$method->settingKey() => $this->boolean($method->settingKey())])
                ->all(),
            'instructions' => trim((string) $this->validated('instructions')),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'instructions' => __('fields.arahan_bayaran'),
        ];
    }
}
