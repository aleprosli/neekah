<?php

namespace App\Http\Requests;

use App\Support\ImageSettings;
use Illuminate\Foundation\Http\FormRequest;

class StorePostImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(ImageSettings $images): array
    {
        return [
            'image' => ['required', ...$images->uploadRules()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'image' => __('fields.gambar'),
        ];
    }
}
