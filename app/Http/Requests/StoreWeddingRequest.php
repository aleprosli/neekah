<?php

namespace App\Http\Requests;

use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWeddingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $wedding = $this->route('wedding');

        return $wedding
            ? ($this->user()?->can('update', $wedding) ?? false)
            : ($this->user()?->isCustomer() ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'event_date' => ['required', 'date', 'after:today'],
            'city' => ['required', 'string', 'max:80'],
            'state' => ['required', Rule::in(Vendor::STATES)],
            'budget' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'nama majlis',
            'event_date' => 'tarikh majlis',
            'city' => 'bandar',
            'state' => 'negeri',
            'budget' => 'bajet',
            'notes' => 'nota',
        ];
    }
}
