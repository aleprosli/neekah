<?php

namespace App\Http\Requests;

use App\Support\ImageSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateImageSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * The upload ceiling stays at 15 MB because the server's own PHP and nginx
     * limits sit just above it; raising this alone would only produce a vague
     * "413 Request Entity Too Large" instead of a form error.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'max_dimension' => ['required', 'integer', 'between:800,4000'],
            'thumbnail_width' => ['required', 'integer', 'between:200,1200'],
            'quality' => ['required', 'integer', 'between:40,95'],
            'format' => ['required', Rule::in(array_keys(ImageSettings::FORMATS))],
            'max_upload_mb' => ['required', 'integer', 'between:1,15'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'max_dimension' => 'saiz maksimum',
            'thumbnail_width' => 'lebar thumbnail',
            'quality' => 'kualiti',
            'format' => 'format',
            'max_upload_mb' => 'had saiz muat naik',
        ];
    }
}
