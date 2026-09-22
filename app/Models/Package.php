<?php

namespace App\Models;

use App\Actions\StoreOptimizedImage;
use App\Support\ContentVersion;
use Database\Factories\PackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['vendor_id', 'name', 'description', 'image', 'price', 'duration', 'features', 'is_active', 'sort_order'])]
class Package extends Model
{
    /** @use HasFactory<PackageFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The full-size image, for the package card on the vendor's public page.
     */
    /**
     * A package is read on the vendor's own page and nowhere else, so a price
     * change throws away that vendor's cached payload and no one else's.
     */
    protected static function booted(): void
    {
        $bump = function (self $model): void {
            ContentVersion::bumpVendor($model->vendor_id);
        };

        static::saved($bump);
        static::deleted($bump);
    }

    public function imageUrl(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }

    /**
     * The narrower copy, for the vendor's own package list.
     */
    public function thumbnailUrl(): ?string
    {
        return StoreOptimizedImage::thumbnailUrl($this->image);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
