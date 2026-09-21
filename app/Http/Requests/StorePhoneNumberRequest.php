<?php

namespace App\Http\Requests;

use App\Support\PhoneNumber;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StorePhoneNumberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'phone' => [
                'required',
                'string',
                'max:30',
                /** A number we cannot turn into a wa.me link is of no use as a lead. */
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (PhoneNumber::normalise((string) $value) === null) {
                        $fail('Masukkan nombor telefon Malaysia yang sah, contoh 012-345 6789.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['phone' => __('fields.nombor_telefon')];
    }
}
