---
paths:
  - '**'
---

# General

## Neekah product scope and source document
Neekah is a wedding all-in-one marketplace and event management platform for Malaysia ("Semua Urusan Majlis, Satu Platform"). Three roles: Customer (couple), Vendor, Admin. The full product working paper (kertas kerja, in Malay) lives at docs/NEEKAH-Kertas-Kerja.md; read it before scoping any feature.
Core principle (kertas kerja): off-platform communication allowed, booking and payment recorded through the platform. For now (owner, 18 Sep 2026) Neekah is positioned as a NETWORK instead: couples find vendors, contact them directly (WhatsApp, shown to signed-in users) and deal and pay them directly; Neekah takes no payment and no commission and helps couples plan (checklist, bajet, timeline, tetamu, kad digital). When a search comes up empty, the team passes the request on to vendors via Neekah's WhatsApp (ContactSettings). Since 25 Sep 2026 the one exception is online booking, a Neekah Pro feature: on a Pro vendor page where VendorAvailability::acceptsOnlineBookings() is true, a couple books a date and pays a deposit straight into the vendor's own account. Copy there may say so, always as "deposit terus ke akaun vendor, Neekah tidak memegang wang anda dan tidak mengenakan komisen". Everywhere else, and site-wide, public copy must still not promise "tempah & bayar di Neekah", payments verified on the platform, or points for platform bookings. LandingPageTest guards the About page. Reviews are open to anyone, signed in or not — see the Review rule in models.md; only the booking-backed ones feed the rating and the ranking.

## Build Phase 1 MVP first; commission is switched off for now (free for everyone)
The kertas kerja's revenue model is an 8% platform commission per booking (RM3,000 -> RM240, vendor receives RM2,760). As of 18 Sep 2026 the owner has closed it: Neekah is free for couples and vendors, so Booking::COMMISSION_RATE is 0. The 8% is switched off, not removed — each booking stamps commission_rate/commission_amount at creation, so re-enabling it changes only bookings made afterwards, and older bookings keep showing what they were made under. Vendor and couple screens show a commission line only when Booking::hasCommission(); never advertise a commission on public pages while it is off (the About page says "Yuran platform: Percuma").
Phase 1 MVP: auth, create wedding, marketplace, vendor profile/catalogue/packages/price/availability, search & filter, enquiry, booking, payment, review; admin user management, vendor approval, categories, booking & transaction management, vendor ranking.
Phase 2: wedding timeline, budget management, checklist, vendor comparison, notifications, vendor point system, recommended vendor.
Phase 3: mobile app (REST API), AI planner, guest management, digital invitation, seating, wedding website, analytics.
Default to Phase 1 scope and flag later-phase work unless asked.

## Security headers live in nginx, not the app
The six headers securityheaders.com grades are set on the prod server in /etc/nginx/snippets/security-headers.conf, included by sites-available/neekah. They are not in this repo, so a server rebuild must restore them and no middleware sets them.

nginx drops inherited add_header as soon as a block sets one, so the snippet is included again inside the static-asset location (which uses `expires`). Add the include to any new location or server block that sets a header of its own.

CSP allows https://challenges.cloudflare.com (Turnstile) and uses script-src 'unsafe-inline', because resources/views/components/seo/tags.blade.php emits a per-page <script type="application/ld+json"> that no static hash can cover. Swapping to a real nonce means Vite::useCspNonce() in middleware and a nonce on that tag — and updating SeoTest/BlogTest, which find the tag by exact string.

## CSP media-src must cover the media CDN, not just self
The nginx CSP snippet (/etc/nginx/snippets/security-headers.conf, not in this repo) carries "media-src 'self' https://cdn.neekah.my". The bucket host is there because card background music moved to R2 with the rest of the media: CardView.vue renders <audio :src="music.url"> and that url is now Storage::disk('public')->url(), i.e. the CDN. With media-src 'self' alone the browser blocks it, and the symptom looks like broken audio rather than a CSP refusal. Nothing had a music track when the disk was switched, so it was latent - restore this directive on any server rebuild.

Cloudflare Web Analytics is deliberately NOT allowed in script-src. Its beacon is injected at the edge when the feature is on, is blocked by the CSP, and the site already has Google Analytics through GTM - two analytics stacks for nothing. Turn the feature off in the Cloudflare dashboard rather than widening script-src to static.cloudflareinsights.com. Edge injection only happens for browser user-agents, so a plain curl will not show it.
