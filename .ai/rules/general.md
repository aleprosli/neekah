---
paths:
  - '**'
---

# General

## Neekah product scope and source document
Neekah is a wedding all-in-one marketplace and event management platform for Malaysia ("Semua Urusan Majlis, Satu Platform"). Three roles: Customer (couple), Vendor, Admin. The full product working paper (kertas kerja, in Malay) lives at docs/NEEKAH-Kertas-Kerja.md; read it before scoping any feature.
Core principle: off-platform communication (WhatsApp, calls, site visits, negotiation) is allowed, but booking and payment for platform-sourced deals must be recorded and settled through the platform. Reviews are only allowed after a verified booking.

## Build Phase 1 MVP first; commission is 8%
Revenue: 8% platform commission per booking (RM3,000 booking -> RM240 commission, vendor receives RM2,760). Booking = deposit + balance; status Pending Payment -> Confirmed after deposit.
Phase 1 MVP: auth, create wedding, marketplace, vendor profile/catalogue/packages/price/availability, search & filter, enquiry, booking, payment, review; admin user management, vendor approval, categories, booking & transaction management, vendor ranking.
Phase 2: wedding timeline, budget management, checklist, vendor comparison, notifications, vendor point system, recommended vendor.
Phase 3: mobile app (REST API), AI planner, guest management, digital invitation, seating, wedding website, analytics.
Default to Phase 1 scope and flag later-phase work unless asked.
