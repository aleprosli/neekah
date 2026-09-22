---
paths:
  - 'app/Support/Card/**'
---

# Card

## Card designs are composed code, and every colour and face is a role token
The 50 invitation designs live in App\Support\Card\Catalog (palette of ten roles, four faces, a cover/invitation/event composition) and are compiled into layers by SceneComposer + the Covers/CoversB/Inner traits. A design is data on site_templates.scenes, written by SiteTemplateSeeder — never edited by hand. Changing a design means changing the catalogue and re-running `php artisan db:seed --class=SiteTemplateSeeder --force`.

Layers must store colours as "role:acc" and fonts as "role:d", never a baked value: CardProps turns the palette into --c-* / --f-* CSS variables, which is what lets a couple recolour a card without recomposing 1,800 layers, and CardDesignTest fails on a baked colour (a short allowlist covers real artwork colours like polaroid white and film black).

Text layers carry {{tokens}}; resources/js/card/tokens.js resolves them in the browser (dates via Intl ms-MY), which is what makes the editor redraw as the couple types. Add a token in both places. Faces are self-hosted through vite.config.js bunny() — the production CSP allows no font or stylesheet from anywhere else, so never load Google Fonts at runtime.

The three canvases are artwork; everything after them is a widget drawn from the couple's own data (App\Support\Card\CardWidgetData, mirrored for the preview in resources/js/card/preview.js). Keep those two in step.
