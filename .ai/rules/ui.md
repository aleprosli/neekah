---
paths:
  - resources/js/components/ui/DataTable.vue
---

# Ui

## One table component, cards on a phone
Every table in the application renders through components/ui/DataTable.vue. Give it `dataUrl` for a server-paged list (controller `data()` returning {data, meta}; searching, sorting and paging in the query) or `rows` for a list already in hand. Columns are [{key, label, sortable?, sort?, align?, type?}]; a page that needs custom markup uses the `#cell-<key>` or `#action` scoped slots rather than writing its own table. Below md it renders cards, not a scrolling table: the first column is the card title, a trailing html column is the badge, the rest become label/value rows, and sorting moves to a select. ResponsiveTablesTest fails the suite if a second <table> appears anywhere under resources, or if any table sits outside something that scrolls.
