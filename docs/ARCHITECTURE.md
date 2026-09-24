# Neekah — Architecture

A map of the system for anyone (human or AI agent) about to change it. It says **where things live and how they connect**. The *why* behind individual decisions lives in `.ai/rules/` — this file points at those rules instead of repeating them. When the two disagree, the rules and the code win; fix this file.

What users see and can do: `docs/FEATURES.md`. Product context: `docs/NEEKAH-Kertas-Kerja.md` (Malay working paper) and `.ai/rules/general.md`.

---

## 1. What the system is

Wedding marketplace + planning platform for Malaysia. Three roles on one `users` table (`App\Enums\UserRole`):

| Role | Area | What they do |
| --- | --- | --- |
| `customer` (couple) | `/dashboard`, `/checklist`, `/tetamu`, `/timeline`, `/budget`, `/kad`, `/enquiries` | Plan the wedding, find vendors, publish a digital invitation card |
| `vendor` | `/vendor/*` | Business profile, packages, portfolio, availability, enquiries, reviews, points |
| `admin` | `/admin/*` | Vendor approval & tiers, users, reviews, categories, master checklist, blog, announcements, card music, NFC cards, settings |

**Current positioning — a network, not a checkout.** Couples contact vendors directly (WhatsApp) and pay them directly. Neekah takes no payment and no commission (`Booking::COMMISSION_RATE = 0`). The booking/payment-recording code still exists but the couple side is off behind `config('neekah.bookings_enabled')` (`NEEKAH_BOOKINGS_ENABLED`). Public copy must never promise booking or payment through Neekah. See `.ai/rules/general.md`.

---

## 2. Stack

| Layer | Choice |
| --- | --- |
| Backend | Laravel 13, PHP 8.3+ (prod 8.5, local 8.4) |
| DB | MySQL 8 in prod, SQLite locally |
| Queue / cache / session | `database` driver for all three |
| Frontend | Blade pages + **Vue 3 islands** (no Inertia, no SPA router), Tailwind v4, Vite 8 |
| Media | Disk `public` → local or Cloudflare R2 (`MEDIA_DISK`), served via `cdn.neekah.my` |
| Auth | Session auth + Google (Socialite), `lab404/laravel-impersonate` for admin impersonation |
| Tests | Pest 5 (`tests/Feature/**`) |
| Hosting | One Ubuntu server behind Cloudflare, nginx + supervisor queue worker, deploy = `git pull main` |

`public/build` **is committed** — the server does not build assets. Any change under `resources/js` or `resources/css` needs `npm run build` and the build committed.

---

## 3. Request lifecycle

```
Request
  → BeginPageMetadata (prepended globally; resets the scoped Seo binding)
  → web group + EnsureAccountIsActive (signs out deactivated users)
             + EnsurePhoneNumber (holds signed-in users without a phone at /telefon)
  → locale:{ms|en}  (SetLocale, from the route group)
  → auth / role:{customer|vendor|admin} (EnsureUserHasRole) / wedding (EnsureUserHasWedding)
  → Controller  (thin: FormRequest validates → Policy authorises → Action does the work)
  → Blade view  (layout + <x-seo.tags> + Vue island mount points)
```

Configured in `bootstrap/app.php`, which also:
- sets the locale on exceptions so a 404 under `/en` renders in English;
- turns `PostTooLargeException` into a validation error instead of a blank "page expired".

---

## 4. Routing (`routes/web.php`)

Everything is in one file. Three groups:

1. **Invitation subdomains** — `Route::domain('{subdomain}.'.config('neekah.site_domain'))`: the published card (`sites.show`), RSVP post, `.ics` calendar, OG preview image. Needs wildcard DNS locally (Herd `*.test`).
2. **NFC / QR redirect** — `/n/{uid}` → the card linked to a physical NFC card.
3. **The site, once per language** — a `$site` closure registered for each `Locales::codes()`: Malay at `/`, English under `/en` with a name prefix. `App\Routing\LocalisedUrlGenerator` (bound in `AppServiceProvider`) makes `route('x')` answer in the language being served. Compare route names with `Locales::baseRouteName()`, never raw.

