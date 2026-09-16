<?php

namespace App\Http\Requests;

use App\Models\PortfolioItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderPortfolioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->user()->vendor) ?? false;
    }

    /**
     * Each id is checked against this vendor's own photos, so a rearranged
     * payload can never touch someone else's portfolio.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'max:200'],
            'items.*.id' => ['required', Rule::exists(PortfolioItem::class, 'id')->where('vendor_id', $this->user()->vendor?->id)],
            'items.*.sort_order' => ['required', 'integer', 'min:0', 'max:1000'],
            'items.*.is_visible' => ['required', 'boolean'],
        ];
    }
}
