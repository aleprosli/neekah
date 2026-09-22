---
paths:
  - 'resources/views/sites/**'
---

# Sites

## The invitation card is one Vue island over a plain-text fallback
`sites/show.blade.php` renders nothing of the card itself: it mounts
`resources/js/components/card/CardView.vue` with the props from
`App\Support\Card\CardProps`, and what sits inside the mount element is the
invitation in plain words (names, date, venue, address, tentatif, contacts) —
that is what a WhatsApp link preview, a crawler and a visitor without JavaScript
get, and it must keep working without a script.

The same component draws the public card, the editor's live preview and every
tile in the design gallery, so a couple can never be shown something their guests
will not get. Assert on the props (`cardProps()` / `cardWidgets()` in tests/Pest.php),
not on words: the card's whole dictionary (`lang/*/card.php`) travels with every
card, so "Salam kaut" appears in the HTML even when the gift section is off.

Card pages stay Malay by decision (.ai/rules/lang.md), and the gallery groups by
`category` (Traditional, Modern, Floral, Islamic, Minimalist, Creative) with
Malay labels in `pages.template_category`. The URL carries the English value —
translating it would move a URL Google already holds.

The old `.nk-sheet` Blade designs, the ornament and layout partials, the dock and
the petal motion are gone; nothing should render a card in Blade again.
