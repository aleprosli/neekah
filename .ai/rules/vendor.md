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
Owner, 25 Sep 2026: while a vendor is VendorStatus::Pending (Vendor::isAwaitingApproval()) the vendor area is one page. DashboardController renders vendor/setup.blade.php (a guide plus one card per onboarding step, each saving in place), layouts/vendor passes an empty nav so layouts/dashboard drops the sidebar, and every other vendor route sits behind the `vendor.approved` middleware, which redirects to the dashboard. Pending vendors cannot record bookings. Only these routes stay open: setup.profile/cover/price (SetupController, one error bag per card), plus packages/portfolio store and destroy, which send a pending vendor back through Controller::vendorReturnUrl() to dashboard#card. A new vendor route goes inside the vendor.approved group unless a setup card needs it. Rejected/suspended vendors keep the old behaviour. Unavailable dates are not an onboarding step any more (owner, 25 Sep 2026: couples WhatsApp vendors and book directly, so most vendors never filled them in); the Kalendar page stays for approved vendors only. Covered by VendorAwaitingApprovalTest.
