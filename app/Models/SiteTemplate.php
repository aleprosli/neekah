<?php

namespace App\Models;

use App\Casts\Translatable;
use App\Models\Concerns\HasTranslatedText;
use App\Support\Card\Fonts;
use App\Support\Card\Palettes;
use App\Support\Card\SceneComposer;
use Database\Factories\SiteTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One invitation-card design: a palette of ten colour roles, four type faces and
 * three composed canvases (kulit, jemputan, butiran). The layers are drawn from
 * App\Support\Card\Catalog by the seeder, never edited by hand.
 */
#[Fillable([
    'slug', 'name', 'style', 'category', 'description', 'is_premium',
    'palette', 'fonts', 'scenes', 'photo_slots', 'experience', 'sort_order', 'is_active',
])]
class SiteTemplate extends Model
{
    /** @use HasFactory<SiteTemplateFactory> */
    use HasFactory;

    use HasTranslatedText;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name' => Translatable::class,
            'description' => Translatable::class,
            'palette' => 'array',
            'fonts' => 'array',
            'scenes' => 'array',
            'photo_slots' => 'array',
            'is_premium' => 'boolean',
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
        // sort_order is what actually orders these; the name is JSON text now.
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * The three designed canvases, each {key, name, width, height, layers}.
     *
     * @return array<int, array<string, mixed>>
     */
    public function canvases(): array
    {
        return $this->scenes ?? [];
    }

    /**
     * The design's own ten colours, with the couple's overrides applied if given.
     *
     * @param  array<string, mixed>|null  $override
     * @return array<string, string>
     */
    public function palette(?array $override = null): array
    {
        return Palettes::resolve($this->palette ?? [], $override);
    }

    /**
     * The four type faces, by role, with the couple's overrides applied if given.
     *
     * @param  array<string, mixed>|null  $override
     * @return array<string, string>
     */
    public function fonts(?array $override = null): array
    {
        // The column is keyed by role. MySQL normalises JSON object keys into
        // alphabetical order, so reading it positionally would hand the script face
        // to the small-caps labels and print the names in Montserrat.
        $roles = Fonts::byRole($this->fonts ?? []);

        foreach (Fonts::ROLES as $role) {
            if (Fonts::isValid($override[$role] ?? null)) {
                $roles[$role] = $override[$role];
            }
        }

        return $roles;
    }

    /**
     * The design's colours and faces as one inline style, for the few places Blade
     * paints before the renderer has mounted (the opening screen, a fallback).
     *
     * @param  array<string, mixed>|null  $paletteOverride
     * @param  array<string, mixed>|null  $fontsOverride
     */
    public function cssVariables(?array $paletteOverride = null, ?array $fontsOverride = null): string
    {
        $vars = [
            ...Palettes::cssVariables($this->palette($paletteOverride)),
            ...Fonts::cssVariables($this->fonts($fontsOverride)),
        ];

        return collect($vars)->map(fn (string $value, string $name): string => $name.':'.$value)->implode(';');
    }

    /**
     * Content keys of the photos this design asks for, e.g. couple_image.
     *
     * @return array<int, string>
     */
    public function photoSlots(): array
    {
        return $this->photo_slots ?? [];
    }

    /**
     * Whether the cover opens with the Bismillah, as most Malay invitations do.
     * It is a layer in the artwork, so the answer is in the layers.
     */
    public function showsBismillah(): bool
    {
        foreach ($this->canvases() as $scene) {
            foreach ($scene['layers'] ?? [] as $layer) {
                if (($layer['name'] ?? '') === 'Bismillah') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Whether the cover is dark, so the link-preview image knows which way to paint.
     */
    public function isDark(): bool
    {
        return Palettes::isDark($this->palette()['bg']);
    }

    /**
     * Compose the design from its catalogue definition. Used by the seeder.
     *
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    public static function attributesFromDefinition(array $definition): array
    {
        $scenes = (new SceneComposer($definition))->scenes();

        return [
            'name' => $definition['name'],
            'style' => $definition['style'],
            'category' => $definition['category'],
            'description' => $definition['desc'],
            'is_premium' => $definition['premium'],
            'palette' => $definition['pal'],
            'fonts' => Fonts::roles($definition['fonts']),
            'scenes' => $scenes,
            'photo_slots' => SceneComposer::photoSlots($scenes),
            'experience' => $definition['experience'] ?? 'scroll',
            'sort_order' => $definition['n'],
            'is_active' => true,
        ];
    }
}
