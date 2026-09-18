<?php

namespace App\Http\Requests;

use App\Models\ChecklistItem;
use App\Models\ChecklistSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Sections and the items inside them arrive in one payload, so a drag can
     * never leave half an order behind.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'sections' => ['nullable', 'array', 'max:100'],
            'sections.*.id' => ['required', Rule::exists(ChecklistSection::class, 'id')],
            'sections.*.sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'items' => ['nullable', 'array', 'max:500'],
            'items.*.id' => ['required', Rule::exists(ChecklistItem::class, 'id')],
            'items.*.checklist_section_id' => ['required', Rule::exists(ChecklistSection::class, 'id')],
            'items.*.sort_order' => ['required', 'integer', 'min:0', 'max:999'],
        ];
    }
}
