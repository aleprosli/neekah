<?php

namespace App\Support;

/**
 * How uploaded images are resized and compressed, as set by an admin under
 * Admin → Tetapan. Every value falls back to a default, so a fresh install
 * optimises images before anyone has opened the settings page.
 */
class ImageSettings extends SettingGroup
{
    /** @var array<string, string> */
    public const FORMATS = [
        'webp' => 'WebP (disyorkan, fail paling kecil)',
        'jpeg' => 'JPEG (paling serasi)',
    ];

    /** @var array{max_dimension: int, thumbnail_width: int, quality: int, format: string, max_upload_mb: int} */
    private const DEFAULTS = [
        'max_dimension' => 1920,
        'thumbnail_width' => 640,
        'quality' => 80,
        'format' => 'webp',
        'max_upload_mb' => 10,
    ];

    /**
     * Anything larger would not fit in PHP's memory while GD decodes it, and no
     * phone camera shoots beyond it at its default setting.
     */
    public const MAX_SOURCE_PIXELS_PER_SIDE = 8000;

    /** The longest side of the stored image, in pixels. */
    public function maxDimension(): int
    {
        return (int) $this->value('max_dimension');
    }

    /** The width of the copy served in listings and grids, in pixels. */
    public function thumbnailWidth(): int
    {
        return (int) $this->value('thumbnail_width');
    }

    /** Encoder quality from 1 to 100. */
    public function quality(): int
    {
        return (int) $this->value('quality');
    }

    public function format(): string
    {
        $format = (string) $this->value('format');

        return array_key_exists($format, self::FORMATS) ? $format : self::DEFAULTS['format'];
    }

    public function maxUploadMegabytes(): int
    {
        return (int) $this->value('max_upload_mb');
    }

    /**
     * Validation rules for any image upload, sized by the admin's limit.
     *
     * @return array<int, string>
     */
    public function uploadRules(): array
    {
        return [
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:'.($this->maxUploadMegabytes() * 1024),
            'dimensions:max_width='.self::MAX_SOURCE_PIXELS_PER_SIDE.',max_height='.self::MAX_SOURCE_PIXELS_PER_SIDE,
        ];
    }

    /**
     * @return array{max_dimension: int, thumbnail_width: int, quality: int, format: string, max_upload_mb: int}
     */
    public static function defaults(): array
    {
        return self::DEFAULTS;
    }

    protected static function prefix(): string
    {
        return 'images';
    }
}
