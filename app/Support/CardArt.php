<?php

namespace App\Support;

/**
 * What a kad may be dressed in: the motions, the type pairings, the paper
 * textures and the artwork pieces.
 *
 * This is a closed catalogue on purpose. A couple picks from it; they never
 * hand the card a URL or a font name of their own, because both end up inside
 * a style attribute on a page strangers open.
 */
class CardArt
{
    /**
     * @var array<int, string>
     */
    public const MOTIONS = ['petals', 'sparkle', 'leaves', 'none'];

    /**
     * @var array<string, string>
     */
    public const MOTION_LABELS = [
        'petals' => 'Kelopak gugur',
        'sparkle' => 'Kerlipan',
        'leaves' => 'Daun gugur',
        'none' => 'Tiada',
    ];

    /**
     * The faces the card stylesheet already loads. A couple may pair them
     * differently; they may not introduce one.
     *
     * @var array<string, array{label: string, stack: string, role: string}>
     */
    public const FONTS = [
        'great-vibes' => ['label' => 'Great Vibes', 'stack' => "'Great Vibes', cursive", 'role' => 'script'],
        'pinyon' => ['label' => 'Pinyon Script', 'stack' => "'Pinyon Script', cursive", 'role' => 'script'],
        'cormorant' => ['label' => 'Cormorant Garamond', 'stack' => "'Cormorant Garamond', serif", 'role' => 'body'],
        'playfair' => ['label' => 'Playfair Display', 'stack' => "'Playfair Display', serif", 'role' => 'body'],
        'instrument' => ['label' => 'Instrument Sans', 'stack' => "'Instrument Sans', system-ui, sans-serif", 'role' => 'body'],
    ];

    /**
     * Paper the sheet is printed on. Each one is a tiling image in
     * public/img/card/texture, laid under the grain .nk-paper already draws.
     *
     * @var array<string, string>
     */
    public const TEXTURES = [
        'none' => 'Kertas licin',
        'kertas' => 'Kertas berbutir',
        'linen' => 'Kain linen',
        'marmar' => 'Marmar',
        'jalur-emas' => 'Jalur emas',
    ];

    /**
     * A drawn piece laid on the sheet behind the names — the thing that stops
     * a card reading as a web page. Each is a Blade partial under
     * sites/artwork, drawn in the card's own accent colour.
     *
     * @var array<string, string>
     */
    public const ARTWORK = [
        'none' => 'Tiada',
        'gerbang' => 'Gerbang bunga',
        'kalungan' => 'Kalungan atas',
        'sudut' => 'Sempoa sudut',
        'songket' => 'Jalur songket',
        'bulan-bintang' => 'Bulan & bintang',
    ];

    public static function hasFont(mixed $stack): bool
    {
        return is_string($stack) && in_array($stack, array_column(self::FONTS, 'stack'), true);
    }

    public static function hasTexture(mixed $key): bool
    {
        return is_string($key) && array_key_exists($key, self::TEXTURES);
    }

    public static function hasArtwork(mixed $key): bool
    {
        return is_string($key) && array_key_exists($key, self::ARTWORK);
    }

    /**
     * The texture as the value of --nk-texture: a url() the stylesheet layers
     * under the paper grain, or "none" so the layer simply does not paint.
     */
    public static function textureCss(?string $key): string
    {
        if ($key === null || $key === 'none' || ! self::hasTexture($key)) {
            return 'none';
        }

        return 'url("'.asset('img/card/texture/'.$key.'.svg').'")';
    }

    /**
     * The fonts grouped for a picker.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function fontOptions(string $role): array
    {
        return collect(self::FONTS)
            ->filter(fn (array $font): bool => $font['role'] === $role)
            ->map(fn (array $font): array => ['value' => $font['stack'], 'label' => $font['label']])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, string>  $catalogue
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(array $catalogue): array
    {
        return collect($catalogue)
            ->map(fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }

    /**
     * Every stock with the value --nk-texture takes, so the editor can swap
     * paper in the live preview without asking the server to redraw the card.
     *
     * @return array<string, string>
     */
    public static function textureCssMap(): array
    {
        return collect(self::TEXTURES)
            ->map(fn (string $label, string $key): string => self::textureCss($key))
            ->all();
    }
}
