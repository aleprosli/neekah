<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUnavailableDateRequest extends FormRequest
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
            'from' => ['required', 'date', 'after_or_equal:today'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'reason' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'from' => 'tarikh mula',
            'to' => 'tarikh akhir',
            'reason' => 'sebab',
        ];
    }
}
