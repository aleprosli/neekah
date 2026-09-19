---
paths:
  - app/Enums/UserSegment.php
---

# Enums

## User segments own their own definition
Admin → Pengguna groups accounts that signed up and then stopped, so the team can call them. App\Enums\UserSegment holds label, description and query together; add a case there rather than writing the condition in a controller, and the chip count and the table rows then cannot disagree.

Deliberate choices, because each has a plausible other reading: CoupleNoCard is asked of weddings the user CREATED and excludes couples with no wedding at all (they are CoupleNoWedding); CoupleNoPartner excludes a wedding whose invitation is still pending, since that couple has already done the asking; VendorSetupPending includes a vendor account with no profile row.

"Setup lengkap" is Vendor::hasCompleteProfile() && hasCompleteCatalogue() — the same two tests the point system awards for, not a new standard. Vendor::setupComplete() is that pair in SQL; VendorSetupScopeTest fails if the two drift. hasCompleteCatalogue() reads active_packages_count/portfolio_items_count when eager-loaded, so list pages must load them to avoid N+1.
