<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCameraSettingsRequest extends FormRequest
{
    /** The whole-number settings and their bounds. */
    public const NUMBERS = [
        'basic_price' => [1, 10000],
        'pro_price' => [1, 10000],
        'basic_max_photos' => [10, 100000],
        'basic_photo_px' => [800, 4096],
        'pro_photo_px' => [1200, 8192],
        'pro_video_max_mb' => [10, 500],
        'pro_video_max_seconds' => [10, 1800],
        'retention_days' => [1, 90],
        'pro_fair_use_gb' => [1, 1000],
    ];

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['nullable', 'boolean'],
            ...collect(self::NUMBERS)->map(fn (array $bounds): array => ['required', 'integer', 'min:'.$bounds[0], 'max:'.$bounds[1]])->all(),
        ];
    }

    /**
     * @return array<string, int|bool>
     */
    public function settings(): array
    {
        return [
            'enabled' => $this->boolean('enabled'),
            ...collect(self::NUMBERS)->map(fn (array $bounds, string $key): int => (int) $this->validated($key))->all(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return collect(self::NUMBERS)->mapWithKeys(fn (array $bounds, string $key): array => [$key => __('fields.camera_'.$key)])->all();
    }
}
