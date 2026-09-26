<?php

namespace App\Http\Requests;

use App\Support\ProSettings;
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
            'elite_enabled' => ['nullable', 'boolean'],
            'elite_bonus_tokens' => ['nullable', 'integer', 'min:0', 'max:365'],
            'elite_row_size' => ['nullable', 'integer', 'min:0', 'max:12'],
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
            // The settings page always posts these; a caller that leaves one
            // out keeps what is there.
            'elite_enabled' => $this->has('elite_enabled') ? $this->boolean('elite_enabled') : app(ProSettings::class)->eliteEnabled(),
            'elite_bonus_tokens' => $this->filled('elite_bonus_tokens') ? (int) $this->validated('elite_bonus_tokens') : app(ProSettings::class)->eliteBonusTokens(),
            'elite_row_size' => $this->filled('elite_row_size') ? (int) $this->validated('elite_row_size') : app(ProSettings::class)->eliteRowSize(),
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
            'elite_bonus_tokens' => __('fields.pro_elite_bonus_tokens'),
            'elite_row_size' => __('fields.pro_elite_row_size'),
        ];
    }
}
