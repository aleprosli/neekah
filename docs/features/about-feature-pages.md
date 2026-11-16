# About dropdown and feature pages

Marketplace, Neekah Kenangan, Neekah Pro and Neekah Boost each have their own page under `/ciri`,
all built on the same frame. On a wide screen they are reached from a "Tentang" dropdown in the
site header. On a phone they are reached from a "Tentang" sheet that opens from the bottom bar.
About (`/about`) no longer explains them inline. It only links to them.

| | |
| --- | --- |
| Type | Feature |
| Area | Public site |
| Roles | Guest, Couple, Vendor |
| Branch | `feature/about-feature-pages` (first shipped) |
| Last updated | 2026-11-16 |

## How it works

- **Header (md and up):** "Tentang" is a `<details data-popover>` dropdown. It shows Tentang Neekah
  and Cara Ia Berfungsi, then two groups: "Untuk pengantin" (Marketplace, Neekah Kenangan) and
  "Untuk vendor" (Neekah Pro, Neekah Boost). The panel is anchored to the whole header pill, not
  to the button, so it stays on screen at tablet widths.
- **Phone (below md):** the header has no menu, so "Tentang" in the bottom bar opens the same
  menu as a sheet above the bar. The sheet also shows pills for Tentang Neekah, Cara Ia Berfungsi
  and Blog.
- Both menus render `x-site.about-menu`, so they always list the same pages. Both close on an
  outside click (`app.js` popover handler) and on page swap (they sit inside `[data-nav-region]`).
- **About:** keeps the hero, how it works, the planning features, deal-direct and for-vendors
  sections. The old Kenangan, Marketplace preview and Pro/Boost sections are replaced by a
  "Terokai Neekah" card grid (`#terokai`).
- **Each feature page:** breadcrumb → feature chip → h1 → floral rule → lead → actions (hero),
  then the page's own sections, then a CTA card, then "Lagi daripada Neekah" (cards for the
  other features on sale).
- For a signed-in vendor, the Pro and Boost CTAs go to `vendor.pro.index` and `vendor.boost.index`.
  Everyone else is sent to `vendor.register`.

## Routes

| Route name | URL | What |
| --- | --- | --- |
| `features.marketplace` | `/ciri/marketplace` | Search form, all active categories, 6 top-scored approved vendors, WhatsApp help (when set) |
| `features.kenangan` | `/ciri/kenangan` | Four guest steps (`KenanganPhoneDemo.vue`), Basic/Pro album tiers at admin prices |
| `features.pro` | `/ciri/pro` | Pro story (`VendorProShowcase.vue`, Pro Elite at `#elite`), Basic vs Pro plans (`#pelan`) |
| `features.boost` | `/ciri/boost` | Three steps, where tokens come from (welcome gift, Pro monthly, packs) |

All four also exist under `/en/ciri/...` (`en.features.*`).

## Files

| File | What |
| --- | --- |
| `app/Support/FeaturePages.php` | The single list of feature pages and whether each is on sale (`isAvailable`, `available(except:)`) |
| `app/Http/Controllers/FeaturePageController.php` | One action per page; 404 when not on sale; Seo title, description and breadcrumbs |
| `app/Http/Controllers/LandingController.php` | About now passes `explore` and `eliteUrl` instead of the Kenangan/Pro/Boost data |
| `app/Http/Controllers/SitemapController.php` | Adds the feature pages that are on sale, in both languages |
| `resources/views/features/{marketplace,kenangan,pro,boost}.blade.php` | The four pages |
| `resources/views/components/site/feature-page.blade.php` | Frame: header, hero slot, sections, explore row, footer, bottom bar |
| `resources/views/components/site/feature-hero.blade.php` | Shared hero |
| `resources/views/components/site/feature-section.blade.php` | Shared section (tone: plain, raised, deep) with centred heading |
| `resources/views/components/site/feature-explore.blade.php` | Feature cards (`data-feature-card="<key>"`), on About and every feature page |
| `resources/views/components/site/feature-cta.blade.php` | Closing CTA card, also used by About |
| `resources/views/components/site/button.blade.php` | Primary and secondary pill buttons |
| `resources/views/components/site/about-menu.blade.php` | Menu contents for the header dropdown and the phone sheet (`compact`) |
| `resources/views/components/site/header.blade.php` | "Tentang" dropdown; nav labels no longer wrap; Blog hidden below lg |
| `resources/views/components/site/mobile-nav.blade.php` | "Tentang" opens the sheet |
| `resources/views/components/site/footer.blade.php` | Links to the feature pages on sale |
| `resources/views/landing.blade.php` | Inline Kenangan, Marketplace preview and Pro/Boost sections removed; explore grid added |
| `resources/views/vendors/index.blade.php` | "Pilihan Elite" link goes to `features.pro#elite` |
| `lang/{ms,en}/pages.php` (`pages.features.*`), `lang/{ms,en}/seo.php` (`seo.features.*`) | New copy; existing `pages.landing.kenangan/plans/boost/*` strings are reused |
| `tests/Feature/FeaturePagesTest.php`, `tests/Feature/LandingPageTest.php` | Coverage |

## Data

No migrations. Whether a page is live comes from existing settings (Admin → Tetapan):

| Page | On sale when |
| --- | --- |
| Marketplace | Always |
| Neekah Kenangan | `CameraSettings::isEnabled()` |
| Neekah Pro | `ProSettings::isEnabled()` |
| Neekah Boost | `BoostSettings::isEnabled()` (the Pro token card appears only when Pro is on sale too) |

## Rules

- A page that is not on sale answers 404 and is left out of the dropdown, the phone sheet, About,
  the footer, the sitemap and the explore rows. `FeaturePages` is the only place that decides this.
- Neekah is still a network. No feature page may promise booking or payment through Neekah.
  On the Pro page, online booking is described only as "deposit terus ke akaun vendor".
- Every new feature page uses the shared frame (`x-site.feature-page` and the `feature-*`
  components), so the pages stay visually the same. See `.ai/rules/features.md`.

## Deploy notes

- `npm run build` (new Tailwind classes; `public/build` is committed).
- `route:cache` on production (new routes). The standard `~/bin/deploy-neekah` runs `optimize`, which covers it.
- No migrations or settings changes.

## Known limits

- The Boost page is only reachable while Boost is switched on. With it off, `/ciri/boost` is 404 on purpose.
- The blog index has no phone bottom bar (a separate Vue page), so the phone sheet is not shown there.

## History

- 2026-11-16: First shipped. Includes the phone sheet and a fix for nav labels wrapping on tablet widths (`feature/about-feature-pages`).
