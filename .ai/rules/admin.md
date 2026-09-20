---
paths:
  - 'app/Http/Controllers/Admin/**'
---

# Admin

## Batch actions post ids[] and go through the same action as the single one
Approving vendors one at a time was a dialog and a page load each, so DataTable takes a `bulkActions` prop ([{key,label,tone,url,fields,confirm}], __COUNT__ in the wording becomes the number ticked) and rows must carry `id`. The confirmed form posts ids[] plus the action's fields.

POST admin/vendors/status (admin.vendors.bulk-status) validates ids (required, max 100, exists) and skips vendors already in that status, so nobody is emailed twice; both it and the single-vendor route call App\Actions\ChangeVendorStatus, which is what promotes New → Verified, rescores and notifies. Never re-implement a status change in a controller. Covered by Admin/VendorApprovalTest.
