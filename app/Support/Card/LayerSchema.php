<?php

namespace App\Support\Card;

use Illuminate\Support\Str;

/**
 * Defaults and clamping for the layers a design is composed of. Layers are stored
 * as JSON on the template row, so every value is whitelisted here once, when the
 * design is composed.
 *
 * Coordinates are canvas pixels (top-left origin) inside the scene's width × height.
 * Colours and fonts are either a literal value or a role token ("role:acc"), which
 * the renderer resolves to a CSS variable.
 */
class LayerSchema
{
    public const TYPES = ['text', 'image', 'shape', 'ornament'];

    public const MAX_LAYERS = 80;

    public const SHAPES = ['rect', 'rounded', 'circle', 'oval', 'arch'];

    public const BLENDS = ['normal', 'multiply', 'screen', 'overlay', 'soft-light'];

    public const SHAPE_KINDS = ['rect', 'circle', 'glow', 'ring', 'fade'];

    /**
     * @return array<string, mixed>
     */
    public static function defaults(string $type): array
    {
        $base = [
            'id' => '', 'type' => $type, 'name' => ucfirst($type),
            'x' => 0, 'y' => 0, 'w' => 400, 'h' => 200, 'rotation' => 0, 'opacity' => 100,
            'visible' => true, 'locked' => false, 'editable' => true,
            'shadow' => null, 'blur' => 0, 'blend' => 'normal',
        ];

        return $base + match ($type) {
            'text' => [
                'text' => 'Your text', 'font' => null, 'size' => 48, 'weight' => 400, 'italic' => false,
                'align' => 'center', 'valign' => 'middle', 'color' => '#3a2a2a', 'spacing' => 0,
                'lineHeight' => 1.2, 'transform' => 'none',
            ],
            'image' => [
                'src' => null, 'bind' => null, 'shape' => 'rect', 'radius' => 24,
                'zoom' => 100, 'fx' => 50, 'fy' => 50, 'fit' => 'cover', 'tile' => 0,
                'border' => null, 'placeholder' => false,
            ],
            'ornament' => ['src' => null, 'color' => '#c9a45c', 'color2' => null, 'angle' => 135, 'tile' => 0],
            'shape' => ['fill' => '#ffffff', 'fill2' => null, 'angle' => 160, 'kind' => 'rect', 'radius' => 0, 'border' => null],
        };
    }

