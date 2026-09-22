---
paths:
  - 'app/Http/Controllers/**'
  - app/Http/Controllers/SitemapController.php
  - app/Http/Controllers/SiteTemplatePreviewController.php
---

# Controllers

## Every image upload goes through StoreOptimizedImage
Never call ->store() on an uploaded image. Use App\Actions\StoreOptimizedImage::handle($file, $dir) (lossless: true for QR codes) and ->delete($path) so the "-thumb" copy is removed too. Validate with ImageSettings::uploadRules() injected into the form request's rules(). Listings/grids show StoreOptimizedImage::thumbnailUrl($path), which falls back to the full image. Sizes/quality/format are admin settings (Admin → Tetapan, settings table); old files are converted by `php artisan neekah:optimize-images`.

## lastmod means the page changed, not the score
The sitemap is built from the database on every request — there is nothing to regenerate and no cache to clear. It lists approved vendors only, because a pending vendor's page answers 404 and a sitemap full of 404s loses a crawler's trust. A vendor's lastmod is the newest of their profile, packages and portfolio; RecalculateVendorStats deliberately writes with timestamps off, because a score or a counter moving is not a change to the page, and a lastmod that churns on its own is one Google stops believing. SitemapFreshnessTest fails if either guarantee breaks.

## Flash messages live in lang/*/flash.php, never as literals
`->with('status', ...)` and `->withErrors([...])` messages go through `__('flash.<area>.<key>')`, with names, references and counts passed as parameters rather than concatenated — a concatenated sentence cannot be reordered in another language.

Areas: account, admin, couple, vendor, impersonation, confirm (the confirm group also feeds the `rowAction`/confirm-dialog props in Blade).

These are the one category the render-and-grep sweep in .ai/rules/lang.md cannot reach, because a flash message only exists after a POST. They were all still Malay long after every page was translated. Verify instead by asserting every `__('flash.*')` in the source resolves in both lang files, and that a key carrying `:param` is always called with an array.

## Cache the gallery artwork, never the per-visitor half of the props
Building fifty CardProps::forThumbnail arrays is the most expensive thing the design gallery does. It is cached under "card-thumbnails:<locale>:<count>-<max updated_at>", so an admin editing or hiding a design busts it with nothing to clear, and until end of day, because SampleCard's event date is relative to today.

VueProps::for() is applied AFTER the cache read, one tile at a time. It injects csrf_token() and the session's validation errors, so caching its output would hand every visitor the first visitor's CSRF token. PublicPageCostTest asserts two sessions get different tokens.

Call thumbnails() once per request, not inside the per-tile closure.
