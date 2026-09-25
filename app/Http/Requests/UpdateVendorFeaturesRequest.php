<?php

namespace App\Http\Requests;

use App\Enums\VendorFeature;
use App\Support\VendorFeatureSettings;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVendorFeaturesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * One checkbox per plan and feature, posted as features[<plan>][<feature>].
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'features' => ['nullable', 'array:'.implode(',', VendorFeatureSettings::PLANS)],
            'features.*' => ['array:'.implode(',', array_column(VendorFeature::cases(), 'value'))],
            'features.*.*' => ['boolean'],
        ];
    }

    /**
     * Every plan and feature, so an unticked box is saved as closed.
     *
     * @return array<string, bool>
     */
    public function settings(): array
    {
        return collect(VendorFeatureSettings::PLANS)
            ->crossJoin(VendorFeature::cases())
            ->mapWithKeys(fn (array $pair): array => [
                VendorFeatureSettings::key($pair[0], $pair[1]) => $this->boolean("features.{$pair[0]}.{$pair[1]->value}"),
            ])
            ->all();
    }
}
