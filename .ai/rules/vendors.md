---
paths:
  - 'resources/views/vendors/**'
---

# Vendors

## A vendor's phone and WhatsApp are never in public markup
On vendors/show.blade.php the WhatsApp button and the phone number sit inside @auth. A guest gets a "Log masuk untuk WhatsApp vendor" link instead — the number must not be rendered and hidden with CSS, or it is one inspect-element away from being scraped. Vendor::whatsappUrl() is what builds the wa.me link. Do not add the number to the vendor card, the compare page, the JSON-LD (no "telephone" property) or any API response; VendorMarketplaceTest asserts a guest page contains neither "wa.me" nor the number.

## Headline review count is display-only; share and social links must not leak the number
vendors/show prints $publishedReviewsCount (reviews_count + published open reviews) so a profile with open or vendor-added reviews does not read "0 review". The star beside it and vendors.reviews_count stay booking-backed only — never widen the column, the ranking reads it.

The share row (x-vendors.share) uses api.whatsapp.com/send, not wa.me, because VendorMarketplaceTest asserts a guest page contains no "wa.me". Instagram/TikTok have no web share URL; they go through the navigator.share button or "Salin pautan".

Vendor social links live in vendors.social_links (JSON) and pass through App\Support\SocialLinks: each platform only accepts its own hosts, and the free "website" field refuses wa.me/whatsapp/t.me so it cannot carry the vendor's number past the login wall. Add a platform there, not in the view.

## A public profile offers WhatsApp and an enquiry, not a booking
Owner, 20 Sep 2026: booking through Neekah is switched off until it is automated. config('neekah.bookings_enabled') (default false, NEEKAH_BOOKINGS_ENABLED) governs it: vendors/show renders the contact card (WhatsApp for signed-in visitors, login prompt otherwise) and an open enquiry form, and Customer\BookingController::store answers 404. Switching it on brings the booking card back — the markup and the flow are still there, and vendors keep recording their own bookings.

The completed-bookings stat ("0 majlis selesai") is gone from the summary line and the highlights, because it reads as a dead marketplace while nothing can be booked. The anchor is #hubungi now, not #tempah; compare.blade.php links to it. Covered by VendorMarketplaceTest, which asserts both states of the flag.
