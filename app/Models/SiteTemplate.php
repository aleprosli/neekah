<?php

namespace App\Models;

use App\Support\CardArt;
use App\Support\CardDesign;
use Database\Factories\SiteTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['slug', 'name', 'style', 'description', 'design', 'sort_order', 'is_active'])]
class SiteTemplate extends Model
{
    /** @use HasFactory<SiteTemplateFactory> */
    use HasFactory;

    /**
     * Layout skeletons. Each one is a Blade partial under sites/layouts.
     *
     * @var array<int, string>
     */
    public const LAYOUTS = ['centered', 'arch', 'frame', 'banner', 'split', 'minimal', 'ribbon', 'mosaic'];

    /**
     * Corner and border ornaments, drawn as SVG.
     *
     * @var array<int, string>
     */
    public const ORNAMENTS = ['floral', 'vine', 'geometric', 'deco', 'botanical', 'none'];

    /**
     * @var array<int, string>
     */
    public const MOTIONS = CardArt::MOTIONS;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'design' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
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

    /**
     * This template on its own, with nothing overridden — what the gallery and
     * the thumbnails render.
     */
    public function toCardDesign(): CardDesign
    {
        return CardDesign::make($this);
    }

    public function layout(): string
    {
        return $this->toCardDesign()->layout();
    }

    public function ornament(): string
    {
        return $this->toCardDesign()->ornament();
    }

    public function motion(): string
    {
        return $this->toCardDesign()->motion();
    }

    public function eyebrow(): string
    {
        return $this->toCardDesign()->eyebrow();
    }

    /**
     * Whether the card opens with the Bismillah, as most Malay invitations do.
     */
    public function showsBismillah(): bool
    {
        return $this->toCardDesign()->showsBismillah();
    }

    public function isDark(): bool
    {
        return $this->toCardDesign()->isDark();
    }

    public function cssVariables(): string
    {
        return $this->toCardDesign()->cssVariables();
    }

    /**
     * @return array<int, string>
     */
    public function petalColors(): array
    {
        return $this->toCardDesign()->petalColors();
    }
}
