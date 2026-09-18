<?php

namespace App\Models;

use Database\Factories\ChecklistSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One phase of the master checklist every couple starts from, e.g. "Urusan
 * Borang & Dokumen Nikah". Admin owns these; a wedding's own tasks point back
 * at the section they came from so the couple sees the same phases.
 */
#[Fillable(['title', 'icon', 'note', 'sort_order', 'is_active'])]
class ChecklistSection extends Model
{
    /** @use HasFactory<ChecklistSectionFactory> */
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

    public function items(): HasMany
    {
        return $this->hasMany(ChecklistItem::class);
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
