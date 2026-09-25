<?php

namespace App\Http\Requests;

use App\Enums\DepositType;
use App\Enums\PriceUnit;
use App\Models\VendorBookingSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateBookingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->user()->vendor) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['nullable', 'boolean'],
            'deposit_type' => ['required', Rule::enum(DepositType::class)],
            'deposit_value' => ['required', 'numeric', 'min:1', $this->input('deposit_type') === DepositType::Percent->value ? 'max:100' : 'max:9999999'],
            'max_per_day' => ['required', 'integer', 'min:1', 'max:'.VendorBookingSetting::MAX_PER_DAY],
            'available_weekdays' => ['required', 'array', 'min:1'],
            'available_weekdays.*' => ['integer', Rule::in(VendorBookingSetting::ALL_WEEKDAYS)],
            'min_lead_days' => ['required', 'integer', 'min:1', 'max:365'],
            'max_advance_months' => ['required', 'integer', 'min:1', 'max:36'],
            'deposit_terms' => ['nullable', 'string', 'max:3000'],
            'manual_instructions' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * A percentage of a per-pax price is a percentage of one guest's plate, not
     * of the booking, so a per-pax vendor sets a fixed deposit.
     *
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('deposit_type') === DepositType::Percent->value && $this->user()->vendor->price_unit === PriceUnit::Pax) {
                    $validator->errors()->add('deposit_type', __('validation.custom.pax_needs_fixed_deposit'));
                }
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        return [
            ...$this->safe()->except('enabled', 'available_weekdays'),
            'enabled' => $this->boolean('enabled'),
            'available_weekdays' => collect($this->validated('available_weekdays'))->map(fn (mixed $day): int => (int) $day)->unique()->sort()->values()->all(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'deposit_type' => __('fields.jenis_deposit'),
            'deposit_value' => __('fields.nilai_deposit'),
            'max_per_day' => __('fields.had_sehari'),
            'available_weekdays' => __('fields.hari_dibuka'),
            'min_lead_days' => __('fields.tempoh_minimum'),
            'max_advance_months' => __('fields.tempoh_maksimum'),
            'deposit_terms' => __('fields.terma_deposit'),
            'manual_instructions' => __('fields.butiran_bank'),
        ];
    }
}
