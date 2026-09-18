---
paths:
  - '**'
---

# General

## Neekah product scope and source document
Neekah is a wedding all-in-one marketplace and event management platform for Malaysia ("Semua Urusan Majlis, Satu Platform"). Three roles: Customer (couple), Vendor, Admin. The full product working paper (kertas kerja, in Malay) lives at docs/NEEKAH-Kertas-Kerja.md; read it before scoping any feature.
Core principle (kertas kerja): off-platform communication allowed, booking and payment recorded through the platform. For now (owner, 18 Sep 2026) Neekah is positioned as a NETWORK instead: couples find vendors, contact them directly (WhatsApp, shown to signed-in users) and deal and pay them directly; Neekah takes no payment and no commission and helps couples plan (checklist, bajet, timeline, tetamu, kad digital). When a search comes up empty, the team passes the request on to vendors via Neekah's WhatsApp (ContactSettings). The booking and payment-recording flows still exist in the app as an option, but public copy must not promise "tempah & bayar di Neekah", payments verified on the platform, or points for platform bookings. LandingPageTest guards the About page. Reviews are only allowed after a verified booking.

## Build Phase 1 MVP first; commission is switched off for now (free for everyone)
The kertas kerja's revenue model is an 8% platform commission per booking (RM3,000 -> RM240, vendor receives RM2,760). As of 18 Sep 2026 the owner has closed it: Neekah is free for couples and vendors, so Booking::COMMISSION_RATE is 0. The 8% is switched off, not removed — each booking stamps commission_rate/commission_amount at creation, so re-enabling it changes only bookings made afterwards, and older bookings keep showing what they were made under. Vendor and couple screens show a commission line only when Booking::hasCommission(); never advertise a commission on public pages while it is off (the About page says "Yuran platform: Percuma").
Phase 1 MVP: auth, create wedding, marketplace, vendor profile/catalogue/packages/price/availability, search & filter, enquiry, booking, payment, review; admin user management, vendor approval, categories, booking & transaction management, vendor ranking.
Phase 2: wedding timeline, budget management, checklist, vendor comparison, notifications, vendor point system, recommended vendor.
Phase 3: mobile app (REST API), AI planner, guest management, digital invitation, seating, wedding website, analytics.
Default to Phase 1 scope and flag later-phase work unless asked.
