<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBoostSettingsRequest extends FormRequest
{
    /** The whole-number settings and their bounds. */
    public const NUMBERS = [
        'welcome_tokens' => [0, 365],
        'pro_monthly_tokens' => [0, 365],
        'max_days' => [1, 365],
        'small_tokens' => [1, 10000],
        'small_price' => [1, 100000],
        'large_tokens' => [1, 10000],
        'large_price' => [1, 100000],
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
        return collect(self::NUMBERS)->mapWithKeys(fn (array $bounds, string $key): array => [$key => __('fields.boost_'.$key)])->all();
    }
}
