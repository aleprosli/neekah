<?php

namespace App\Http\Requests;

use App\Support\ImageSettings;
use Illuminate\Foundation\Http\FormRequest;

class StoreWeddingSitePhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('wedding')) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(ImageSettings $images): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:12'],
            'images.*' => $images->uploadRules(),
            'caption' => ['nullable', 'string', 'max:160'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'images' => 'gambar',
            'images.*' => 'gambar',
            'caption' => 'kapsyen',
        ];
    }
}
