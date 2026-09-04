<?php

namespace App\Http\Requests;

use App\Enums\ViolationType;
use App\Models\Booking;
use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportVendorRequest extends FormRequest
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
            'type' => ['required', Rule::enum(ViolationType::class)],
            'description' => ['required', 'string', 'min:20', 'max:2000'],
            'booking_id' => ['nullable', Rule::exists(Booking::class, 'id')->where('user_id', $this->user()->id)->where('vendor_id', $vendor->id)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => 'jenis pelanggaran',
            'description' => 'penerangan',
            'booking_id' => 'booking berkaitan',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'description.min' => 'Sila terangkan apa yang berlaku sekurang-kurangnya 20 aksara supaya admin boleh menyiasat.',
        ];
    }
}
