<?php

namespace App\Http\Requests;

use App\Models\Package;
use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCustomer() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Vendor $vendor */
        $vendor = $this->route('vendor');

        return [
            'package_id' => ['required', Rule::exists(Package::class, 'id')->where('vendor_id', $vendor->id)->where('is_active', true)],
            'event_date' => ['required', 'date', 'after:today'],
            'wedding_id' => ['nullable', Rule::exists('weddings', 'id')->where('user_id', $this->user()->id)],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Vendor $vendor */
                $vendor = $this->route('vendor');

                if ($validator->errors()->has('event_date') || ! $this->filled('event_date')) {
                    return;
                }

                if (! $vendor->isAvailableOn($this->date('event_date'))) {
                    $validator->errors()->add('event_date', 'Vendor ini tidak tersedia pada tarikh tersebut. Sila pilih tarikh lain.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'package_id' => 'pakej',
            'event_date' => 'tarikh majlis',
            'wedding_id' => 'majlis',
            'notes' => 'nota',
        ];
    }
}
