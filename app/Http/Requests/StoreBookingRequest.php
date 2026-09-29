<?php

namespace App\Http\Requests;

use App\Models\Package;
use App\Models\Vendor;
use App\Rules\Turnstile;
use App\Support\VendorAvailability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBookingRequest extends FormRequest
{
    /**
     * Only a vendor taking online bookings answers at all: for every other
     * vendor this address does not exist, before anything is validated.
     */
    public function authorize(): bool
    {
        abort_unless(VendorAvailability::for($this->route('vendor'))->acceptsOnlineBookings(), 404);

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
            // The vendor's deposit terms are shown before paying, and agreed to.
            'terms' => filled($vendor->bookingSettingsOrDefault()->deposit_terms) ? ['accepted'] : ['nullable'],
            'wedding_id' => ['nullable', Rule::in($this->user()->weddings()->pluck('weddings.id'))],
            'notes' => ['nullable', 'string', 'max:500'],
            'cf-turnstile-response' => [app(Turnstile::class)],
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

                $day = VendorAvailability::for($vendor)->dayFor($this->date('event_date'));

                if (! $day->isOpen()) {
                    $validator->errors()->add('event_date', $day->refusal());
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
            'package_id' => __('fields.pakej'),
            'event_date' => __('fields.tarikh_majlis'),
            'wedding_id' => __('fields.majlis'),
            'notes' => __('fields.nota'),
            'terms' => __('fields.terma_deposit'),
        ];
    }
}
