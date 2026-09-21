---
paths:
  - 'routes/**'
---

# Routes

## Production caches routes and config; rebuild both on deploy
/var/www/neekah runs with bootstrap/cache populated: config.php, routes-v7.php, events.php. A deploy that adds or renames a route MUST run `php artisan route:cache`, and one that touches config/ MUST run `php artisan config:cache`. Nothing reloads them on its own.

This has already broken production once. The open-reviews deploy (19 Sep 2026) ran config:cache and view:clear but not route:cache, so vendors/show.blade.php called route('vendors.reviews.store'), which the stale cache did not have. Every public vendor page answered 500 for about half a minute, ~66 requests, until route:cache ran. The new admin and vendor pages answered 404 in the same window, which looks like a missing feature rather than a broken deploy — so check a NEW route, not just the home page, when verifying.

Full sequence: git checkout -- public/robots.txt (it is rewritten by the app), git pull --ff-only, migrate --force if there are migrations, then config:cache, route:cache, view:clear, neekah:robots, queue:restart. public/build IS committed, so no npm build on the server. Verify with a page that uses the change, not just a 200 on the landing page.

## Routes are registered once per language; never compare route names directly
routes/web.php defines the site as one `$site` closure, registered once per language in App\Support\Locales::codes(). Malay is the default and has no prefix — its URLs are the ones Google already indexed and must never move. English is served under /en with `en.` prefixed to every route name.

App\Routing\LocalisedUrlGenerator (bound in AppServiceProvider) makes route('vendors.index') resolve to the current language, so call sites stay untouched. Use url()->routeIn($code, $name, $params) for the same page in another language — that is what the switcher and hreflang are built from.

NEVER use request()->routeIs() or $route->getName() comparisons: the registered name carries the language prefix, so 'vendors.*' silently stops matching on English pages. Use Locales::routeIs(...) instead, which strips it. It cannot be a Request macro — routeIs() is a real method, and Macroable only catches undefined ones.

The card subdomain group and the sitemaps stay outside the per-language registration: one sitemap lists both languages, and the wedding card is the couple's own artifact, still Malay only.

config/app.php locale is now 'ms' and fallback is 'ms' too, so an English string nobody has written yet prints Malay rather than a bare key. Prod caches config and routes — a deploy touching either MUST run config:cache and route:cache.