Inside `$site`: public pages (vendor list at `/`, vendor profile, compare, blog, card gallery `/kad-jemputan`, about), guest auth, then the `vendor`, customer (`auth`) and `admin` groups.

Conventions: Malay URL slugs for user-facing pages (`/akaun`, `/tetamu`, `/kad`); `*.data` routes return JSON for `DataTable.vue`; planning pages resolve the couple's wedding and carry `->middleware('wedding')`. Sitemaps (`/sitemap*.xml`) sit outside the language loop and list both languages.

---

## 5. Backend layout (`app/`)

| Directory | Role |
| --- | --- |
| `Http/Controllers/{Admin,Customer,Vendor,Auth}` | One namespace per area; public controllers at the root. Thin, single-purpose (`BookingCompletionController`, `VendorApprovalController`, …) |
| `Http/Requests` | All validation. `Store*` / `Update*` naming |
| `Http/Middleware` | See §3 |
| `Policies` | `Booking`, `Enquiry`, `Package`, `Review`, `User`, `Vendor` |
| `Actions` | **Business logic lives here**, one invokable-style class per verb: `CreateBooking`, `VerifyManualPayment`, `AwardVendorPoints`, `ApplyViolationAction`, `RecalculateVendorStats`, `RegisterVendor`, `SeedWeddingChecklist`, `StoreOptimizedImage`, `RecordRsvp`, `ImportWeddingGuests`, `DeleteUserAccount`, … |
| `Models` (+ `Concerns/HasTranslatedText`) | Eloquent models, §6 |
| `Enums` | All enums. ⚠️ `app/*.php` (`App\UserRole`, `App\BookingStatus`, …) are **empty leftover stubs** — always import from `App\Enums` |
| `Casts/Translatable` | JSON `{"ms":…, "en":…}` column that reads in the current locale (admin-authored text: categories, checklist) |
| `Support` | Services and value helpers, see below |
| `Support/Card` | The invitation card design engine, §8 |
| `Notifications` | All `ShouldQueue`; queue runs `after_commit` |
| `Jobs` | `SendAnnouncement`, `SendTelegramAlert` |
| `Rules` | `Turnstile` (Cloudflare challenge; skipped when keys are blank) |
| `Console/Commands` | `neekah:optimize-images`, `neekah:robots`, `neekah:mail-test` |

**Key `Support` classes**

| Class | Purpose |
| --- | --- |
| `Seo` | The only source of meta tags and JSON-LD; controllers inject it and call `title/description/canonical/schema/…`. Printed once by `components/seo/tags.blade.php` |
| `SettingGroup` → `ContactSettings`, `SeoSettings`, `ImageSettings`, `TurnstileSettings`, `TelegramSettings`, `PaymentSettings` | Admin-editable settings (Admin → Tetapan), one `settings` row per `prefix.key`, read via cached `Setting::values()` |
| `ContentVersion` | Version-keyed cache for public pages (`global()` / `forVendor($id)`); writes bump the version, nothing is flushed. Never cache objects — `serializable_classes` is `false` |
| `Locales`, `Translations` | Language codes, prefixes, route-name mapping |
| `VueProps` | Encodes island props; exposed as the `@vueProps` Blade directive |
| `InvitationSetup` | Derives the 4-step "make your card" guide from existing data |
| `HtmlSanitizer` | Blog body sanitising on save |
| `States`, `PhoneNumber`, `CallingName`, `TableFilter`, `AnalyticsPeriod`, `MonthlyTotals`, `StoredNotification`, `NeekahMail` | Small domain helpers |

---

## 6. Domain model

