<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConnectHerepayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->user()->vendor) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'herepay_secret_key' => ['required', 'string', 'min:8', 'max:255'],
            'herepay_private_key' => ['required', 'string', 'min:8', 'max:255'],
            // Optional: only asking Herepay about a deposit again needs it.
            'herepay_api_key' => ['nullable', 'string', 'min:8', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'herepay_secret_key' => __('fields.herepay_secret_key'),
            'herepay_private_key' => __('fields.herepay_private_key'),
        ];
    }
}
