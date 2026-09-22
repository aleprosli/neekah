---
paths:
  - 'resources/js/components/card/**'
---

# Components Card

## The card renderer scales in cqw and ships its own words
A canvas is designed at 1080×1920 and rendered at any width, so nothing in resources/js/card/layerStyle.js is in pixels: positions are percentages of the scene and every length is cqw (1% of the scene's width) inside a container-query stage. That is why one component is a 110px gallery thumbnail and a full-screen card. Keep new layer styling in those units.

The card shell ships no translation dictionary (App\Support\Translations::GROUPS_BY_SHELL has 'card' => []), so every word the renderer prints arrives in the `labels` prop from lang/*/card.php. Never reach for $t() inside a card component.

Opacity on a role colour uses color-mix(), not rgba(): the colour is a CSS variable and is not known until the palette is applied.

CardView draws the gate, the music button and the reveal-on-scroll (adding .is-visible, which app.css styles). The old vanilla card JS in app.js is gone — don't add page-level card scripts back.
