<?php

namespace App\Models;

use Database\Factories\WeddingTaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['wedding_id', 'category_id', 'title', 'notes', 'due_date', 'completed_at', 'completed_by', 'sort_order'])]
class WeddingTask extends Model
{
    /** @use HasFactory<WeddingTaskFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function wedding(): BelongsTo
    {
        return $this->belongsTo(Wedding::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    #[Scope]
    protected function outstanding(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }

    public function isDone(): bool
    {
        return $this->completed_at !== null;
    }

    public function isOverdue(): bool
    {
        return ! $this->isDone() && $this->due_date !== null && $this->due_date->isPast();
    }
}
