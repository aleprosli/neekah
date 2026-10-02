---
paths:
  - 'tests/**'
---

# Tests

## Run the suite with APP_LOCALE=ms; a local .env in English fails unrelated tests
phpunit.xml does not pin APP_LOCALE or APP_NAME, so a local .env with APP_LOCALE=en / APP_NAME=Laravel makes ~10 tests fail on English flash messages, English Translatable titles, "pagi" vs "AM" and the page title. They are not regressions. Run `APP_LOCALE=ms APP_FALLBACK_LOCALE=ms APP_NAME=Neekah php artisan test --compact`, or fix the .env. TimezoneTest is the exception the other way: it expects "AM" and fails under ms ("pagi"), so run it on its own with the plain .env.

## Tests must never touch the local MySQL database
The local .env points at MySQL `neekah_prod` with real data. A cached bootstrap/cache/config.php used to make the suite ignore phpunit.xml's sqlite :memory: and RefreshDatabase wiped that database (25 Sep 2026). Two guards now: phpunit.xml points APP_CONFIG_CACHE/APP_ROUTES_CACHE at *.testing.php files that never exist, and Tests\TestCase::setUpTraits() throws before any trait runs unless the connection is sqlite :memory:. Keep both. Never run migrate:fresh, db:wipe, migrate:refresh or seeders against the local DB, and never loosen the guard to make a test pass. A 419 on every POST in tests is the symptom of the config cache leaking in: stop, do not rerun.
