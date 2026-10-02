---
paths:
  - 'resources/js/components/vendor/**'
---

# Components Vendor

## Document pages are 8/4: sheet left, panel right, like the calendar page
Owner, 2 Oct 2026: "8w untuk invoice, 4w untuk panel, same row, refer /vendor/availability". Quotation and contract show pages and both forms use `lg:grid-cols-12`: the sheet (fluid, x-*-sheet fluid / the form's article) is lg:col-span-8, the panel lg:col-span-4 lg:sticky lg:top-24. The forms' panel holds the choices (save & send / save draft / cancel, add package or add-on, discount & deposit with RM/% toggles feeding hidden inputs, the attached quotation, the template checkbox); the sheet holds only what prints. Below lg the show-page panel comes first (single column, history folded) and the form panel follows the sheet with a sticky save bar (lg:hidden). Every one of these pages has a back link through the layouts' `back` prop (['url','label']). Don't reintroduce a 2-column card grid of actions or cap the sheet at 210mm on vendor pages.
