<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'icon', 'examples', 'sort_order', 'is_active'])]
class Category extends Model
{
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

    public function illustrationUrl(): ?string
    {
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
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
