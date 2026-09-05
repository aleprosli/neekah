<?php

namespace App\Http\Requests;

use App\Enums\GuestGroup;
use App\Enums\GuestSide;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWeddingGuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('wedding')) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:30'],
            'side' => ['required', Rule::enum(GuestSide::class)],
            'group' => ['required', Rule::enum(GuestGroup::class)],
            'pax_invited' => ['required', 'integer', 'between:1,20'],
            'notes' => ['nullable', 'string', 'max:300'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama tetamu',
            'phone' => 'nombor telefon',
            'side' => 'pihak',
            'group' => 'kumpulan',
            'pax_invited' => 'bilangan jemputan',
            'notes' => 'nota',
        ];
    }
}
