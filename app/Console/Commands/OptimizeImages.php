<?php

namespace App\Console\Commands;

use App\Actions\StoreOptimizedImage;
use App\Models\Category;
use App\Models\Package;
use App\Models\Payment;
use App\Models\PortfolioItem;
use App\Models\Post;
use App\Models\ReviewPhoto;
use App\Models\Vendor;
use App\Models\WeddingSite;
use App\Models\WeddingSitePhoto;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Throwable;

class OptimizeImages extends Command
{
    protected $signature = 'neekah:optimize-images';

    protected $description = 'Re-encode images uploaded before optimisation existed, using the admin image settings';

    /**
     * Every column that stores an uploaded image, and whether it must stay
     * lossless (a QR code has to scan, so it is never lossy-compressed).
     *
     * @var array<int, array{0: class-string<Model>, 1: string, 2: bool}>
     */
    private const COLUMNS = [
        [Vendor::class, 'cover_image', false],
        [Vendor::class, 'logo', false],
        [PortfolioItem::class, 'path', false],
        [WeddingSite::class, 'cover_image', false],
        [WeddingSite::class, 'gift_qr_image', true],
        [WeddingSitePhoto::class, 'path', false],
        [Post::class, 'cover_image', false],
        [Category::class, 'image', false],
        [Package::class, 'image', false],
        [ReviewPhoto::class, 'path', false],
        [Payment::class, 'receipt_image', false],
    ];

    /**
     * An image that already has a thumbnail went through StoreOptimizedImage,
     * so running this twice only touches what the first run missed.
     */
    public function handle(StoreOptimizedImage $storeImage): int
    {
        $disk = Storage::disk('public');
        $optimised = 0;
        $skipped = 0;
        $failed = 0;

        foreach (self::COLUMNS as [$model, $column, $lossless]) {
            $model::query()->whereNotNull($column)->lazyById()->each(function (Model $record) use ($disk, $storeImage, $column, $lossless, &$optimised, &$skipped, &$failed): void {
                $path = $record->getAttribute($column);

                if (! $disk->exists($path) || $disk->exists(StoreOptimizedImage::thumbnailPath($path))) {
                    $skipped++;

                    return;
                }

                try {
                    $newPath = $storeImage->storeContents($disk->get($path), dirname($path), $lossless);
                } catch (Throwable $exception) {
                    $failed++;
                    $this->components->warn($path.': '.$exception->getMessage());

                    return;
                }

                // Swapping the file is not an edit, so lastmod in the sitemap stays put.
                $record->timestamps = false;
                $record->forceFill([$column => $newPath])->saveQuietly();
                $disk->delete($path);
                $optimised++;
            });
        }

        $this->components->info("{$optimised} gambar dioptimumkan, {$skipped} dilangkau, {$failed} gagal.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
