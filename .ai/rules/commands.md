---
paths:
  - app/Console/Commands/UpdateVendorPopularity.php
---

# Commands

## Profile views drive the "Paling ramai dilihat" sort and Trending, never the score
Owner, 26 Sep 2026: neekah:vendor-popularity runs nightly at 03:00. It writes vendors.views_30d (the sum of vendor_daily_stats.profile_views over 30 days) and trending_at (the top 3 per primary category with at least 20 views). Both are written with toBase() so updated_at does not move. They feed only the 'popular' sort and the Trending badge on the card. Do not add views to Vendor::calculateScore(), because the kertas kerja weights are fixed. VendorController::countView counts a view once per session and once per IP per vendor per day, so reloading cannot inflate it. Covered by VendorPopularityTest.
