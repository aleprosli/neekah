<?php

namespace App\Http\Requests;

use App\Enums\VendorFeature;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorFeatureOverridesRequest extends FormRequest
{
    public const FOLLOW_PLAN = 'plan';

    public const OPEN = 'open';

    public const CLOSED = 'closed';

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
            'features' => ['required', 'array:'.implode(',', array_column(VendorFeature::cases(), 'value'))],
            'features.*' => ['required', Rule::in([self::FOLLOW_PLAN, self::OPEN, self::CLOSED])],
        ];
    }

    /**
     * Only the features that differ from the plan are stored; null when none do.
     *
     * @return array<string, bool>|null
     */
    public function overrides(): ?array
    {
        $overrides = collect($this->validated('features'))
            ->reject(fn (string $choice): bool => $choice === self::FOLLOW_PLAN)
            ->map(fn (string $choice): bool => $choice === self::OPEN)
            ->all();

        return $overrides === [] ? null : $overrides;
    }
}
