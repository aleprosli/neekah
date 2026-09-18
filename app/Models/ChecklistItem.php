<?php

namespace App\Models;

use Database\Factories\ChecklistItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of the master checklist. `group` is the sub-heading it sits under
 * inside its section ("Dokumen Asas", "Pengantin Perempuan"), and
 * `months_before` is how long before the event the task is due.
 */
#[Fillable(['checklist_section_id', 'category_id', 'group', 'title', 'notes', 'months_before', 'sort_order', 'is_active'])]
class ChecklistItem extends Model
{
    /** @use HasFactory<ChecklistItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(ChecklistSection::class, 'checklist_section_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
