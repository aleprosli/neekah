<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** The client declining a quotation from its public page, a reason optional. */
class DeclineQuotationRequest extends FormRequest
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
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'reason' => __('fields.sebab'),
        ];
    }
}
