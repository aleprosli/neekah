---
paths:
  - 'resources/js/**'
  - resources/js/navigation.js
---

# Js

## Vue runs as islands mounted from Blade
Vue is wired the way herepay does it, not with Inertia: Blade stays the page (routing, auth, layout, meta tags) and anything interactive is a component mounted into `<div data-vue="component-name" data-props="{{ json_encode([...]) }}">`. resources/js/vue.js auto-registers every resources/js/components/**/*.vue by kebab-cased filename, so a new component needs no registration. Always render a usable server-side fallback inside the mount element — it is what Google, a WhatsApp link preview and a visitor without JavaScript get. Tables use components/ui/DataTable.vue (TanStack) against a controller `data()` method returning {data, meta}; sorting, searching and paging belong in the query, never in the browser.

## Navigation swaps regions; it never owns routing
resources/js/navigation.js intercepts same-origin link clicks, fetches the page the server would have rendered, and replaces <main> and every [data-nav-region] (the dashboard sidebar nav and the public header nav), then re-mounts Vue islands. Laravel still routes, authorises and renders every page, so meta tags, SEO and link previews are untouched. A page declares its shell with <meta name="page-shell"> (site, dashboard, card); when the incoming shell differs the swap is abandoned for an ordinary page load, because a dashboard holds its <main> inside a grid beside a sidebar and a public page does not. Regions are matched pairwise, so a dashboard's header, sidebar and mobile bottom bar all update. Everything else is kept, so a page whose top-level <body> furniture (tag, id and data-* attributes of each direct child, read from server markup) differs from the current one is never swapped into — otherwise the vendor list's search bar, compare tray and filter dialog stayed on every page reached from it. Page-level UI outside <main> therefore forces a full load to and from that page; put it inside <main> if it should swap. Any page missing <main> or a region, any mismatch in region counts, any non-HTML or non-OK response, and any modified click falls through to an ordinary page load — a failure here must never be worse than the browser's own behaviour. Keep new layouts wrapping their content in <main>, and mark navigation that shows an active state with data-nav-region.

## Island components load as their own chunks, and data-vue-lazy waits for the viewport
resources/js/vue.js globs components/**/*.vue lazily, never with { eager: true }. Vite gives each component its own chunk, so a page downloads only the islands it declares; making the glob eager again puts all 80-odd components back into the entry bundle and roughly quintuples it. mountIslands is therefore async and mountIsland claims data-vue-mounted before awaiting its chunk, so a page swap landing mid-fetch cannot mount the same element twice, and it checks el.isConnected before mounting.

Add data-vue-lazy to a mount element to hold it until it is within 300px of the viewport. That is opt-in, not the default, because an island inside a closed dialog never intersects anything and would never mount. Use it for long repeated lists, such as the fifty tiles in sites/templates.blade.php, not for anything above the fold or hidden.

## Prefer Vue islands over Blade for UI; QR codes are generated in Vue
Owner, 26 Sep 2026: build new interactive UI as Vue islands (resources/js/components) rather than Blade markup — it is more responsive and is the direction the owner wants. Blade stays the page shell (routing, auth, layout, SEO) and still renders the minimal server fallback inside each mount element (js.md), but forms, pickers, previews and anything that reacts to input belong in Vue. QR codes are generated client-side in Vue (a JS QR library), not with a PHP QR package; server-side rendering of a QR is only for a case that truly needs a file without a browser. Existing Blade pages need not be rewritten unless being reworked anyway.
