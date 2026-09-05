<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRsvpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:30'],
            'attending' => ['required', 'boolean'],
            'pax' => ['required_if:attending,1', 'nullable', 'integer', 'between:1,20'],
            'message' => ['nullable', 'string', 'max:300'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'phone' => 'nombor telefon',
            'attending' => 'kehadiran',
            'pax' => 'bilangan orang',
            'message' => 'ucapan',
        ];
    }
}
