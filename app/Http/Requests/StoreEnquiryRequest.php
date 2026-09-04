<?php

namespace App\Http\Requests;

use App\Models\Package;
use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEnquiryRequest extends FormRequest
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
            'message' => ['required', 'string', 'min:10', 'max:2000'],
            'event_date' => ['nullable', 'date', 'after:today'],
            'package_id' => ['nullable', Rule::exists(Package::class, 'id')->where('vendor_id', $vendor->id)],
            'wedding_id' => ['nullable', Rule::exists('weddings', 'id')->where('user_id', $this->user()->id)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'message' => 'mesej',
            'event_date' => 'tarikh majlis',
            'package_id' => 'pakej',
            'wedding_id' => 'majlis',
        ];
    }
}
