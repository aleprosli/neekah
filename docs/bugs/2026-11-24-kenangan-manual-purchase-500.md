# Recording a manual Kenangan purchase no longer ends in a 500

| | |
| --- | --- |
| Type | Bug fix |
| Area | Neekah Kenangan |
| Roles | Admin |
| Branch | `fix/kenangan-manual-purchase-500` |
| Reported by | Owner |
| Date | 2026-11-24 |

## Symptom
Admin → Kamera (`/admin/kamera`), "rekod pembelian manual" form: submitting an email and tier
showed a 500 page instead of returning to the list with a success message.

## Root cause
`CameraController::store()` built the flash message from `$purchase->tier->label()`, but
`recordManually()` returns a `Payment`, which has no `tier` attribute (the tier lives in
`details['tier']`). `$purchase->tier` was `null`, so `->label()` threw. This ran **after**
`ActivateCameraAlbum::recordManually()` had already committed, so the payment was recorded as
paid, the album was opened and the couple was emailed — only the redirect failed.

The existing test only asserted `assertSessionHasNoErrors()`, which also passes on a 500, so
it never caught this.

## Fix
The controller keeps the `CameraTier` it parsed from the request and uses it for the flash
message.

| File | Change |
| --- | --- |
| `app/Http/Controllers/Admin/CameraController.php` | `store()` uses the validated `$tier` for the flash message instead of `$purchase->tier` |
| `tests/Feature/AdminCameraTest.php` | The manual purchase test now asserts the redirect back and the `flash.admin.camera_activated` message |

## Data repair
None required by the fix. Because each failed attempt still saved the purchase, an admin who
retried after the 500 may have created **duplicate** paid manual Kenangan payments (and extra
albums / emails) for the same wedding. Check production for `payments` rows with
`purpose = kenangan`, `gateway = manual`, created close together for the same `wedding_id`.

## Verification
- `php artisan test --compact tests/Feature/AdminCameraTest.php` — 6 passed.
- The updated test fails on the old controller (500 instead of 302) and passes with the fix.

## Watch out
`updateForUser()` (the switch on an account's page) already used its own `$tier`, so it was not affected.
