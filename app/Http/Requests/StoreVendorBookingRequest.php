<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A vendor records a booking for a customer after an off-platform discussion.
 */
class StoreVendorBookingRequest extends FormRequest
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
            'customer_email' => ['required', 'email', Rule::exists(User::class, 'email')->where('role', UserRole::Customer->value)],
            'package_id' => ['required', Rule::exists(Package::class, 'id')->where('vendor_id', $this->user()->vendor->id)->where('is_active', true)],
            'event_date' => ['required', 'date', 'after:today'],
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
                if ($validator->errors()->has('event_date') || ! $this->filled('event_date')) {
                    return;
                }

                if (! $this->user()->vendor->isAvailableOn($this->date('event_date'))) {
                    $validator->errors()->add('event_date', 'Anda sudah ada tempahan atau tarikh ditutup pada hari tersebut.');
                }
            },
        ];
    }

    public function customer(): User
    {
        return User::where('email', $this->string('customer_email'))->sole();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'customer_email' => 'emel pelanggan',
            'package_id' => 'pakej',
            'event_date' => 'tarikh majlis',
            'notes' => 'nota',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_email.exists' => 'Tiada akaun pengantin dengan emel ini. Minta pelanggan daftar di Neekah dahulu.',
        ];
    }
}
