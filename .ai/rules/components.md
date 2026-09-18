---
paths:
  - 'resources/js/components/**'
---

# Components

## Detail-page grids need min-w-0, break-words alone does not stop overflow
A grid or flex child defaults to min-width:auto, so one long unbroken word (an email, a package name, a location) widens it past the screen and a phone browser zooms the whole page out. `break-words` does not help on its own. Give the two-column detail layout `lg:grid-cols-[minmax(0,1fr)_320px]`, put `min-w-0` on each child and `break-words` on the grid (see VendorBookingDetail.vue). To check, replace a field's text with a 50-character email at 360px and confirm document.documentElement.scrollWidth stays 360.

## A row action that changes something for someone else asks first
Approving or suspending a vendor emails them and moves their profile on or off the marketplace, so none of it fires on one tap. DataTable's `rowAction: {inline: true}` renders a UiConfirm, not a bare form: the server supplies `action.confirm` ({title, message, confirmLabel, tone}) per row, and UiConfirm takes a `fields` prop for the hidden inputs (status, etc.) that the confirmed form posts. Same for AdminVendorDetail's status buttons (statusActions carry confirm_title/confirm_message/tone) and the dashboard's "Lulus".

Name the thing in the question — "Luluskan Studio Baharu?", not "Anda pasti?" — and say what follows in the message. Covered by Admin/VendorApprovalTest.

The exceptions are ordinary forms someone fills in and submits, and reversible one-tap things like reopening a blocked date; those do not need a dialog.
