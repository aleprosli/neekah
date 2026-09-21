<?php

namespace App\Http\Requests;

use App\Support\Locales;
use Illuminate\Foundation\Http\FormRequest;

class StoreChecklistSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'array'],
            'title.'.Locales::DEFAULT => ['required', 'string', 'max:80'],
            'title.*' => ['nullable', 'string', 'max:80'],
            'icon' => ['nullable', 'string', 'max:8'],
            'note' => ['nullable', 'array'],
            'note.*' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesForSection(): array
    {
        return [
            ...$this->safe()->only(['title', 'icon', 'note']),
            'is_active' => $this->boolean('is_active'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title.'.Locales::DEFAULT => __('fields.nama_fasa'),
            'icon' => __('fields.ikon'),
            'note' => __('fields.nota'),
        ];
    }
}
