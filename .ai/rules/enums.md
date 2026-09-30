---
paths:
  - app/Enums/UserSegment.php
  - app/Enums/VendorFeature.php
---

# Enums

## User segments own their own definition
Admin → Pengguna groups accounts that signed up and then stopped, so the team can call them. App\Enums\UserSegment holds label, description and query together; add a case there rather than writing the condition in a controller, and the chip count and the table rows then cannot disagree.

Deliberate choices, because each has a plausible other reading: CoupleNoCard is asked of weddings the user CREATED and excludes couples with no wedding at all (they are CoupleNoWedding); CoupleNoPartner excludes a wedding whose invitation is still pending, since that couple has already done the asking; VendorSetupPending includes a vendor account with no profile row.

"Setup lengkap" is Vendor::hasCompleteProfile() && hasCompleteCatalogue() — the same two tests the point system awards for, not a new standard. Vendor::setupComplete() is that pair in SQL; VendorSetupScopeTest fails if the two drift. hasCompleteCatalogue() reads active_packages_count/portfolio_items_count when eager-loaded, so list pages must load them to avoid N+1.

## Vendor features are fixed per plan: Basic builds the profile, Pro runs the business
Owner, 26 Sep 2026: the admin "Ciri vendor" page and per-vendor overrides are gone (feature_overrides dropped). VendorFeature::requiresPro() decides: Basic has Packages, Portfolio, Reviews and Boost; Pro adds Calendar (one page with online booking, vendor.availability.index → CalendarController, tabs kalendar/tempahan/deposit/google), Bookings, Enquiries, Points and OnlineBooking. Vendor::hasFeature() = !requiresPro() || isPro(). Enforcement stays on the server: Pro routes sit in `vendor.feature:<feature>` groups (EnsureVendorHasFeature): a Basic GET goes to the Pro page, writes get 403, a vendor awaiting approval is let through for the setup page. Enquiries are the exception: couples can still write to a Basic vendor, vendor.enquiries.index shows Basic only how many are waiting (vendor.enquiries.locked), and EnquiryReceived hides the message from Basic. The vendor sidebar lists Basic first, then a folding "Neekah Pro" group whose items are locked (lock icon) on Basic; groups with a `key` fold (<details data-nav-group>, remembered by resources/js/nav-groups.js). To give one vendor Pro features, give them Pro (admin manual Pro). A new vendor page = a new case (label, description, icon, route, routePattern, requiresPro, lang enums.vendor_feature[_desc]) plus its route group and sidebar entry. Covered by VendorFeatureAccessTest.
