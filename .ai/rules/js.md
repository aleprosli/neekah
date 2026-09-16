---
paths:
  - 'resources/js/**'
---

# Js

## Vue runs as islands mounted from Blade
Vue is wired the way herepay does it, not with Inertia: Blade stays the page (routing, auth, layout, meta tags) and anything interactive is a component mounted into `<div data-vue="component-name" data-props="{{ json_encode([...]) }}">`. resources/js/vue.js auto-registers every resources/js/components/**/*.vue by kebab-cased filename, so a new component needs no registration. Always render a usable server-side fallback inside the mount element — it is what Google, a WhatsApp link preview and a visitor without JavaScript get. Tables use components/ui/DataTable.vue (TanStack) against a controller `data()` method returning {data, meta}; sorting, searching and paging belong in the query, never in the browser.
