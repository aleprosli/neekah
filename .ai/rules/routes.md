---
paths:
  - 'routes/**'
---

# Routes

## Production caches routes and config; rebuild both on deploy
/var/www/neekah runs with bootstrap/cache populated: config.php, routes-v7.php, events.php. A deploy that adds or renames a route MUST run `php artisan route:cache`, and one that touches config/ MUST run `php artisan config:cache`. Nothing reloads them on its own.

This has already broken production once. The open-reviews deploy (19 Sep 2026) ran config:cache and view:clear but not route:cache, so vendors/show.blade.php called route('vendors.reviews.store'), which the stale cache did not have. Every public vendor page answered 500 for about half a minute, ~66 requests, until route:cache ran. The new admin and vendor pages answered 404 in the same window, which looks like a missing feature rather than a broken deploy — so check a NEW route, not just the home page, when verifying.

Full sequence: git checkout -- public/robots.txt (it is rewritten by the app), git pull --ff-only, migrate --force if there are migrations, then config:cache, route:cache, view:clear, neekah:robots, queue:restart. public/build IS committed, so no npm build on the server. Verify with a page that uses the change, not just a 200 on the landing page.
