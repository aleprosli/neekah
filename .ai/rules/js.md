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
