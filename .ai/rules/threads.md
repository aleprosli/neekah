---
paths:
  - 'app/Support/Threads/**'
---

# Threads

## Threads auto-post: plan by slot, publish a step a minute, JPEG only
Owner, 25 Oct 2026: Neekah's own Threads account posts 6-7 times a day from Admin → Threads (admin.threads.*). App id/secret live in .env (services.threads); the account's 60-day token is kept encrypted in ThreadsSettings and refreshed by neekah:threads-token (daily 04:00, once under 30 days left; Threads refuses a token <24h old or already expired → reconnect). ThreadsSettings::redirectUri() is always the Malay URL, because it must match the Meta app character for character.
neekah:threads-plan (00:10, and on settings save / "Isi slot kosong") fills each daily slot once via PlanThreadsPosts → ThreadsComposer: listing = approved vendor with a cover, least recently featured, cooldown in settings; blog = newest published post not shared in 90 days; engagement/tip/promo = next ThreadsTemplate in turn (least recently used). An empty type falls back to a listing. threads_posts.slot_at marks the slot as decided — cancelling keeps the row, so never delete a planned post or the planner refills its slot. Posts are written in Malay whatever the admin's locale.
neekah:threads-publish (every minute) walks each post Scheduled → container (Processing) → FINISHED → Published, one new post per run, and marks anything >2h late Missed instead of dumping it. Threads takes JPEG/PNG only, so every image is a JPEG copy under threads/ (StoreOptimizedImage format: 'jpeg'); text limit is 500 UTF-8 BYTES; polls go only on text posts; link_attachment only on text posts. Content must not promise "tempah & bayar di Neekah" (general.md). Covered by Admin/ThreadsAutopostTest.
