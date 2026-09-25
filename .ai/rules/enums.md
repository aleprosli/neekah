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

## Vendor features are opened per plan and per vendor
Owner, 25 Sep 2026: an admin decides which parts of the vendor area each plan (basic/pro) opens under Admin → Ciri vendor (VendorFeatureSettings, keys "<plan>_<feature>", all open by default), and can override any feature for one vendor on that vendor's admin page (vendors.feature_overrides JSON, only the differences stored). Vendor::hasFeature() = override ?? plan; featurePlan() is "pro" while isPro(). Enforcement is on the server: every feature's routes sit in a `vendor.feature:<feature>` group (EnsureVendorHasFeature), GET redirects to the Pro page when Pro would open it (unlocksWithPro) else the dashboard, writes get 403, and a vendor awaiting approval is let through so the setup page keeps working. The sidebar (layouts/vendor) hides closed features, or shows them with a "Pro" badge linking to the Pro page. Overview, Profile, the Pro page and Account are always open and are not VendorFeature cases. A new vendor page = a new case (label, description, icon, route, routePattern, lang in enums.vendor_feature[_desc]) plus its route group and sidebar entry. Covered by VendorFeatureAccessTest.

## OnlineBooking is closed on Basic by default
VendorFeature::openByDefault() keeps every feature open to both plans except OnlineBooking, which defaults to basic=false, pro=true (VendorFeatureSettings::defaults reads it). So online booking is what Neekah Pro sells; an admin can still change it per plan or per vendor. Its page is vendor.booking-settings.* (Vendor\BookingSettingsController), and the sidebar shows it with a Pro badge to Basic vendors.
