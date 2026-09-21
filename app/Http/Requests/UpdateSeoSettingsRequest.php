<?php

namespace App\Http\Requests;

use App\Support\Seo;
use App\Support\SeoSettings;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSeoSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * The limits match what Google will actually show before it truncates.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'tagline' => ['required', 'string', 'max:'.SeoSettings::TAGLINE_LIMIT],
            'description' => ['required', 'string', 'min:50', 'max:'.Seo::DESCRIPTION_LIMIT],
            'twitter' => ['nullable', 'string', 'max:30', 'regex:/^@?[A-Za-z0-9_]+$/'],
            // Optional: an empty English field falls back to the Malay one.
            'tagline_en' => ['nullable', 'string', 'max:'.SeoSettings::TAGLINE_LIMIT],
            'description_en' => ['nullable', 'string', 'min:50', 'max:'.Seo::DESCRIPTION_LIMIT],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'tagline' => 'tagline',
            'description' => 'penerangan',
            'twitter' => 'akaun X',
            'tagline_en' => 'tagline English',
            'description_en' => 'penerangan English',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'twitter.regex' => 'Akaun X hanya boleh mengandungi huruf, nombor dan garis bawah.',
        ];
    }
}
