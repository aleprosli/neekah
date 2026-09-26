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

## A public profile offers WhatsApp and an enquiry; Pro vendors add online booking
Owner, 25 Sep 2026: the booking form on vendors/show renders only when VendorController::onlineBooking() returns data, i.e. VendorAvailability::acceptsOnlineBookings() (site switch in Admin → Tetapan → Tempahan online, approved, VendorFeature::OnlineBooking open — Pro by default —, vendor switched it on, active packages, a deposit path, calendar confirmed recently). Every other vendor gets the contact card (WhatsApp for signed-in visitors, login prompt otherwise) and the enquiry form, and StoreBookingRequest::authorize answers 404 before validating. config('neekah.bookings_enabled') is gone. Availability is computed per request and never goes into the cached payload; the date picker (VendorDatePicker.vue) reads /vendors/{vendor}/ketersediaan, which returns statuses only. Server fallback: a native date input plus the next open days.

The completed-bookings stat ("0 majlis selesai") is gone from the summary line and the highlights. The anchor is #hubungi, not #tempah; compare.blade.php links to it. Covered by VendorMarketplaceTest and OnlineBookingTest.

## Vendor contact taps go through counting routes
The WhatsApp button links to vendors.contact.whatsapp (auth-only, counts whatsapp_clicks, then redirects to Vendor::whatsappUrl), so the page still never contains wa.me. The tel: link keeps its href and carries data-track-phone + data-track-token; resources/js/contact-beacon.js sends a beacon to vendors.contact.phone. Taps and profile views by the vendor themselves, admins and bots are not counted.
