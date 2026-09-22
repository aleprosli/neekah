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
        $thumbnail = self::thumbnailPath($path);

        // The public disk is configured not to throw, so a write it cannot make
        // comes back as false. Ignoring that returned a path for a file that was
        // never written, and the caller saved it: a wedding card pointing at a
        // 404, with nothing anywhere saying the upload had failed.
        $stored = $disk->put($path, $this->encode($this->resize($image, $largest, $largest), $extension))
            && $disk->put($thumbnail, $this->encode($this->resize($image, $this->settings->thumbnailWidth(), PHP_INT_MAX), $extension));

        if (! $stored) {
            // Whichever half landed is of no use on its own.
            $disk->delete([$path, $thumbnail]);

            throw new RuntimeException('Could not write the uploaded image to '.$directory.'.');
        }

        return $path;
    }

    /**
     * Give an image a new name and a thumbnail drawn at today's settings,
     * leaving the image itself byte for byte as it was.
     *
     * The rename is the point. Thumbnails are served with
     * "Cache-Control: immutable, max-age=31536000", which is a promise that
     * what lives at a URL never changes, and browsers hold them for a year on
     * the strength of it. Redrawing a thumbnail in place would break that
     * promise: every visitor who already has the old one would keep it until
     * 2027. A new name is a new URL, so the promise stands and the old pair is
     * deleted behind it.
     *
     * The image is copied rather than re-encoded because it has already been
     * through here once. Running it through the encoder again would cost a
     * generation of quality to produce a file nobody asked to change.
     *
     * @return string The new path on the public disk.
     */
    public function refreshThumbnail(string $path): string
    {
        $this->allowMemoryForDecoding();

        $disk = Storage::disk('public');
        $contents = $disk->get($path);

        if ($contents === null) {
            throw new RuntimeException('There is no image at '.$path.'.');
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'webp';
        $directory = trim((string) pathinfo($path, PATHINFO_DIRNAME), '/');
        $newPath = $directory.'/'.Str::random(40).'.'.$extension;
        $thumbnail = self::thumbnailPath($newPath);

        // As in storeContents: the disk is configured not to throw, so a write
        // it cannot make comes back false, and half a pair is of no use.
        $stored = $disk->put($newPath, $contents)
            && $disk->put($thumbnail, $this->encode(
                $this->resize($this->decode($contents), $this->settings->thumbnailWidth(), PHP_INT_MAX),
                $extension,
            ));

        if (! $stored) {
            $disk->delete([$newPath, $thumbnail]);

            throw new RuntimeException('Could not write the redrawn image to '.$directory.'.');
        }

        $disk->delete([$path, self::thumbnailPath($path)]);

        return $newPath;
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
     * The thumbnail's URL.
     *
     * This asks the disk nothing. storeContents writes the thumbnail beside
     * every image it stores, and neekah:optimize-images backfills the ones
     * uploaded before thumbnails existed, so the file is there.
     *
     * The check that used to be here cost a stat() on a local disk and was
     * invisible. On an object store it is an HTTPS round trip per image, and
     * the marketplace listing alone draws thirty-five of them before it can
     * send a single byte of HTML. Run the backfill before pointing the disk at
     * a bucket; anything it reports as failed is an image that would 404 here.
     */
    public static function thumbnailUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return Storage::disk('public')->url(self::thumbnailPath($path));
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
