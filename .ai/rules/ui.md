---
paths:
  - resources/js/components/ui/DataTable.vue
---

# Ui

## One table component, cards on a phone
Every table in the application renders through components/ui/DataTable.vue. Give it `dataUrl` for a server-paged list (controller `data()` returning {data, meta}; searching, sorting and paging in the query) or `rows` for a list already in hand. Columns are [{key, label, sortable?, sort?, align?, type?}]; a page that needs custom markup uses the `#cell-<key>` or `#action` scoped slots rather than writing its own table. Below md it renders cards, not a scrolling table: the first column is the card title, a trailing html column is the badge, the rest become label/value rows, and sorting moves to a select. ResponsiveTablesTest fails the suite if a second <table> appears anywhere under resources, or if any table sits outside something that scrolls.

## Check mobile width with a long business name, not with demo data
Demo names are short, so a page can look fine at 360px and still blow out in production. Two real cases, both now fixed: the admin dashboard's "Menunggu kelulusan" grid (a grid child without min-w-0 widened the whole page to 731px at a 360px viewport) and DataTable's mobile card (the ul/li/a/dl chain, plus the mobile sort select, which is as wide as its longest option unless it carries w-full).

To check: set a vendor name to something like "Studio Fotografi Dan Videografi Perkahwinan Nur Aisyah Enterprise Sdn Bhd", load the page, and confirm document.body.scrollWidth equals the viewport. Chrome on macOS will not open a window under 500px, so narrow document.documentElement instead — no Tailwind breakpoint sits between 390 and 500, so the layout is the same one a phone gets.

Every flex or grid child in that chain needs min-w-0; break-words alone does not stop it.
