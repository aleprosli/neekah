# Neekah — Features & User Journeys

What each kind of user sees and can do, written from their side of the screen. For where the code lives see `docs/ARCHITECTURE.md`; for the decisions behind it see `.ai/rules/`.

**Positioning in one line:** Neekah is a free *network* — couples find vendors and plan the wedding here, then deal and pay vendors directly. Neekah takes no payment and no commission, and public copy must never say otherwise (`.ai/rules/general.md`).

---

## 1. The core loop

```
 Vendor lists for free ──► Neekah SEO + sharing brings couples in ──► Couple finds vendor
          ▲                                                                   │
          │                                                   WhatsApp / enquiry, direct deal
          │                                                                   ▼
   Reviews, points, tier ◄──── Couple plans on Neekah (checklist, budget, guests, kad digital)
                                                    │
                                     Card on its own subdomain → guests see it
```

---

## 2. First visit — what a couple sees

The homepage (`/`, `vendors.index`) **is the marketplace**, not a marketing page:

- Header + search bar (keyword, category, negeri).
- Category strip with icons (Pelamin, Katering, Jurugambar, …) — one tap filters.
- Filters: negeri, price range, minimum rating, vendor tier. Sort: recommended (ranking score), rating, price ↑/↓, most reviews.
- Vendor cards, paginated.
- **Empty result → "WhatsApp Neekah"**, a prefilled message saying what they were looking for, so the team can pass the request on to vendors (`ContactSettings`).

Other public pages: `/about` (the pitch, "Yuran platform: Percuma"), `/blog`, `/kad-jemputan` (gallery of the 50 card designs with live previews), `/compare` (vendors side by side). Everything exists in Malay at `/` and English at `/en`.

---

## 3. Vendor

### Register & listing — free
- `/vendor/register` → account + business profile in one go → status **Pending** → admin approves → listed.
- A couple account with no activity can convert itself into a vendor at `/vendor/tukar-akaun`.
- Onboarding checklist on the vendor dashboard guides them to a complete profile.

### What a vendor manages (`/vendor/*`)
| Page | What |
| --- | --- |
| Profile | Name, tagline, description, **several categories**, city/negeri + **negeri served**, phone, WhatsApp, social links, price-from + unit, logo, cover |
| Packages | Catalogue of packages with prices |
| Portfolio | Photo gallery, drag to reorder (auto-optimised uploads) |
| Availability | Dates they are not available |
| Enquiries | Messages from couples, reply to each |
| Reviews | See, reply, report; add reviews from past clients themselves |
| Points | Ledger of points earned (profile complete +50, catalogue +30, …) |
| Bookings | Record bookings they made with couples; verify payments the couple recorded |

### What Neekah does for the vendor's visibility (SEO & traffic)
- **Profile page is indexable** at `/vendors/{slug}` with its own title, description, canonical and OG image (first portfolio photo).
- **Structured data**: `LocalBusiness` JSON-LD with `aggregateRating` once real reviews exist, plus breadcrumbs → star snippets in Google.
- **Category / negeri landing pages**: `/?category=pelamin` gets its own canonical, title and breadcrumb, so "vendor pelamin" searches can land on a listing.
- **Sitemaps**: `/sitemap-vendors.xml` with an honest `lastmod` (changes when the profile/catalogue/reviews change), both languages with hreflang.
- **Two languages** = two indexable pages per vendor.
- **Blog** (`BlogPosting` schema) to pull search traffic in and link to vendors.
- **Share buttons** on the profile (WhatsApp, Facebook, Telegram, copy link, native share sheet) so vendors push their own followers to Neekah.
- **Ranking**: tier (New → Verified → Trusted → Top → Recommended) and a score from rating, completion, response rate and profile quality decides the default sort. Rating alone never drives it.
- **Related vendors** on each profile keep couples browsing.

---

## 4. How a couple connects with a vendor

From a vendor profile:

| Action | Who can | What happens |
| --- | --- | --- |
| **WhatsApp vendor** | Signed-in users only (guests see "Log masuk untuk WhatsApp") | Opens WhatsApp with a prefilled "saya jumpa anda di Neekah…" message. The sign-in wall is deliberate: it turns browsers into registered couples |
| **Hantar enquiry** | Signed-in | Message (+ event date, package) goes to the vendor's Enquiries; vendor is notified; couple sees it under `/enquiries` |
| **Review** | Anyone, signed in or not (throttled, Turnstile) | Public review; only booking-backed reviews affect ranking |
| **Report vendor** | Signed-in | Goes to admin as a violation report |
| **Compare** | Anyone | Put vendors side by side |

Then they **deal directly** — price, deposit and payment happen between them, off the platform.

