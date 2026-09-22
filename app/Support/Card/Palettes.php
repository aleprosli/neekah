<?php

namespace App\Support\Card;

/**
 * The ten colour roles every card design is painted with, and the ready-made
 * palettes a couple may swap to.
 *
 * Layers store the role ("role:acc"), never the colour, so a palette swap is ten
 * CSS variables rather than a redraw of the whole card.
 */
class Palettes
{
    /**
     * bg/bg2: cover background · onbg: text on that background · head: the names ·
     * acc/acc2: gold (and its lighter tint) · card: inner-scene surface ·
     * ink: body text on the card · pri: headings on the card · mut: quiet text.
     *
     * @var array<int, string>
     */
    public const ROLES = ['bg', 'bg2', 'onbg', 'head', 'acc', 'acc2', 'card', 'ink', 'pri', 'mut'];

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'bg' => 'Latar kulit',
            'bg2' => 'Latar kulit (bawah)',
            'onbg' => 'Tulisan atas kulit',
            'head' => 'Nama pengantin',
            'acc' => 'Aksen',
            'acc2' => 'Aksen lembut',
            'card' => 'Latar dalam',
            'ink' => 'Tulisan',
            'pri' => 'Tajuk',
            'mut' => 'Tulisan halus',
        ];
    }

    /**
     * Alternative palettes offered in the editor. Each one is the full set of ten
     * roles, so any design can wear any of them.
     *
     * @return array<int, array{key: string, label: string, colors: array<string, string>}>
     */
    public static function presets(): array
    {
        return [
            self::preset('emas-zamrud', 'Emas Zamrud', ['#0F3D32', '#0A2A22', '#F3EBD4', '#F3E6BC', '#C8A951', '#EAD9A0', '#F8F2E7', '#23352E', '#0F3D32', '#9FB7AC']),
            self::preset('emas-marun', 'Emas Marun', ['#5A1526', '#7D263E', '#F4E8D0', '#F6E7C1', '#C9A45C', '#EBD79E', '#F7EEDC', '#4A2A2E', '#7D263E', '#A87F86']),
            self::preset('gading-champagne', 'Gading Champagne', ['#F7F1E5', '#EFE6D3', '#4D4232', '#6E5A3A', '#B99A5B', '#DCC58E', '#FBF7EE', '#4D4232', '#6E5A3A', '#9C8F76']),
            self::preset('emas-hitam', 'Emas Hitam', ['#0A0A0A', '#121212', '#EDE6D6', '#E9CF8B', '#C8A24A', '#E9CF8B', '#0A0A0A', '#E6DCC4', '#E9CF8B', '#8A8A8A']),
            self::preset('biru-diraja', 'Biru Diraja', ['#14286B', '#0C1A4A', '#F1EAD6', '#F3E5B0', '#D0AE5F', '#EED9A2', '#F6F2E8', '#1B2447', '#14286B', '#8E98B8']),
            self::preset('malam-emas', 'Malam Emas', ['#0B1530', '#050B1E', '#E8E2D2', '#F0DCA0', '#C9A45C', '#F4E2A8', '#0B1530', '#E8E2D2', '#E6CB80', '#8E9AB8']),
            self::preset('mawar-lembut', 'Mawar Lembut', ['#FBF3EE', '#F3E2DB', '#5B3F43', '#B0606F', '#C8A56A', '#E6CFA1', '#FEF9F5', '#5B3F43', '#B0606F', '#AE9294']),
            self::preset('hijau-botani', 'Hijau Botani', ['#FFFFFF', '#F5F8F3', '#2B3A31', '#2F5A44', '#6F9078', '#B7CDBB', '#FFFFFF', '#2B3A31', '#2F5A44', '#8FA598']),
            self::preset('putih-suci', 'Putih Suci', ['#FFFFFF', '#FBF8F2', '#4A443A', '#3D3830', '#C9AE6A', '#E6D5A5', '#FFFFFF', '#4A443A', '#3D3830', '#9A927F']),
            self::preset('pasir-terakota', 'Pasir Terakota', ['#EFE4D3', '#E6D6BE', '#4A3B2E', '#A8532F', '#C29A55', '#E3CE9B', '#F6EEE0', '#4A3B2E', '#A8532F', '#A0917C']),
        ];
    }

    /**
     * @param  array<int, string>  $colors  in ROLES order
     * @return array{key: string, label: string, colors: array<string, string>}
     */
    protected static function preset(string $key, string $label, array $colors): array
    {
        return ['key' => $key, 'label' => $label, 'colors' => array_combine(self::ROLES, $colors)];
    }

    /**
     * The design's own palette with the couple's overrides on top. Anything that is
     * not a valid colour is dropped, so a bad value shows the design's own.
     *
     * @param  array<string, mixed>  $template
     * @param  array<string, mixed>|null  $override
     * @return array<string, string>
     */
    public static function resolve(array $template, ?array $override): array
    {
        $palette = [];

        foreach (self::ROLES as $role) {
            $colour = self::isHex($override[$role] ?? null) ? $override[$role] : ($template[$role] ?? null);
            $palette[$role] = self::isHex($colour) ? strtolower($colour) : '#888888';
        }

        return $palette;
    }

    /**
     * Only the roles the couple actually changed, so a design can be re-tuned later
     * without overwriting their choices.
     *
     * @param  array<string, mixed>  $override
     * @return array<string, string>
     */
    public static function sanitize(array $override): array
    {
        return collect($override)
            ->only(self::ROLES)
            ->filter(fn (mixed $colour): bool => self::isHex($colour))
            ->map(fn (string $colour): string => strtolower($colour))
            ->all();
    }

    /**
     * @param  array<string, string>  $palette
     * @return array<string, string>
     */
    public static function cssVariables(array $palette): array
    {
        $vars = [];

        foreach (self::ROLES as $role) {
            if (isset($palette[$role])) {
                $vars['--c-'.$role] = $palette[$role];
            }
        }

        return $vars;
    }

    public static function isHex(mixed $value): bool
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1;
    }

    public static function isRole(mixed $value): bool
    {
        return is_string($value) && str_starts_with($value, 'role:') && in_array(substr($value, 5), self::ROLES, true);
    }

    /**
     * Whether text on this colour has to be light. Used by the link-preview image,
     * which paints with real colours rather than variables.
     */
    public static function isDark(string $hex): bool
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');

        return (0.299 * $r + 0.587 * $g + 0.114 * $b) < 140;
    }
}