    public static function newId(): string
    {
        return 'l'.Str::lower(Str::random(9));
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    public static function sanitize(array $raw): array
    {
        $type = in_array($raw['type'] ?? null, self::TYPES, true) ? $raw['type'] : 'shape';
        $d = self::defaults($type);

        $layer = [
            'id' => '', 'type' => $type,
            'name' => Str::limit(strip_tags((string) ($raw['name'] ?? $d['name'])), 60, ''),
            'x' => self::num($raw['x'] ?? 0, -3000, 6000), 'y' => self::num($raw['y'] ?? 0, -3000, 8000),
            'w' => self::num($raw['w'] ?? $d['w'], 4, 6000), 'h' => self::num($raw['h'] ?? $d['h'], 4, 8000),
            'rotation' => self::num($raw['rotation'] ?? 0, -360, 360),
            'opacity' => self::num($raw['opacity'] ?? 100, 0, 100),
            'visible' => (bool) ($raw['visible'] ?? true),
            'locked' => (bool) ($raw['locked'] ?? false),
            'editable' => (bool) ($raw['editable'] ?? true),
            'shadow' => self::shadow($raw['shadow'] ?? null),
            'blur' => self::num($raw['blur'] ?? 0, 0, 40),
            'blend' => in_array($raw['blend'] ?? '', self::BLENDS, true) ? $raw['blend'] : 'normal',
        ];

        return $layer + match ($type) {
            'text' => [
                'text' => Str::limit(strip_tags((string) ($raw['text'] ?? '')), 600, ''),
                'font' => self::font($raw['font'] ?? null),
                'size' => self::num($raw['size'] ?? 48, 8, 400),
                'weight' => self::weight($raw['weight'] ?? 400),
                'italic' => (bool) ($raw['italic'] ?? false),
                'align' => in_array($raw['align'] ?? '', ['left', 'center', 'right'], true) ? $raw['align'] : 'center',
                'valign' => in_array($raw['valign'] ?? '', ['top', 'middle', 'bottom'], true) ? $raw['valign'] : 'middle',
                'color' => self::hex($raw['color'] ?? null, '#3a2a2a'),
                'spacing' => self::num($raw['spacing'] ?? 0, -0.1, 1),
                'lineHeight' => self::num($raw['lineHeight'] ?? 1.2, 0.8, 3),
                'transform' => in_array($raw['transform'] ?? '', ['none', 'uppercase', 'capitalize'], true) ? $raw['transform'] : 'none',
            ],
            'image' => [
                'src' => self::asset($raw['src'] ?? null),
                'bind' => is_string($raw['bind'] ?? null) && preg_match('/^[a-z_]+_image$/', $raw['bind']) ? $raw['bind'] : null,
                'shape' => in_array($raw['shape'] ?? '', self::SHAPES, true) ? $raw['shape'] : 'rect',
                'radius' => self::num($raw['radius'] ?? 24, 0, 400),
                'zoom' => self::num($raw['zoom'] ?? 100, 100, 400),
                'fx' => self::num($raw['fx'] ?? 50, 0, 100), 'fy' => self::num($raw['fy'] ?? 50, 0, 100),
                'fit' => ($raw['fit'] ?? '') === 'contain' ? 'contain' : 'cover',
                'tile' => self::num($raw['tile'] ?? 0, 0, 800),
                'border' => self::border($raw['border'] ?? null),
                'placeholder' => (bool) ($raw['placeholder'] ?? false),
            ],
            'ornament' => [
                'src' => self::asset($raw['src'] ?? null),
                'color' => self::hex($raw['color'] ?? null, '#c9a45c'),
                'color2' => self::hex($raw['color2'] ?? null, null),
                'angle' => self::num($raw['angle'] ?? 135, 0, 360),
                'tile' => self::num($raw['tile'] ?? 0, 0, 800),
            ],
            'shape' => [
                'fill' => self::hex($raw['fill'] ?? null, '#ffffff'),
                'fill2' => self::hex($raw['fill2'] ?? null, null),
                'angle' => self::num($raw['angle'] ?? 160, 0, 360),
                'kind' => in_array($raw['kind'] ?? '', self::SHAPE_KINDS, true) ? $raw['kind'] : 'rect',
                'radius' => self::num($raw['radius'] ?? 0, 0, 400),
                'border' => self::border($raw['border'] ?? null),
            ],
        };
    }

    /**
     * Only files that ship in public/img/layers may be referenced by src.
     */
    public static function asset(mixed $src): ?string
    {
        return is_string($src) && preg_match('#^/img/layers/[a-z0-9_\-]+(/[a-z0-9_\-]+)*\.(svg|png|webp|jpg)$#', $src) ? $src : null;
    }

    /**
     * @return array{x: int|float, y: int|float, blur: int|float, color: string, opacity: int|float}|null
     */
    protected static function shadow(mixed $s): ?array
    {
        if (! is_array($s)) {
            return null;
        }

        return [
            'x' => self::num($s['x'] ?? 0, -100, 100), 'y' => self::num($s['y'] ?? 6, -100, 100),
            'blur' => self::num($s['blur'] ?? 16, 0, 120),
            'color' => self::hex($s['color'] ?? null, '#000000'),
            'opacity' => self::num($s['opacity'] ?? 30, 0, 100),
        ];
    }

    /**
     * @return array{width: int|float, color: string}|null
     */
    protected static function border(mixed $b): ?array
    {
        if (! is_array($b) || ($b['width'] ?? 0) <= 0) {
            return null;
        }

        return ['width' => self::num($b['width'], 0, 40), 'color' => self::hex($b['color'] ?? null, '#c9a45c')];
    }

    protected static function weight(mixed $v): int
    {
        return in_array((int) $v, [300, 400, 500, 600, 700], true) ? (int) $v : 400;
    }

    /**
     * A literal colour or a palette role token.
     */
    protected static function hex(mixed $v, ?string $default): ?string
    {
        if (Palettes::isRole($v)) {
            return $v;
        }

        return Palettes::isHex($v) ? strtolower($v) : $default;
    }

    /**
     * A type-face role token, or a family we actually ship.
     */
    protected static function font(mixed $v): ?string
    {
        if (Fonts::isRole($v)) {
            return $v;
        }

        return Fonts::isValid(is_string($v) ? $v : null) ? $v : null;
    }

    public static function num(mixed $v, float $min, float $max): int|float
    {
        $n = is_numeric($v) ? (float) $v : $min;
        $n = max($min, min($max, $n));

        return round($n, 2) == (int) round($n, 2) ? (int) round($n) : round($n, 2);
    }
}
