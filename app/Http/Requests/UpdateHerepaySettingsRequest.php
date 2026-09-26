<?php

namespace App\Http\Requests;

use App\Support\Herepay\HerepayGateway;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateHerepaySettingsRequest extends FormRequest
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
            'enabled' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Switching Herepay on with a key missing would open a checkout that fails
     * for every vendor, so it is refused, naming what to put in .env.
     *
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $missing = app(HerepayGateway::class)->missingKeys();

                if ($this->boolean('enabled') && $missing !== []) {
                    $validator->errors()->add('enabled', __('validation.custom.herepay_keys_missing', ['keys' => implode(', ', $missing)]));
                }
            },
        ];
    }

    /**
     * @return array<string, bool>
     */
    public function settings(): array
    {
        return ['enabled' => $this->boolean('enabled')];
    }
}
