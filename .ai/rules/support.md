---
paths:
  - app/Support/Seo.php
---

# Support

## All meta tags come from App\Support\Seo
Controllers describe their page by injecting App\Support\Seo and calling title/description/canonical/image/noindex. The only thing that writes meta tags is resources/views/components/seo/tags.blade.php, rendered once in layouts/app. Never add a <title>, description, canonical or og: tag anywhere else, or a page can disagree with itself.

The layout uses fallbackTitle/fallbackDescription, not title/description, so a controller's values always win over the view's :title prop.

Seo is a scoped binding and BeginPageMetadata middleware forgets it per request. Without that, one noindex page marks every later page in the same process noindex too, under Octane and in tests.

robots.txt must stay a real file in public/. The standard Laravel nginx config, Herd included, answers "location = /robots.txt" itself and never reaches PHP, so a route for it silently never runs. Regenerate it with "php artisan neekah:robots" on deploy.

## JSON-LD also comes only from Seo
Structured data is added with Seo::schema([...]) / breadcrumbs([name => url]) / article($published, $modified) from the controller, and printed once by seo/tags.blade.php as one @graph (JSON_HEX_TAG encoded). Never hand-write <script type="application/ld+json"> in a view. Noindex pages emit no JSON-LD. Only claim aggregateRating when real reviews exist.
