---
paths:
  - app/Models/WeddingGuest.php
  - app/Models/Vendor.php
  - app/Models/Post.php
  - app/Models/Review.php
  - app/Models/Setting.php
---

# Models

## Guest tokens must fail silently on the public card
A guest's personal link is `?u=TOKEN` on the card subdomain. PublicSiteController resolves it scoped to the site's own wedding_id, and a wrong, stale or guessed token renders the ordinary anonymous card: no 404, no error banner. Any distinguishable response is an enumeration oracle that leaks the guest list one name at a time. Never return more than one guest row from anything served on a card subdomain, and never expose the phone.

RSVPs attach to a guest by token, or by phone when exactly one guest of that wedding carries it (recorded in matched_by so the couple sees it qualified). Never match on name: Malaysian lists are full of "Aina" and "Abang Mie", and a wrong bind corrupts the caterer headcount silently.

## response_rate is measured, never set by hand
vendors.response_rate is derived in Vendor::calculateResponseRate() from enquiries answered over enquiries older than 24 hours, and refreshed by RecalculateVendorStats. It is nullable and returns null below MIN_ENQUIRIES_FOR_RESPONSE_RATE, because with two or three enquiries the figure is noise. Display it through responseRateLabel(), which reads "Belum diukur" when null. Do not add a form field that sets it: one existed in the admin tier form and was removed, and any value typed in is overwritten on the next recalculation anyway.

## Blog body is sanitised on save; publish times are Malaysian time
Post body is printed unescaped ({!! !!}), so every write must pass through App\Support\HtmlSanitizer (StorePostRequest::attributesForPost does). app.timezone is Asia/Kuala_Lumpur (config/app.php, TimezoneTest); the admin's datetime-local input is still parsed explicitly in Post::LOCAL_TIMEZONE (Asia/Kuala_Lumpur) and displayed via localPublishedAt(); never call setTimezone() on the published_at attribute itself.

## Anyone may review; only bookings move the ranking
Reviews are open: a guest or a signed-in user posts one straight on a vendor profile (POST vendors/{vendor}/reviews, throttled 5/hour, Turnstile), with 1-5 stars, a comment and up to Review::MAX_PHOTOS images. It is published immediately — nobody waits for approval to be heard. The old "only after a verified booking" rule is gone.

What keeps it honest is the split, not pre-moderation. Review::verified() is booking_id IS NOT NULL; Vendor::rankingReviews() is verified+published and is the ONLY thing rating_avg, reviews_count, the points and the tier may read. Open reviews are shown with their own average and badged apart. Never fold the two averages into one — a stranger with an email address would then be moving a vendor's ranking, which the kertas kerja makes 30% of the Recommended score.

A vendor CANNOT hide or delete a review; ReviewPolicy gives them reply() and report() only. Admins moderate via ModerateReview: hide (reversible, keeps the row, the reason and who did it) and delete (permanent, takes the photos off disk). Both re-award or revoke PositiveReview points and recalculate stats, but only for booking-backed reviews.

Admins may also enter a review on someone's behalf (migrating one from Google etc.): stored as open, stamped with added_by, and created_at may be backdated.

The five aspect scores (quality, service, …) are nullable now: the open form asks for a star and a sentence. Photos go through StoreOptimizedImage, which re-encodes and strips EXIF — that matters more here than anywhere, because the uploader may be a stranger.

## A vendor has many categories and covers many negeri; the primary is always inside both
vendors.category_id and vendors.state stay the one category and the one address shown on the card, the profile heading, the breadcrumb and the SEO. The full lists are the `category_vendor` pivot (Vendor::categories()) and vendors.service_states (JSON, Vendor::serviceStates()).

Vendor::booted() keeps the primary category in the pivot and the home state in service_states on every model save, so search reads one place and trusts it: scope inCategory() and servingState(), never where('category_id') or where('state'), and never whereBelongsTo(category). A query-builder update bypasses the hook — save the model.

A vendor may untick an extra category but never the primary: Vendor\ProfileController pushes category_id back into the sync, and UpdateVendorProfileRequest::prepareForValidation folds category_id and state into the posted lists so MAX_CATEGORIES counts what is really saved. Covered by Vendor/VendorServiceAreaTest and VendorMarketplaceTest.

## Setting::values() reads through Cache::memo(), never the bare store
CACHE_STORE is database in production, so every Cache::get is a round trip to MySQL. Roughly twenty callers ask Setting::values() during one page render, which was twenty of the ~26 queries on every public page. Cache::memo() collapses them to one. Setting::put() must forget through Cache::memo() too, so the copy this request already read is dropped along with the stored one.

PublicPageCostTest guards it, and it switches to the database store on purpose: under the array store the repetition is free and the regression is invisible.