```
User ─┬─ hasOne ── Vendor ─┬─ belongsToMany Category (primary: vendors.category_id)
      │                    ├─ Package, PortfolioItem, VendorUnavailableDate
      │                    ├─ Enquiry, Booking, Review
      │                    └─ VendorPoint (ledger), VendorViolation
      │
      ├─ belongsToMany ── Wedding  (pivot role: WeddingRole Owner | Partner)
      │                    ├─ WeddingInvitation (invite a partner by email)
      │                    ├─ WeddingTask ← seeded from ChecklistSection/ChecklistItem (master list, admin-edited)
      │                    ├─ WeddingBudgetItem (per Category)
      │                    ├─ WeddingTimelineItem (optionally → Vendor)
      │                    ├─ WeddingGuest ── hasOne WeddingRsvp
      │                    ├─ Booking, Enquiry
      │                    └─ hasOne WeddingSite (the digital card)
      │                          ├─ SiteTemplate (design; scenes JSON from SiteTemplateSeeder)
      │                          ├─ CardMusicTrack (admin-managed playlist)
      │                          ├─ WeddingSitePhoto, WeddingRsvp (+ wishes), WeddingSiteView (daily counts)
      │                          └─ CardNfcCard (physical card → /n/{uid})
      │
      └─ Booking ─┬─ Package
                  ├─ Payment (manual record → vendor verifies)
                  └─ hasOne Review ── ReviewPhoto

Standalone: Post (blog), Announcement (→ users), Setting, Category
```

Notable rules (details in `.ai/rules/app.md`, `models.md`):
- **Vendor lifecycle**: `VendorStatus` Pending → Approved / Rejected / Suspended (`ChangeVendorStatus`). **Tier** (`VendorTier`): New → Verified → Trusted → Top → Recommended.
- **Points & ranking** weights are fixed by the kertas kerja. Points are a ledger (`VendorPoint`, `PointReason`); `RecalculateVendorStats` derives rating, completion and response rate — `response_rate` is measured, never set.
- **Reviews** are open to anyone; only booking-backed ones (`rankingReviews`) move rating and ranking. Admin moderates via `ModerateReview`.
- **Bookings**: `BookingStatus` PendingPayment → Confirmed → Completed / Cancelled. Commission rate/amount stamped per booking.
- **Payments**: no gateway. Couple records a payment → `AwaitingVerification` → vendor verifies (`VerifyManualPayment`, awards points) or rejects.
- **Violations**: reports → admin review → `ApplyViolationAction` ladder (warning → deduction → suspension → removal).
- **Account switching**: a couple with no activity can become a vendor (`/vendor/tukar-akaun`); anything else goes through admin.

---

## 7. Frontend

```
resources/
├─ views/                 Blade pages, one folder per area (admin, customer, vendor, vendors, blog, sites, auth, account…)
│  └─ components/layouts  Page shells: app (base), site, auth, dashboard, customer, vendor, admin; seo/tags.blade.php writes all meta
├─ js/
│  ├─ app.js              Entry
│  ├─ vue.js              Finds [data-vue], lazy-loads components/**/*.vue by kebab-case name, mounts
│  ├─ navigation.js       Intercepts same-origin clicks, fetches server HTML, swaps <main> + [data-nav-region]
│  ├─ i18n.js, form-guard.js
│  ├─ components/{admin,customer,vendor,public,account,auth,ui,card}
│  ├─ card/               tokens.js, layerStyle.js, preview.js — card rendering helpers
│  └─ composables/
└─ css/app.css            Tailwind v4
```

- **Islands**: Blade renders `<div data-vue="customer-checklist-page" data-props="@vueProps([...])">` with a usable fallback inside. No registration needed for new components. `data-vue-lazy` defers until near the viewport. See `.ai/rules/js.md`.
- **Page swap, not SPA**: Laravel still routes, authorises and renders every page; `navigation.js` only swaps regions. It has deliberate bail-outs — read the rule before changing it.
- **Shared UI**: `components/ui/*` (`DataTable`, `UiField`, `UiSelect`, `UiConfirm`, charts, `UiTurnstile`, `UiRichEditor` on TipTap…). Reuse before writing new.
- Fonts are self-hosted; the CSP allows nothing from Google Fonts at runtime.

