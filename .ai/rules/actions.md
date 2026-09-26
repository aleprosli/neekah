---
paths:
  - app/Actions/SeedWeddingChecklist.php
  - app/Actions/StoreOptimizedImage.php
  - app/Actions/ImportVendorIcal.php
  - app/Actions/ActivateCameraAlbum.php
  - app/Actions/PurgeCameraAlbum.php
---

# Actions

## The master checklist lives in the database and only ever adds to a wedding
Admin -> Checklist (checklist_sections + checklist_items, seeded by ChecklistSeeder from "Checklist Melangkah ke Alam Perkahwinan": 8 fasa, ~99 tugasan) is the source. SeedWeddingChecklist::handle() runs on wedding creation AND on every visit to /checklist, so an item admin adds reaches existing couples by itself.

It is additive only. A wedding_task is created when the wedding has no task with that checklist_item_id; an unlinked task with the same title is adopted (checklist_item_id/section filled in) instead of duplicated, which is what keeps a couple seeded from the old template from getting "Tempah katering" twice. Nothing is ever updated or deleted from a couple's list — deleting a master item only nulls their checklist_item_id, because a tick is their record of work they did. Never make this destructive.

months_before null = no deadline (everything after the akad), 0 = the event day. A due date in the past is clamped to today. Covered by WeddingChecklistTest and Admin/ChecklistManagementTest.

## Check the return of Storage::put — the disk does not throw
config/filesystems.php sets 'throw' => false on every disk, so a write the adapter cannot make comes back as `false` rather than raising. StoreOptimizedImage used to ignore that and return the generated path anyway; the caller then saved it, leaving a wedding card and a vendor profile pointing at files that were never written, with the upload reporting success. It now checks both puts, deletes whichever half landed, and throws.

Deployment note that caused it: PHP-FPM runs as `ubuntu` (group www-data), and storage/ is shared with www-data through POSIX ACLs. `chown`ing storage to www-data moves ubuntu from owner — whose `user::rwx` bypasses the ACL mask — to a named ACL entry, which the `mask::r-x` on the subdirectories then caps at read-only. Every upload silently stopped working. Leave storage owned by ubuntu and grant www-data through setfacl.

## Never ask the disk whether a thumbnail exists
thumbnailUrl() derives the "-thumb" path and returns its URL without touching the disk. storeContents() writes both files together, and neekah:optimize-images backfills anything older, so the file is there.

The exists() check that used to be there was a free stat() locally and an HTTPS round trip per image on an object store. The marketplace listing draws about thirty-five images, so it would have added thirty-five sequential requests to Cloudflare before the first byte of HTML. Do not reintroduce it, and do not add any other per-image disk call to a listing path.

Run neekah:optimize-images before pointing MEDIA_DISK at a bucket. Its COLUMNS list must cover every image column that a model turns into a thumbnail URL, or those images 404.

## A thumbnail is never redrawn in place — it gets a new name
Media is served with "Cache-Control: public, max-age=31536000, immutable" (set on the R2 disk in config/filesystems.php). That is a promise that what lives at a URL never changes, and browsers hold the file for a year on it. So nothing may overwrite a path.

Changing the admin thumbnail width therefore cannot redraw thumbnails where they are: every visitor who already has the old one would keep it until the TTL runs out. `php artisan neekah:optimize-images --thumbnails` goes through StoreOptimizedImage::refreshThumbnail(), which writes the pair under a fresh random name, updates the record and deletes the old pair. New URL, promise intact.

refreshThumbnail copies the image itself byte for byte and only re-encodes the thumbnail. The stored image has already been through the encoder once; running it again would cost a generation of quality for a file nobody asked to change.

Changing the width alone does nothing to existing images - the plain command skips anything that already has a thumbnail. Change the setting, then run with --thumbnails. Note quality is global: lowering it to shrink thumbnails also recompresses full-size portfolio photos.

## Google Calendar import: guarded fetch, KL dates, only its own rows
Owner, 26 Sep 2026: a Pro vendor pastes their Google Calendar secret iCal address (vendor_booking_settings.ical_url, encrypted); ImportVendorIcal runs on connect (address kept only if it imports), on "Segerakkan sekarang", and hourly via neekah:sync-ical → SyncVendorIcal (ShouldBeUnique per vendor, random delay). Fetch goes through IcalUrlGuard: https on 443 only, every resolved IP must be public, and the connection is pinned to the checked IP (CURLOPT_RESOLVE), no redirects, 2 MB cap. Parsing uses sabre/vobject expand() (RRULE/EXDATE): all-day DTEND is exclusive; timed events come back in UTC and must be converted to Asia/Kuala_Lumpur before taking the date; TRANSPARENT and CANCELLED are skipped. Rows are source=ical; each import replaces only those (match by date in PHP — SQLite stores date columns with a time, and Collection::except() did not drop Y-m-d keys) and never touches manual rows. With max_per_day > 1 each event takes a slot. A failure keeps old rows, records ical_error, and IcalSyncFailed goes to the vendor once on the 3rd failure in a row. A successful import within 24h counts as a confirmed calendar (VendorBookingSetting::calendarIsFresh). Covered by IcalImportTest.

## Neekah Kenangan (formerly Kamera Majlis) is paid to Neekah; ActivateCameraAlbum is the only activation
Owner, 26 Sep 2026: couples buy Neekah Kenangan (Basic RM29: 500 HD photos, no video; Pro RM99: full quality, video ≤100 MB and ≤3 min, "unlimited" with a fair-use alert; all in CameraSettings, Admin → Tetapan → Wang → Kamera Majlis). It is paid on Neekah's OWN Herepay account (HerepayCameraClient reuses HerepayClient::isConfigured and HerepayCredentials::neekah), callback POST /webhooks/herepay/kamera. ActivateCameraAlbum is the only thing that marks a CameraPurchase paid: idempotent under a lock, creates a NEW album for a new purchase (a wedding may hold several since 26 Sep 2026, see "A wedding buys one Neekah Kenangan album per majlis") or raises the tier of the album an upgrade names (camera_purchases.camera_album_id), 12-char unambiguous token = the QR address /k/{token}, only ever raises the tier, keeps the token, and sets expires_at = (album.event_date ?? wedding.event_date) + retention_days (never sooner than 3 days from now). Moving the wedding's event_date moves expires_at of the albums without a date of their own (Wedding::booted). Upgrade Basic→Pro costs the price difference; a tier already owned is refused. /kamera carries the `wedding` middleware. "Yuran platform: Percuma" stays true: this is a Neekah product, not a commission. Covered by CameraPurchaseTest.

## Kamera Majlis files live under camera/{id}/ and are purged, never the album row
Every album file (photos, thumbnails, video posters, incoming uploads, ZIP exports) is stored under camera/{album id}/ on the public disk, so PurgeCameraAlbum deletes that directory. It removes the media rows, zeroes the counters and sets purged_at, but keeps the album and its purchases as the record. Buying again clears purged_at. It is called by neekah:camera-retention (daily 09:00, when expires_at passes), by the admin takedown, and by DeleteUserAccount. A new camera file kind must go under camera/{id}/ or it outlives the album. Album files are stored with CameraAlbum::CACHE_CONTROL (one day), not the disk's immutable year, and deletes queue PurgeCdnUrls. That job does nothing unless CLOUDFLARE_ZONE_ID and CLOUDFLARE_PURGE_TOKEN are set. Covered by CameraRetentionTest.
