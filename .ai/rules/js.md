---
paths:
  - 'resources/js/**'
  - resources/js/navigation.js
---

# Js

## Vue runs as islands mounted from Blade
Vue is wired the way herepay does it, not with Inertia: Blade stays the page (routing, auth, layout, meta tags) and anything interactive is a component mounted into `<div data-vue="component-name" data-props="{{ json_encode([...]) }}">`. resources/js/vue.js auto-registers every resources/js/components/**/*.vue by kebab-cased filename, so a new component needs no registration. Always render a usable server-side fallback inside the mount element — it is what Google, a WhatsApp link preview and a visitor without JavaScript get. Tables use components/ui/DataTable.vue (TanStack) against a controller `data()` method returning {data, meta}; sorting, searching and paging belong in the query, never in the browser.

## Navigation swaps regions; it never owns routing
resources/js/navigation.js intercepts same-origin link clicks, fetches the page the server would have rendered, and replaces <main> and every [data-nav-region] (the dashboard sidebar nav and the public header nav), then re-mounts Vue islands. Laravel still routes, authorises and renders every page, so meta tags, SEO and link previews are untouched. Any page missing <main> or a region, any non-HTML or non-OK response, and any modified click falls through to an ordinary page load — a failure here must never be worse than the browser's own behaviour. Keep new layouts wrapping their content in <main>, and mark navigation that shows an active state with data-nav-region.