---

## 8. The digital invitation card (`kad`)

The most self-contained subsystem.

1. **Designs are code.** `App\Support\Card\Catalog` defines 50 designs (palette of 10 colour roles, 4 font faces, cover/invitation/event compositions). `SceneComposer` + `Covers`/`CoversB`/`Inner` compile them into layers; `SiteTemplateSeeder` writes them to `site_templates.scenes`. Never edit that JSON by hand — change the catalogue and re-seed.
2. **Role tokens, not values.** Layers store `role:acc` / `role:d`; `CardProps` turns the palette into `--c-*` / `--f-*` CSS variables so a couple can recolour without recomposing. `CardDesignTest` fails on baked colours.
3. **Text tokens** (`{{bride}}`, dates…) resolve in the browser via `resources/js/card/tokens.js`. Add a token in both PHP and JS.
4. **Widgets** after the artwork (countdown, itinerary, location, gallery, gift, RSVP, wishes, contacts, music) come from the couple's data: `CardWidgetData` server-side, mirrored in `card/preview.js` for the live editor.
5. **One renderer.** `components/card/CardView.vue` draws the public card, the editor preview and every gallery tile. `sites/show.blade.php` mounts it over a plain-text fallback (for WhatsApp previews, crawlers, no-JS).
6. **Serving.** Published on `{subdomain}.{NEEKAH_SITE_DOMAIN}`; RSVP via `RecordRsvp`; guests can get personal links (`WeddingGuestShareController`, tokens fail silently); views counted per day for `/kad/statistik`.
7. Card pages stay **Malay only** by decision.

Rules: `.ai/rules/card.md`, `components-card.md`, `sites.md`, `customer.md`.

---

## 9. Cross-cutting concerns

| Concern | Where / how |
| --- | --- |
| **i18n** | `lang/ms` + `lang/en`, must keep key parity; missing keys fall back to Malay silently. DB text uses the `Translatable` cast. Flash messages in `lang/*/flash.php`. Rule: `lang.md` |
| **SEO** | `Seo` service only; sitemaps with honest `lastmod`; `public/robots.txt` is a real file (`neekah:robots`) |
| **Caching** | `ContentVersion` keys for public pages; `database` store, so every read is a MySQL round trip — `PublicPageCostTest` guards query counts |
| **Images** | Every upload goes through `StoreOptimizedImage` (sizes/quality from `ImageSettings`); thumbnails get new names on redraw, never overwritten |
| **Notifications** | Queued, mail + database (bell at `/notifications`); admin Telegram alerts via `SendTelegramAlert::about()`; announcements via `SendAnnouncement` |
| **Security** | Turnstile on public forms; throttles on public POSTs; security headers + CSP live in **nginx on the server**, not in this repo (`general.md`) |
| **Admin tooling** | Impersonation, log viewer (`viewLogViewer` gate), bulk actions post `ids[]` through the same Action as the single one |

---

## 10. Where to start for common changes

| Task | Touch |
| --- | --- |
| New customer planning page | Route in the `auth` group with `->middleware('wedding')` → `Customer\*Controller` → Blade in `views/customer` mounting a `Customer*Page.vue` island |
| New admin setting | Class extending `SettingGroup` + `Update*SettingsRequest` + `SettingController` method + `AdminSettingsPage.vue` |
| New card widget / token | `CardWidgetData` + `card/preview.js` (both), component in `components/card`, token in `tokens.js` |
| New card design | `Support/Card/Catalog` → `php artisan db:seed --class=SiteTemplateSeeder` |
| New master checklist item | Admin UI (`/admin/checklist`) or `ChecklistSeeder`; weddings only ever gain items |
| Change business rule (points, tiers, payments) | The Action class, and read `.ai/rules/app.md` first |
| Any user-facing string | Both `lang/ms` and `lang/en` |

Before editing any file: open `.ai/rules/index.md`, read the rule files whose globs match, then `grep -rin '<keyword>' .ai/rules`.
