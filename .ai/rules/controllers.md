---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## Every image upload goes through StoreOptimizedImage
Never call ->store() on an uploaded image. Use App\Actions\StoreOptimizedImage::handle($file, $dir) (lossless: true for QR codes) and ->delete($path) so the "-thumb" copy is removed too. Validate with ImageSettings::uploadRules() injected into the form request's rules(). Listings/grids show StoreOptimizedImage::thumbnailUrl($path), which falls back to the full image. Sizes/quality/format are admin settings (Admin → Tetapan, settings table); old files are converted by `php artisan neekah:optimize-images`.
