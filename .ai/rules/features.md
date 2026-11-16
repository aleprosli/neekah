---
paths:
  - 'resources/views/features/**'
---

# Features

## Product pages live under /ciri, built on x-site.feature-page; About only links to them
Owner, 16 Nov 2026: Marketplace, Neekah Kenangan, Neekah Pro and Neekah Boost each have their own page (/ciri/marketplace|kenangan|pro|boost, FeaturePageController). They no longer appear as sections on About. App\Support\FeaturePages is the only list of them. It feeds the header "Tentang" dropdown, the About "Terokai Neekah" grid, the footer, the sitemap and the explore row at the foot of each page. A page whose feature is not on sale answers 404 and is left out of all of those lists. A new feature page must use the shared frame (x-site.feature-page, feature-hero, feature-section, feature-cta, feature-explore, site.button) so the pages look the same. Links to Pro Elite go to route('features.pro')#elite. Covered by FeaturePagesTest and LandingPageTest.
