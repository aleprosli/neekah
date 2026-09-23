---
paths:
  - 'tests/**'
---

# Tests

## Run the suite with APP_LOCALE=ms; a local .env in English fails unrelated tests
phpunit.xml does not pin APP_LOCALE or APP_NAME, so a local .env with APP_LOCALE=en / APP_NAME=Laravel makes ~10 tests fail on English flash messages, English Translatable titles, "pagi" vs "AM" and the page title. They are not regressions. Run `APP_LOCALE=ms APP_FALLBACK_LOCALE=ms APP_NAME=Neekah php artisan test --compact`, or fix the .env. TimezoneTest is the exception the other way: it expects "AM" and fails under ms ("pagi"), so run it on its own with the plain .env.
