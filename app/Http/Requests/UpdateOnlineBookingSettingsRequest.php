<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOnlineBookingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['nullable', 'boolean'],
            'hold_hours' => ['required', 'integer', 'min:1', 'max:72'],
            'calendar_fresh_days' => ['required', 'integer', 'min:1', 'max:60'],
        ];
    }

    /**
     * @return array<string, int|bool>
     */
    public function settings(): array
    {
        return [
            'enabled' => $this->boolean('enabled'),
            'hold_hours' => (int) $this->validated('hold_hours'),
            'calendar_fresh_days' => (int) $this->validated('calendar_fresh_days'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'hold_hours' => __('fields.tempoh_pegang'),
            'calendar_fresh_days' => __('fields.tempoh_sah_kalendar'),
        ];
    }
}
