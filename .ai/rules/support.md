---
paths:
  - app/Support/Seo.php
  - 'app/Support/**'
  - app/Support/ImageSettings.php
  - app/Support/ContentVersion.php
  - app/Support/VendorAvailability.php
  - app/Support/Translations.php
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

## Public pages are cached against ContentVersion, and nothing objecty goes in
Every cached public payload carries a ContentVersion string in its key. A write bumps the version, so the stale entry is never asked for again - nothing is deleted and nothing is cleared by hand. Two scopes: global() moves on any Vendor, Category or Setting save (the listings); forVendor($id) moves on that vendor's Package, PortfolioItem or Review save, so one vendor editing a price does not throw away all the others. Hooks live in each model's booted(). TTL is the backstop for what the version cannot see (a ReviewPhoto deleted without its Review being saved, a query-builder update firing no model event).

Trap 1 - order inside bump(). Cache::memo()->forget() clears the UNDERLYING store as well as the memo. Forget first, write second. Writing then forgetting deletes the version just minted and leaves the next reader to invent another one, so the payload is written under a key nobody reads back.

Trap 2 - config/cache.php sets serializable_classes to false, deliberately: no PHP object may be unserialized from the cache, so a leaked APP_KEY cannot become a gadget chain. Cache raw attribute arrays (Model::getAttributes()) and rebuild with Model::hydrate(). Caching a model or an Eloquent collection gives __PHP_Incomplete_Class on read. Do not widen that setting to make a cache work. PublicPageCostTest asserts no cache row contains "O:".

## VendorAvailability is the only place a date is decided
Whether a vendor can be booked on a day, and whether they take online bookings at all, is decided only in App\Support\VendorAvailability (owner, 25 Sep 2026). Capacity = VendorBookingSetting::max_per_day; active bookings (pending/confirmed) take a place each; a vendor_unavailable_dates row closes the day, or takes `slots` places when set. Online-only rules: past/today, min_lead_days, max_advance_months, available_weekdays (ISO 1-7). onlineState() returns the first blocker in fix order (GloballyOff, NotApproved, FeatureOff, SwitchedOff, NoPackages, NoPaymentPath, CalendarStale, Open). Vendor::isAvailableOn, StoreBookingRequest, StoreVendorBookingRequest, CreateBooking (under a vendor row lock), the public ketersediaan JSON and the vendor calendar all ask it; never re-implement a check elsewhere. Booking settings live in vendor_booking_settings, not on vendors, so saving them or confirming the calendar never moves ContentVersion::global(). Herepay keys there are `encrypted` casts: rotating APP_KEY needs APP_PREVIOUS_KEYS. Dates are Asia/Kuala_Lumpur calendar days. Covered by VendorAvailabilityTest, OnlineBookingTest.

## A Vue island on a site/auth/camera page needs its ui group in GROUPS_BY_SHELL
Translations::forClient() ships only some lang/*/ui.php groups to public shells (site, auth, card, camera); signed-in dashboards get everything. A component that calls $t('group.key') on a public page shows the raw key unless that group is listed for the page's shell — the vendor page date picker (date_picker) and the password eye (copy) shipped broken this way. When adding a $t group to a component used on a public page, add it to GROUPS_BY_SHELL and assert it in ClientTranslationsTest.
