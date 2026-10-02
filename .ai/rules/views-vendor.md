---
paths:
  - 'resources/views/vendor/**'
---

# Views Vendor

## Vendor dashboard: rank first in the hero, no traffic numbers, Basic gets things to do
Owner, 2 Oct 2026 ("dashboard vendor tak cantik", "rank paling atas", "buang paparan/rating sebab traffic belum tinggi"): the dashboard is x-vendor-hero (Blade, because the rank badge is a Blade SVG; greeting by server time) with the rank badge first, then an 8/4 grid — VendorDashboardPage (perlu tindakan with the first item large; Pro: business tiles + upcoming; Basic: "Apa anda boleh buat" tools, a Boost explainer using img/boost/kad-dipromosi.jpg in a phone frame, then the Pro upsell listing all 6 Pro features incl. quotations and contracts) beside x-vendor-ranking (next-rank checklist + ladder, id="ranking"). Do NOT show profile views, rating or score numbers in the hero while traffic is low: a "0" turns vendors away. The page is styled like the couple dashboard (burgundy hero, gold-ringed cards, SVG icons — no emoji in action rows). Covered by VendorDashboardTest, VendorOnboardingTest.

## No reach numbers for vendors on the web; Pro story comes from ProStory
Owner, 3 Oct 2026 ("analitik apa apa berkaitan berapa view page kena buang"): the web shows no vendor their profile views, WhatsApp/phone taps or a daily chart — not on the dashboard, not on /vendor/pro (Pro or not), not as a Pro benefit or in onboarding/About copy. VendorDailyStat is still recorded (score/popularity) and the mobile dashboard API still returns reach/daily_views because the released Flutter app requires those fields. The Neekah Pro before/after story (7 benefits: enquiries, quotations, contracts, booking, boost, ranking, badge) with Pro Elite inside it is built only by App\Support\ProStory::props() and rendered by VendorProShowcase on both /vendor/pro and the About page (#pro), so the two never drift. /vendor/pro order: Pro status (Pro only) → story → #harga prices → payment history. Covered by VendorProTest, VendorReachTest, LandingPageTest.
