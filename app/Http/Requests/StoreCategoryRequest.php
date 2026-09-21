<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Support\ImageSettings;
use App\Support\Locales;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreCategoryRequest extends FormRequest
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
            'image' => ['nullable', ...$images->uploadRules()],
            // One field per language. The default one is required; the rest
            // may be filled in later, and fall back to it until they are.
            'name' => ['required', 'array'],
            'name.'.Locales::DEFAULT => ['required', 'string', 'max:60', function (string $attribute, mixed $value, Closure $fail): void {
                // Rule::unique cannot see inside a JSON column, and two
                // categories with the same name would be indistinguishable in
                // every dropdown on the site.
                $taken = Category::whereTranslated('name', (string) $value)
                    ->when($this->route('category'), fn ($query, Category $category) => $query->whereKeyNot($category))
                    ->exists();

                if ($taken) {
                    $fail('Kategori dengan nama ini sudah wujud.');
                }
            }],
            'name.*' => ['nullable', 'string', 'max:60'],
            'examples' => ['nullable', 'array'],
            'examples.*' => ['nullable', 'string', 'max:120'],
            'icon' => ['required', 'string', 'max:8'],
            'sort_order' => ['nullable', 'integer', 'between:0,999'],
            'is_active' => ['nullable', 'boolean'],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesForCategory(): array
    {
        return [
            ...$this->safe()->only(['name', 'icon', 'examples']),
            'slug' => $this->route('category')?->slug ?? Str::slug((string) $this->input('name.'.Locales::DEFAULT)),
            ...($this->has('sort_order') ? ['sort_order' => $this->integer('sort_order')] : []),
            'is_active' => $this->boolean('is_active'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name.'.Locales::DEFAULT => __('fields.nama_kategori'),
            'image' => __('fields.gambar_kategori'),
            'icon' => __('fields.ikon'),
            'examples' => __('fields.contoh'),
            'sort_order' => __('fields.susunan'),
        ];
    }
}
