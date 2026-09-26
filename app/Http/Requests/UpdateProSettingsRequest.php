<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProSettingsRequest extends FormRequest
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
            'monthly_price' => ['required', 'integer', 'min:1', 'max:100000'],
            'yearly_price' => ['required', 'integer', 'min:1', 'max:100000'],
        ];
    }

    /**
     * @return array<string, int|bool>
     */
    public function settings(): array
    {
        return [
            'enabled' => $this->boolean('enabled'),
            'monthly_price' => (int) $this->validated('monthly_price'),
            'yearly_price' => (int) $this->validated('yearly_price'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'monthly_price' => __('fields.pro_monthly_price'),
            'yearly_price' => __('fields.pro_yearly_price'),
        ];
    }
}
