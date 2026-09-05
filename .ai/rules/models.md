---
paths:
  - app/Models/WeddingGuest.php
  - app/Models/Vendor.php
---

# Models

## Guest tokens must fail silently on the public card
A guest's personal link is `?u=TOKEN` on the card subdomain. PublicSiteController resolves it scoped to the site's own wedding_id, and a wrong, stale or guessed token renders the ordinary anonymous card: no 404, no error banner. Any distinguishable response is an enumeration oracle that leaks the guest list one name at a time. Never return more than one guest row from anything served on a card subdomain, and never expose the phone.

RSVPs attach to a guest by token, or by phone when exactly one guest of that wedding carries it (recorded in matched_by so the couple sees it qualified). Never match on name: Malaysian lists are full of "Aina" and "Abang Mie", and a wrong bind corrupts the caterer headcount silently.

## response_rate is measured, never set by hand
vendors.response_rate is derived in Vendor::calculateResponseRate() from enquiries answered over enquiries older than 24 hours, and refreshed by RecalculateVendorStats. It is nullable and returns null below MIN_ENQUIRIES_FOR_RESPONSE_RATE, because with two or three enquiries the figure is noise. Display it through responseRateLabel(), which reads "Belum diukur" when null. Do not add a form field that sets it: one existed in the admin tier form and was removed, and any value typed in is overwritten on the next recalculation anyway.
