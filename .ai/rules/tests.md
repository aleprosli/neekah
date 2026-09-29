---
paths:
  - 'tests/**'
---

# Tests

## Tests must never touch the local MySQL database
The local .env points at MySQL `neekah_prod` with real data. A cached bootstrap/cache/config.php used to make the suite ignore phpunit.xml's sqlite :memory: and RefreshDatabase wiped that database (25 Sep 2026). Two guards now: phpunit.xml points APP_CONFIG_CACHE/APP_ROUTES_CACHE at *.testing.php files that never exist, and Tests\TestCase::setUpTraits() throws before any trait runs unless the connection is sqlite :memory:. Keep both. Never run migrate:fresh, db:wipe, migrate:refresh or seeders against the local DB, and never loosen the guard to make a test pass. A 419 on every POST in tests is the symptom of the config cache leaking in: stop, do not rerun.
