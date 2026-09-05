<?php

namespace App\Models;

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

    public function layout(): string
    {
        return $this->design['layout'] ?? 'centered';
    }

    public function ornament(): string
    {
        return $this->design['ornament'] ?? 'floral';
    }

    public function motion(): string
    {
        return $this->design['motion'] ?? 'petals';
    }

    public function eyebrow(): string
    {
        return $this->design['eyebrow'] ?? 'Walimatulurus';
    }

    public function isDark(): bool
    {
        return (bool) ($this->design['palette']['dark'] ?? false);
    }

    /**
     * The palette and type choices as CSS custom properties, so one stylesheet
     * can render every template without a class per design.
     */
    public function cssVariables(): string
    {
        $palette = $this->design['palette'] ?? [];
        $type = $this->design['type'] ?? [];

        $variables = [
            '--nk-page' => $palette['page'] ?? '#ffffff',
            '--nk-ink' => $palette['ink'] ?? '#2b2b2b',
            '--nk-name' => $palette['name'] ?? '#2b2b2b',
            '--nk-accent' => $palette['accent'] ?? '#c19a4b',
            '--nk-body' => $palette['body'] ?? '#5f5f5f',
            '--nk-muted' => $palette['muted'] ?? '#9a9a9a',
            '--nk-panel' => $palette['panel'] ?? '#ffffff',
            '--nk-line' => $palette['line'] ?? '#e5e5e5',
            '--nk-button-bg' => $palette['buttonBg'] ?? '#2b2b2b',
            '--nk-button-text' => $palette['buttonText'] ?? '#ffffff',
            '--nk-script' => $type['script'] ?? "'Great Vibes', cursive",
            '--nk-serif' => $type['body'] ?? "'Cormorant Garamond', serif",
        ];

        return collect($variables)
            ->map(fn (string $value, string $key): string => $key.':'.$value)
            ->implode(';');
    }

    /**
     * @return array<int, string>
     */
    public function petalColors(): array
    {
        return $this->design['petals'] ?? ['#f2c6d4', '#e9b8c6', '#f6dcc2'];
    }
}
