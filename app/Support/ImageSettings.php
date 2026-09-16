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
     * What this server will actually accept, in megabytes: the smaller of
     * upload_max_filesize and post_max_size. A request larger than post_max_size
     * never reaches the application intact, so the admin's own limit cannot
     * raise this one.
     */
    public function serverUploadMegabytes(): int
    {
        $limits = array_filter([
            self::megabytes(ini_get('upload_max_filesize')),
            self::megabytes(ini_get('post_max_size')),
        ]);

        return $limits === [] ? PHP_INT_MAX : (int) min($limits);
    }

    /**
     * The limit uploads are really validated against, and the one to show a
     * vendor, so the number in the page is never a promise the server breaks.
     */
    public function effectiveUploadMegabytes(): int
    {
        return min($this->maxUploadMegabytes(), $this->serverUploadMegabytes());
    }

    /** True when php.ini, not the admin, is the binding limit. */
    public function isLimitedByServer(): bool
    {
        return $this->serverUploadMegabytes() < $this->maxUploadMegabytes();
    }

    /**
     * The formats a vendor may upload, as an "JPG, PNG atau WebP" style list.
     */
    public function acceptedFormatsLabel(): string
    {
        return 'JPG, PNG atau WebP';
    }

    /**
     * "1M", "512K", "2G" or a plain byte count as whole megabytes; 0 when the
     * directive is empty or unlimited.
     */
    private static function megabytes(string|false $directive): int
    {
        $value = trim((string) $directive);

        if ($value === '' || $value === '-1' || $value === '0') {
            return 0;
        }

        $bytes = (int) $value * match (strtoupper(substr($value, -1))) {
            'G' => 1024 ** 3,
            'M' => 1024 ** 2,
            'K' => 1024,
            default => 1,
        };

        return (int) floor($bytes / (1024 ** 2));
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
            'max:'.($this->effectiveUploadMegabytes() * 1024),
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
