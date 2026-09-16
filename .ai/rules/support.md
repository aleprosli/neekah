---
paths:
  - app/Support/Seo.php
  - 'app/Support/**'
  - app/Support/ImageSettings.php
---

# Support

## All meta tags come from App\Support\Seo
Controllers describe their page by injecting App\Support\Seo and calling title/description/canonical/image/noindex. The only thing that writes meta tags is resources/views/components/seo/tags.blade.php, rendered once in layouts/app. Never add a <title>, description, canonical or og: tag anywhere else, or a page can disagree with itself.

The layout uses fallbackTitle/fallbackDescription, not title/description, so a controller's values always win over the view's :title prop.

Seo is a scoped binding and BeginPageMetadata middleware forgets it per request. Without that, one noindex page marks every later page in the same process noindex too, under Octane and in tests.

robots.txt must stay a real file in public/. The standard Laravel nginx config, Herd included, answers "location = /robots.txt" itself and never reaches PHP, so a route for it silently never runs. Regenerate it with "php artisan neekah:robots" on deploy.

## JSON-LD also comes only from Seo
Structured data is added with Seo::schema([...]) / breadcrumbs([name => url]) / article($published, $modified) from the controller, and printed once by seo/tags.blade.php as one @graph (JSON_HEX_TAG encoded). Never hand-write <script type="application/ld+json"> in a view. Noindex pages emit no JSON-LD. Only claim aggregateRating when real reviews exist.

## Admin-editable settings extend SettingGroup
Anything an admin can change under Admin → Tetapan lives in a class extending App\Support\SettingGroup (ContactSettings, SeoSettings, TurnstileSettings, ImageSettings). Each declares defaults() and a prefix(); values are stored one row per "prefix.key" in the settings table and read through Setting::values(), which is cached forever and forgotten on save. Never read a settings row directly, and never add a key without a default — defaults() is what makes a fresh install work and lets a group gain keys without a migration. config/neekah.php and config/services.php hold the defaults these groups fall back to, not the live values.

## Upload limits are capped by php.ini, not by the admin setting
ImageSettings::maxUploadMegabytes() is what the admin saved; uploadRules() and every page hint use effectiveUploadMegabytes(), which is the smaller of that and serverUploadMegabytes() (upload_max_filesize vs post_max_size). A POST above post_max_size is discarded by PHP before any controller runs, so bootstrap/app.php renders PostTooLargeException as a redirect back with a readable error instead of a bare "page expired". Never print a limit from the raw admin value, and show upload rules through <x-form.image-hint />.
