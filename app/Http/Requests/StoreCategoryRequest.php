<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:60', Rule::unique(Category::class, 'name')->ignore($this->route('category'))],
            'icon' => ['required', 'string', 'max:8'],
            'examples' => ['nullable', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'between:0,999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesForCategory(): array
    {
        return [
            ...$this->safe()->only(['name', 'icon', 'examples']),
            'slug' => $this->route('category')?->slug ?? Str::slug($this->string('name')),
            'sort_order' => $this->integer('sort_order'),
            'is_active' => $this->boolean('is_active'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama kategori',
            'icon' => 'ikon',
            'examples' => 'contoh',
            'sort_order' => 'susunan',
        ];
    }
}
