<?php

namespace App\Models;

use App\Actions\StoreOptimizedImage;
use App\Casts\Translatable;
use App\Models\Concerns\HasTranslatedText;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'icon', 'image', 'examples', 'sort_order', 'is_active'])]
class Category extends Model
{
    use HasTranslatedText;

    /**
     * The drawing for each category slug. Pelamin and Decoration share the
     * floral-arch illustration; a slug that is not here falls back to its emoji.
     *
     * @var array<string, string>
     */
    public const ILLUSTRATIONS = [
        'catering' => 'catering',
        'pelamin' => 'decoration',
        'decoration' => 'decoration',
        'photography' => 'photography',
        'videography' => 'videography',
        'emcee' => 'emcee',
        'makeup' => 'makeup',
        'bridal' => 'bridal',
        'venue' => 'venue',
        'cake' => 'cake',
        'entertainment' => 'entertainment',
        'invitation' => 'invitation',
    ];

    /**
     * The picture shown for this category: what an admin uploaded, else the
     * drawing that ships with the slug. Falls back to the emoji when neither
     * exists, which is what a brand new category starts with.
     */
    public function illustrationUrl(): ?string
    {
        if (filled($this->image)) {
            return StoreOptimizedImage::thumbnailUrl($this->image);
        }

        $file = self::ILLUSTRATIONS[$this->slug] ?? null;

        return $file ? asset('img/icon/'.$file.'.svg') : null;
    }

    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name' => Translatable::class,
            'examples' => Translatable::class,
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        // sort_order is what actually orders these; the name was only a
        // tiebreak, and sorting by it now would sort by JSON text.
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
