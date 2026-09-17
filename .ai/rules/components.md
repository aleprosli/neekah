---
paths:
  - 'resources/js/components/**'
---

# Components

## Detail-page grids need min-w-0, break-words alone does not stop overflow
A grid or flex child defaults to min-width:auto, so one long unbroken word (an email, a package name, a location) widens it past the screen and a phone browser zooms the whole page out. `break-words` does not help on its own. Give the two-column detail layout `lg:grid-cols-[minmax(0,1fr)_320px]`, put `min-w-0` on each child and `break-words` on the grid (see VendorBookingDetail.vue). To check, replace a field's text with a 50-character email at 360px and confirm document.documentElement.scrollWidth stays 360.