> ⚠️ **Enquiry is one message + one reply, not a chat.** `enquiries` has a single `message` and a single `reply`. A real back-and-forth thread would be new work (new `enquiry_messages` table, notifications per message). WhatsApp is the conversation channel today.

Booking through Neekah (couple books → pays → vendor verifies) is built but switched off (`NEEKAH_BOOKINGS_ENABLED=false`). Vendors can still record bookings on their side.

---

## 5. Couple — wedding project & planning

Registration: email or **Google sign-in**, then a phone number is required before anything else.

### Wedding project
- `/weddings/create` — title, date, negeri/city, total budget, notes. This is the "project" everything else hangs off.
- **Invite pasangan**: send an invitation by email → partner accepts at `/invitations/{id}` → becomes a member (role Partner) and manages the same wedding. Owner can remove members.
- Sidebar shows "N hari lagi" to the date; dashboard shows progress, next steps and a 4-step "make your card" guide (save → details → publish → first views).

### Planning tools (all need a wedding; without one the user is sent to create it)
| Page | What |
| --- | --- |
| `/checklist` | Preparation checklist seeded from the admin's master list, grouped in phases (Perancangan Awal, Borang & Dokumen Nikah, Kursus & Kesihatan, Urusan Wali, Persediaan Pengantin / Akad / Majlis, Selepas Nikah), each task with a "months before" hint. Tick many at once, add own tasks, progress counted live |
| `/budget` | Planned amount per category vs actual; actuals come from recorded bookings, so while bookings are off they stay mostly empty |
| `/timeline` | Run-of-day / tentative, items can link to a vendor |
| `/tetamu` | Guest list: side (bride / groom / both), group, pax, notes. **Bulk paste import** ("nama, telefon, pihak, kumpulan, pax"), send each guest a **personal WhatsApp invite link**, see their RSVP |
| `/enquiries` | Enquiries sent to vendors and their replies |

---

## 6. Kad Kahwin Digital — the standout feature

Editor at `/kad`, live preview at `/kad/preview`.

**Customisation**
- **50 designs** in 6 categories (Traditional, Modern, Floral, Islamic, Minimalist, Creative), switchable any time.
- **Recolour** (palette of colour roles) and **change fonts** without breaking the design.
- Content: bride/groom full & short names, parents, bios, salutation, invitation note, date & time, venue, address, Google Maps link.
- Sections (widgets) on/off: **countdown**, itinerary/tentatif, location, photo gallery, contacts, **RSVP** (with deadline), **ucapan/wishes**, **salam kaut** (bank accounts + QR), closing note, **background music** from an admin-curated playlist.
- The editor preview uses the same renderer as the real card — what the couple sees is exactly what guests get.

**Own subdomain**
- Couple picks an address → `ainahakim.neekah.my`. Availability is checked live as they type, with suggestions if taken.
- Publish / unpublish.
- Nice WhatsApp link preview (generated OG image) and a plain-text version for crawlers and no-JS.
- Guests: view, RSVP (linked to the guest list when opened from a personal link), leave wishes, add to calendar (`.ics`).
- **Statistik** (`/kad/statistik`): daily views, RSVP counts.
- **NFC / QR card**: a physical card tapped or scanned → `/n/{uid}` → redirects to the couple's card (admin assigns cards).

> Card pages stay Malay only, by decision.

> 💡 **Traffic gap:** a published card currently has **no "Dibuat dengan Neekah" link back**. Every card is shared to hundreds of guests — many of them future couples — so a small footer credit linking to `/kad-jemputan` would be the cheapest acquisition channel the platform has.

---

## 7. Admin

Dashboard & analytics · vendor approval (single + bulk), tier override · users (view, impersonate, switch role, deactivate, delete with safety checks) · reviews moderation (hide/restore/add) · violations (report → action ladder: warning → point deduction → suspension → removal) · categories (with order) · master checklist (sections/items, order) · card music playlist · NFC cards · blog posts (rich editor) · announcements to user segments (email + in-app) · settings: contact/WhatsApp, SEO, image sizes, Telegram alerts, Turnstile, payment methods · log viewer.

Admin gets **Telegram alerts** on new couple and vendor sign-ups.

---

## 8. Notifications

In-app bell (`/notifications`) + email, all queued. Covers enquiry received/replied, vendor registered/approved/status changed, partner invited, violations, announcements, and the booking/payment set when bookings are on.

---

## 9. Not there yet (so an agent doesn't assume it)

- Real enquiry **chat thread** (only one reply today).
- Online payment gateway (payment methods exist as switches in settings; no gateway integration).
- Neekah credit/backlink on published cards.
- Seating plan, AI planner, mobile app / REST API (Phase 3 in the kertas kerja).
