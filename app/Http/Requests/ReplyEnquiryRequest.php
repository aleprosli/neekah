<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReplyEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('reply', $this->route('enquiry')) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'reply' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'reply' => 'balasan',
        ];
    }
}
