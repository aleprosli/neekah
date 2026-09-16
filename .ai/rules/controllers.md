---
paths:
  - 'app/Http/Controllers/**'
  - app/Http/Controllers/SitemapController.php
---

# Controllers

## Every image upload goes through StoreOptimizedImage
Never call ->store() on an uploaded image. Use App\Actions\StoreOptimizedImage::handle($file, $dir) (lossless: true for QR codes) and ->delete($path) so the "-thumb" copy is removed too. Validate with ImageSettings::uploadRules() injected into the form request's rules(). Listings/grids show StoreOptimizedImage::thumbnailUrl($path), which falls back to the full image. Sizes/quality/format are admin settings (Admin → Tetapan, settings table); old files are converted by `php artisan neekah:optimize-images`.

## lastmod means the page changed, not the score
The sitemap is built from the database on every request — there is nothing to regenerate and no cache to clear. It lists approved vendors only, because a pending vendor's page answers 404 and a sitemap full of 404s loses a crawler's trust. A vendor's lastmod is the newest of their profile, packages and portfolio; RecalculateVendorStats deliberately writes with timestamps off, because a score or a counter moving is not a change to the page, and a lastmod that churns on its own is one Google stops believing. SitemapFreshnessTest fails if either guarantee breaks.
