<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** The vendor withdrawing a sent contract nobody has signed. */
class VoidContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('void', $this->route('contract')) ?? false;
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
