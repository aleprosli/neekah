<?php

namespace App\Actions;

use App\Support\ImageSettings;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Every uploaded image goes through here instead of being stored as sent.
 *
 * It is re-encoded at the admin's size and quality (see ImageSettings), which
 * also strips EXIF data such as the GPS location a phone embeds, and a narrower
 * "-thumb" copy is written next to it for listings and grids. Re-encoding means
 * the stored file is always a real image, never the bytes a visitor uploaded.
 */
class StoreOptimizedImage
{
    public function __construct(private ImageSettings $settings) {}

    /**
     * @param  bool  $lossless  Keep a PNG, for images that must stay pixel-exact such as a payment QR code.
     * @return string The stored path on the public disk.
     */
    public function handle(UploadedFile $file, string $directory, bool $lossless = false): string
    {
        return $this->storeContents((string) file_get_contents($file->getRealPath()), $directory, $lossless);
    }

    /**
     * @return string The stored path on the public disk.
     */
    public function storeContents(string $contents, string $directory, bool $lossless = false): string
    {
        $this->allowMemoryForDecoding();

        $image = $this->decode($contents);
        $extension = $lossless ? 'png' : ($this->settings->format() === 'jpeg' ? 'jpg' : 'webp');
        $path = trim($directory, '/').'/'.Str::random(40).'.'.$extension;
        $disk = Storage::disk('public');

        $largest = $this->settings->maxDimension();
        $disk->put($path, $this->encode($this->resize($image, $largest, $largest), $extension));
        $disk->put(self::thumbnailPath($path), $this->encode($this->resize($image, $this->settings->thumbnailWidth(), PHP_INT_MAX), $extension));

        return $path;
    }

    /**
     * Remove an image and its thumbnail.
     */
    public function delete(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        Storage::disk('public')->delete([$path, self::thumbnailPath($path)]);
    }

    /**
     * "vendors/7/abc.webp" becomes "vendors/7/abc-thumb.webp".
     */
    public static function thumbnailPath(string $path): string
    {
        $info = pathinfo($path);
        $directory = ($info['dirname'] ?? '.') === '.' ? '' : $info['dirname'].'/';

        return $directory.$info['filename'].'-thumb'.(isset($info['extension']) ? '.'.$info['extension'] : '');
    }

    /**
     * The thumbnail's URL, or the full image's while an image uploaded before
     * thumbnails existed has not been through neekah:optimize-images yet.
     */
    public static function thumbnailUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $disk = Storage::disk('public');
        $thumbnail = self::thumbnailPath($path);

        return $disk->url($disk->exists($thumbnail) ? $thumbnail : $path);
    }

    private function decode(string $contents): GdImage
    {
        $image = @imagecreatefromstring($contents);

        if (! $image instanceof GdImage) {
            throw new RuntimeException('The file is not an image GD can read.');
        }

        if (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        return $this->applyExifOrientation($image, $contents);
    }

    /**
     * Phones store a portrait photo sideways and record the turn in EXIF.
     * Re-encoding drops that record, so the turn has to be applied first or
     * the photo comes out lying on its side.
     */
    private function applyExifOrientation(GdImage $image, string $contents): GdImage
    {
        if (! function_exists('exif_read_data') || ! str_starts_with($contents, "\xFF\xD8")) {
            return $image;
        }

        $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($contents));

        return match ((int) ($exif['Orientation'] ?? 1)) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };
    }

    /**
     * Scale down to fit inside the box, never up.
     */
    private function resize(GdImage $image, int $maxWidth, int $maxHeight): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $maxWidth / $width, $maxHeight / $height);

        if ($scale >= 1) {
            return $image;
        }

        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        return $canvas;
    }

    private function encode(GdImage $image, string $extension): string
    {
        ob_start();

        match ($extension) {
            'webp' => $this->encodeWebp($image),
            'jpg' => $this->encodeJpeg($image),
            default => $this->encodePng($image),
        };

        return (string) ob_get_clean();
    }

    private function encodeWebp(GdImage $image): void
    {
        imagesavealpha($image, true);
        imagewebp($image, null, $this->settings->quality());
    }

    /**
     * JPEG has no transparency, so a transparent PNG is laid on white rather
     * than on the black GD would otherwise fill in.
     */
    private function encodeJpeg(GdImage $image): void
    {
        $flat = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($flat, 0, 0, imagecolorallocate($flat, 255, 255, 255));
        imagecopy($flat, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        imageinterlace($flat, true);
        imagejpeg($flat, null, $this->settings->quality());
    }

    private function encodePng(GdImage $image): void
    {
        imagesavealpha($image, true);
        imagepng($image, null, 9);
    }

    /**
     * A decoded 8000px photo needs about 256 MB on its own, above PHP's usual
     * 128 MB. Raise the limit for this request only, and never lower it.
     */
    private function allowMemoryForDecoding(): void
    {
        $current = ini_get('memory_limit');

        if ($current !== '-1' && ini_parse_quantity((string) $current) < 512 * 1024 * 1024) {
            ini_set('memory_limit', '512M');
        }
    }
}
