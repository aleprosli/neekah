<?php

namespace App\Support;

use App\Models\SiteTemplate;

/**
 * A kad's design, once the template and whatever the couple changed are put
 * together.
 *
 * Every view reads the design through this object, so the template gallery,
 * the editor preview and the published card cannot drift apart. A couple who
 * has changed nothing gets the template exactly as it was designed; a couple
 * who has is still held to the vocabulary — a listed layout, a listed
 * ornament, a colour that parses — because a free-text design ends up as the
 * "terlalu html" card this whole thing exists to avoid.
 */
class CardDesign
{
    /**
     * @param  array<string, mixed>  $design
     */
    private function __construct(private readonly array $design) {}

    /**
     * @param  array<string, mixed>|null  $overrides
     */
    public static function make(SiteTemplate $template, ?array $overrides = null): self
    {
        $design = $template->design ?? [];

        foreach (self::clean($overrides ?? []) as $key => $value) {
            // Palette and type are merged key by key, so changing one colour
            // does not throw away the eleven the designer chose.
            $design[$key] = is_array($value) && is_array($design[$key] ?? null)
                ? [...$design[$key], ...$value]
                : $value;
        }

        return new self($design);
    }

    /**
     * Only the keys a couple is allowed to change, each held to what the card
     * vocabulary can actually render.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function clean(array $overrides): array
    {
        $clean = [];

        if (in_array($overrides['layout'] ?? null, SiteTemplate::LAYOUTS, true)) {
            $clean['layout'] = $overrides['layout'];
        }

        if (in_array($overrides['ornament'] ?? null, SiteTemplate::ORNAMENTS, true)) {
            $clean['ornament'] = $overrides['ornament'];
        }

        if (in_array($overrides['motion'] ?? null, CardArt::MOTIONS, true)) {
            $clean['motion'] = $overrides['motion'];
        }

        if (array_key_exists('texture', $overrides) && CardArt::hasTexture($overrides['texture'])) {
            $clean['texture'] = $overrides['texture'];
        }

        if (array_key_exists('artwork', $overrides) && CardArt::hasArtwork($overrides['artwork'])) {
            $clean['artwork'] = $overrides['artwork'];
        }

        if (array_key_exists('bismillah', $overrides)) {
            $clean['bismillah'] = (bool) $overrides['bismillah'];
        }

        if (is_string($overrides['eyebrow'] ?? null) && trim($overrides['eyebrow']) !== '') {
            $clean['eyebrow'] = mb_substr(trim($overrides['eyebrow']), 0, 40);
        }

        $palette = collect($overrides['palette'] ?? [])
            ->filter(fn (mixed $value, string $key): bool => self::isColour($value) && in_array($key, self::PALETTE_KEYS, true))
            ->all();

        if ($palette !== []) {
            $clean['palette'] = $palette;
        }

        $type = collect($overrides['type'] ?? [])
            ->filter(fn (mixed $value, string $key): bool => in_array($key, ['script', 'body'], true) && CardArt::hasFont($value))
            ->all();

        if ($type !== []) {
            $clean['type'] = $type;
        }

        return $clean;
    }

    /**
     * @var array<int, string>
     */
    public const PALETTE_KEYS = ['page', 'ink', 'name', 'accent', 'body', 'muted', 'panel', 'line', 'buttonBg', 'buttonText'];

    /**
     * A hex colour and nothing else: these land in a style attribute.
     */
    private static function isColour(mixed $value): bool
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1;
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

    public function texture(): ?string
    {
        return $this->design['texture'] ?? null;
    }

    public function artwork(): ?string
    {
        return $this->design['artwork'] ?? null;
    }

    public function showsBismillah(): bool
    {
        return (bool) ($this->design['bismillah'] ?? false);
    }

    public function isDark(): bool
    {
        return (bool) ($this->design['palette']['dark'] ?? false);
    }

    /**
     * @return array<int, string>
     */
    public function petalColors(): array
    {
        return $this->design['petals'] ?? ['#f2c6d4', '#e9b8c6', '#f6dcc2'];
    }

    /**
     * @return array<string, mixed>
     */
    public function palette(): array
    {
        return $this->design['palette'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->design;
    }

    /**
     * The palette, type and texture as CSS custom properties, so one stylesheet
     * renders every card without a class per design.
     */
    public function cssVariables(): string
    {
        $palette = $this->palette();
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
            '--nk-texture' => CardArt::textureCss($this->texture()),
        ];

        return collect($variables)
            ->map(fn (string $value, string $key): string => $key.':'.$value)
            ->implode(';');
    }
}
