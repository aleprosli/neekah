---
paths:
  - 'resources/views/sites/**'
---

# Sites

## Invitation cards are printed-card sheets; .nk-* classes beat Tailwind utilities
The owner said the old cards "nampak terlalu html" (Sep 2026). Every card is now one sheet (.nk-sheet + .nk-paper texture + .nk-sheet-frame double rule), opened from a sealed envelope gate (partials/gate, wax seal with WeddingSite::initials()), with monogram, date-block (hari | tarikh | bulan tahun), script section headings (partials/section-heading) and a fixed icon toolbar (partials/dock: Lokasi, Kalendar, RSVP, Hubungi, Hadiah — only the ones the card can answer). Keep new sections in that vocabulary, not rounded web panels or emoji chips. Bismillah shows only where design.bismillah is true (SiteTemplate::showsBismillah), never on the modern designs.

Trap: the .nk-* classes in app.css are unlayered, so they override Tailwind utilities on the same element (e.g. text-[6px] on .nk-eyebrow did nothing). Thumbnails scale with container units (@container + text-[Ncqw]) because the same partial renders at ~110px in the editor and ~170px in the gallery. Ornaments take $ornamentSize to shrink.

Adding a template = a row in SiteTemplateSeeder (updateOrCreate by slug); prod needs `php artisan db:seed --class=SiteTemplateSeeder --force`. PublicSiteTest pins the count.

## A kad is drawn from CardDesign, and its sections come from CardSections
Never read SiteTemplate->design or build css variables by hand. Every card — the gallery thumbnail, the sample preview, the published sheet and the OG image — renders from App\Support\CardDesign: the template's design merged with wedding_sites.design_overrides. SiteTemplate delegates to it; $template in the sites/ views is a CardDesign, not the model, so use $siteTemplate when you need the row's name or slug.

CardDesign::clean is the only gate on what a couple may change (listed layout/ornament/motion/artwork/texture, hex colours, fonts from CardArt::FONTS). Those values land in a style attribute on a public page, so never widen it to free text.

Sections are partials in sites/sections and the sheet is a loop over CardSections::resolve. Each partial keeps its own data guard, so switching one on never leaves a hole. The arrangement is stored as {key, on}, never as a list of what is on: a missing key would mean both "off" and "added after this couple last saved", and a new section would silently never appear. Hero and closing stay fixed.

Artwork lives in sites/artwork drawn in var(--nk-accent); textures are colourless tiling files under public/img/card/texture layered through --nk-texture, because a photographic texture carries a hue that fights most of the palettes. A layout whose hero is a photo gets artwork 'none'.

Trap: new arbitrary Tailwind classes in these views need `npm run build` before they exist — @source scans compiled Blade, so a freshly cleared view cache means a class like h-[100svh] silently resolves to nothing and overflow-hidden then clips the whole piece.
