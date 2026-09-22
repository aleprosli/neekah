---
paths:
  - app/Actions/SeedWeddingChecklist.php
  - app/Actions/StoreOptimizedImage.php
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
