---
paths:
  - 'app/Http/Controllers/Admin/**'
  - app/Http/Controllers/Admin/SettingController.php
---

# Admin

## Batch actions post ids[] and go through the same action as the single one
Approving vendors one at a time was a dialog and a page load each, so DataTable takes a `bulkActions` prop ([{key,label,tone,url,fields,confirm}], __COUNT__ in the wording becomes the number ticked) and rows must carry `id`. The confirmed form posts ids[] plus the action's fields.

POST admin/vendors/status (admin.vendors.bulk-status) validates ids (required, max 100, exists) and skips vendors already in that status, so nobody is emailed twice; both it and the single-vendor route call App\Actions\ChangeVendorStatus, which is what promotes New → Verified, rescores and notifies. Never re-implement a status change in a controller. Covered by Admin/VendorApprovalTest.

## Admin settings are one sub-page per group
Owner, 25 Sep 2026: Admin → Tetapan shows one page at a time at /admin/settings/{section} (admin.settings.edit, constrained to SettingController::SECTIONS, first is the default, anything else 404). SettingController::MENU groups the pages (Laman, Sistem, Wang); the Blade view draws it as a column on lg and a row of chips on a phone. A page may hold several forms: "pro" holds the Pro plan AND the Herepay switch, because Herepay is Neekah's own account taking Pro payments (vendor → Neekah). "bayaran" is booking payments (couple → vendor) and lists only PaymentMethod::isIntegrated() methods; booking gateways will be each vendor's own account, never a Neekah switch. Keep money split by who pays whom. Each form posts to its own route and back() returns to its page. A new page = a *Section() method, an entry in MENU and SECTIONS, and the $pages map in edit(). Covered by SiteSettingsTest.
