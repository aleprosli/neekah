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
            'format' => ['required', Rule::in(ImageSettings::FORMAT_KEYS)],
            'max_upload_mb' => ['required', 'integer', 'between:1,15'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'max_dimension' => __('fields.saiz_maksimum'),
            'thumbnail_width' => __('fields.lebar_thumbnail'),
            'quality' => __('fields.kualiti'),
            'format' => __('fields.format'),
            'max_upload_mb' => __('fields.had_saiz_muat_naik'),
        ];
    }
}
