---
paths:
  - resources/js/components/ui/DataTable.vue
  - resources/js/components/ui/UiFlagSelect.vue
  - 'resources/js/components/ui/**'
---

# Ui

## One table component, cards on a phone
Every table in the application renders through components/ui/DataTable.vue. Give it `dataUrl` for a server-paged list (controller `data()` returning {data, meta}; searching, sorting and paging in the query) or `rows` for a list already in hand. Columns are [{key, label, sortable?, sort?, align?, type?}]; a page that needs custom markup uses the `#cell-<key>` or `#action` scoped slots rather than writing its own table. Below md it renders cards, not a scrolling table: the first column is the card title, a trailing html column is the badge, the rest become label/value rows, and sorting moves to a select. ResponsiveTablesTest fails the suite if a second <table> appears anywhere under resources, or if any table sits outside something that scrolls.

## Check mobile width with a long business name, not with demo data
Demo names are short, so a page can look fine at 360px and still blow out in production. Two real cases, both now fixed: the admin dashboard's "Menunggu kelulusan" grid (a grid child without min-w-0 widened the whole page to 731px at a 360px viewport) and DataTable's mobile card (the ul/li/a/dl chain, plus the mobile sort select, which is as wide as its longest option unless it carries w-full).

To check: set a vendor name to something like "Studio Fotografi Dan Videografi Perkahwinan Nur Aisyah Enterprise Sdn Bhd", load the page, and confirm document.body.scrollWidth equals the viewport. Chrome on macOS will not open a window under 500px, so narrow document.documentElement instead — no Tailwind breakpoint sits between 390 and 500, so the layout is the same one a phone gets.

Every flex or grid child in that chain needs min-w-0; break-words alone does not stop it.

## Table filters are a DataTable prop; dataUrl must carry no query
DataTable adds page/per_page/search/sort to dataUrl itself. Passing a url that already had a query (route('admin.vendors.data', ['status' => ...])) produced "...?status=pending?page=2", so the server read status="pending?page=2": the filter silently did nothing and paging stuck on page one. Every admin/vendor list had it.

Filters are the `filters` prop — [{key, label, value, exclusive?, multiple?, hint?, options: [{value, label, count?, description?}]}], built with App\Support\TableFilter::fromEnum() — rendered since 26 Sep 2026 as one toolbar of UiFacetedFilter buttons (Reka UI Popover + Listbox: a checklist with counts and search), not rows of chips, so ten filters fit on one line (owner: the chip rows stacked a line per filter and took one value each). A group takes SEVERAL values by default: the table sends key=a,b, and every data() endpoint must read it with TableFilter::requested()/requestedEnums() and apply whereIn (or OR the enum scopes) — values within a group widen, groups narrow each other. `multiple: false` keeps a group single (users' segment views, which swap columns); `exclusive` still clears the other groups. Chosen values show as removable chips; the choice is written back to the address bar with replaceState. Columns can be hidden (UiColumnToggle, remembered per list in localStorage) and rows per page chosen. The whole table — toolbar, chips, rows, pager — is one white card (bg-surface-raised, rounded-3xl, overflow-clip so the bulk-action bar can still stick); pages must not wrap a DataTable in another bordered card (owner, 26 Sep 2026: the table used to sit transparent on the ivory page). A data() response may also return `columns`, which wins over the prop; that is how the segment views change columns without a page load. ResponsiveTablesTest fails on any dataUrl built with route parameters.

## Negeri pickers are a teleported listbox over a real select, not a styled select
A native select can only hold text, so every negeri picker is UiFlagSelect: a button, a listbox and a hidden input carrying the value. Blade mounts it as an island over an ordinary select, which is what a visitor without JavaScript keeps — leave that select in place (SelectStylingTest still demands nk-select and a pr-* on it).

The list is teleported and positioned with getBoundingClientRect, because the marketplace hero is overflow-hidden and would cut an absolutely positioned menu off. Inside a dialog it teleports into that dialog, not the body: a modal dialog sits in the browser's top layer and would paint over anything left behind. Outside-click closing must test the teleported menu as well as the root, or picking an option closes the list before the click lands.

The negeri list is static data in config/states.php ({name, slug}), read only through App\Support\States — never config('states') directly, and there is no Vendor::STATES any more. Validation uses Rule::in(States::names()); dropdowns get States::options() ({value, label, flag}), which controllers pass as `states` to the Vue forms. The 16 SVGs live in public/img/flag named after each entry's slug, which is written in the config rather than derived so renaming a negeri cannot silently break its flag. StatesTest fails if one is missing.

Picking several of something (categories, negeri covered) uses UiMultiSelect, not a wrap of checkboxes: thirteen or sixteen chips run off the bottom of a phone. What is picked shows as removable chips above the field, each row in the list says "Dipilih", and a `locked` option (the primary category, the home state) posts a value nobody can take off. Both it and UiFlagSelect share the popup placement in composables/useAnchoredMenu.js.

## Every label starts with a capital; fields.* keys are lowercase on purpose
Owner, 25 Sep 2026: every visible label must start with a capital letter. lang/*/fields.php is lowercase because it names fields inside validation sentences ("Medan nama perniagaan diperlukan"); when a fields.* key is reused as a label, wrap it in Str::ucfirst() / ucfirst(). As a safety net the label element of every shared form component (UiField, UiSelect, UiTextarea, UiFlagSelect, UiMultiSelect, UiPhoneField, x-form.field, x-form.select, AdminSettingsPage checkbox) carries first-letter:uppercase — it needs a block-level box, so the span must be a flex item or `block`. No UI text is written inline in PHP, Blade or Vue: it goes through lang (server) or lang/*/ui.php via $t (Vue), in both languages. SiteSettingsTest checks settings labels.
