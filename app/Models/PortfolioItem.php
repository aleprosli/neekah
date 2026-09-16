<?php

namespace App\Models;

use App\Actions\StoreOptimizedImage;
use Database\Factories\PortfolioItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['vendor_id', 'path', 'caption', 'sort_order', 'is_visible'])]
class PortfolioItem extends Model
{
    /** @use HasFactory<PortfolioItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_visible' => 'boolean'];
    }

    /** Photos the vendor chose to show, in the order they arranged them. */
    #[Scope]
    protected function visible(Builder $query): Builder
    {
        return $query->where('is_visible', true)->orderBy('sort_order');
    }

    public function thumbnailUrl(): ?string
    {
        return StoreOptimizedImage::thumbnailUrl($this->path);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
