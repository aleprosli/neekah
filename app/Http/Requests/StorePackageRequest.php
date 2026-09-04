<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $package = $this->route('package');

        return $package
            ? ($this->user()?->can('update', $package) ?? false)
            : ($this->user()?->can('update', $this->user()->vendor) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'duration' => ['nullable', 'string', 'max:60'],
            'features' => ['required', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Features are entered one per line.
     *
     * @return array<int, string>
     */
    public function featureList(): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $this->string('features')->toString()))
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama pakej',
            'price' => 'harga',
            'duration' => 'tempoh',
            'features' => 'kandungan pakej',
        ];
    }
}
