<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\ChecklistSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChecklistItemRequest extends FormRequest
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
            'checklist_section_id' => ['required', Rule::exists(ChecklistSection::class, 'id')],
            'category_id' => ['nullable', Rule::exists(Category::class, 'id')],
            'group' => ['nullable', 'string', 'max:60'],
            'title' => ['required', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:500'],
            // 0 means the event day itself; null means the task has no deadline.
            'months_before' => ['nullable', 'integer', 'between:0,36'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attributesForItem(): array
    {
        return [
            ...$this->safe()->only(['checklist_section_id', 'category_id', 'group', 'title', 'notes']),
            'months_before' => $this->filled('months_before') ? $this->integer('months_before') : null,
            'is_active' => $this->boolean('is_active'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'checklist_section_id' => 'fasa',
            'category_id' => 'kategori',
            'group' => 'kumpulan',
            'title' => 'tugasan',
            'notes' => 'nota',
            'months_before' => 'bulan sebelum majlis',
        ];
    }
}
