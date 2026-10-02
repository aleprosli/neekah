<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The client accepting a quotation from its public page, with no account:
 * the token in the address is what lets them. Their full name and a tick
 * are what is kept as their agreement.
 */
class AcceptQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'agree' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('fields.nama_penuh'),
            'agree' => __('fields.persetujuan'),
        ];
    }
}
