---
paths:
  - app/Http/Controllers/Vendor/ReviewController.php
  - 'app/Http/Controllers/Vendor/**'
---

# Vendor

## A vendor may add reviews, but the page says they did
Vendors can enter reviews they already received elsewhere (Google, Instagram, WhatsApp) at /vendor/reviews. The vendor comes from the signed-in account, never the request, so it can only land on their own profile.

The label is not optional. Review::sourceLabel() prints "Ditambah oleh vendor" for added_by == vendor->user_id and "Ditambah oleh Neekah" for an admin, beside "✓ Tempahan disahkan" and "Review terbuka". Without it Neekah publishes a business's own words as a customer's, which is a misleading representation under the Trade Descriptions Act 2011 and lands on the platform, not just the vendor. The owner was asked and chose the labelled version (19 Sep 2026); do not remove the badge to tidy the design.

These are open reviews, so they cannot move rating_avg, points or tier — a test asserts three five-star vendor-added reviews change none of them. A vendor may delete only rows where added_by is their own id (ReviewPolicy::deleteOwnAddition); everything a customer wrote stays, and admins moderate all of it as before.

isVendorAdded() reads $this->vendor, so any list rendering sourceLabel() must have that relation set — VendorController::show uses setRelation on the vendor already in hand rather than loading it per row.

## A pending vendor only has the setup page on the dashboard
Owner, 25 Sep 2026: while a vendor is VendorStatus::Pending (Vendor::isAwaitingApproval()) the vendor area is one page. DashboardController renders vendor/setup.blade.php (a guide plus one card per onboarding step, each saving in place), layouts/vendor passes an empty nav so layouts/dashboard drops the sidebar, and every other vendor route sits behind the `vendor.approved` middleware, which redirects to the dashboard. Pending vendors cannot record bookings. Only these routes stay open: setup.profile/cover (SetupController, one error bag per card), plus packages/portfolio store and destroy, which send a pending vendor back through Controller::vendorReturnUrl() to the dashboard with ?langkah=<step> so that step stays open. A new vendor route goes inside the vendor.approved group unless a setup card needs it. Rejected/suspended vendors keep the old behaviour. Unavailable dates and the starting price are not onboarding steps any more (the price comes from the cheapest active package via PackageController::syncPriceFrom, so hasCompleteProfile still passes once a package exists) (owner, 25 Sep 2026: couples WhatsApp vendors and book directly, so most vendors never filled them in); the Kalendar page stays for approved vendors only. Covered by VendorAwaitingApprovalTest.

The setup page shows one step at a time (owner, 25 Sep 2026: a page of every form stacked was too much scrolling). Steps are sr-only radios inside .group/setup and each tab/panel shows through group-has-[#langkah-<key>:checked] — no JavaScript. Those classes are written out per key in the view because Tailwind cannot see a class built from a variable; a new step needs its own entries in $tabClass and $panelClass. The open step is: the one with errors, else ?langkah=, else the first not done. Desktop: guide column left, step right; phone: step first, guide below. The guide column is not sticky and never scrolls on its own: an inner scroll cut opened sections in half.
