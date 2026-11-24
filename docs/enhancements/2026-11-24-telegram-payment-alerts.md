# Admin Telegram chat hears about every payment

| | |
| --- | --- |
| Type | Enhancement |
| Area | Notifications |
| Roles | Admin |
| Branch | `feature/telegram-payment-alerts` |
| Requested by | Owner |
| Date | 2026-11-24 |

## Request
"Kalau ada org beli album, langgan Neekah Pro, atau yang lain lain berkenaan pembayaran, masuk dalam Telegram."

## Before → After
| | Before | After |
| --- | --- | --- |
| Admin Telegram chat | New signups, reported Kenangan files and Kenangan fair-use only | Also one message for every payment that becomes paid |
| Covers | — | Neekah Kenangan (new album or upgrade), Neekah Pro, boost tokens, booking payments (online deposit via the vendor's Herepay, or a bank transfer the vendor confirms) |

The message reads, for example:

```
NEEKAH PRODUCTION

💰 Bayaran diterima: Neekah Kenangan
Jumlah: RM29.00
Item: <receipt line>
Butiran: <album title · event date>
Pembayar: <name · email · phone>
Kaedah: Herepay · FPX
Resit: RS-2026-000123
Pautan: https://neekah.my/admin/kewangan/<payment>
```

Booking payments add `Vendor: <business name>`.

## Changes
| File | Change |
| --- | --- |
| `app/Actions/AlertPaymentReceived.php` | New. Builds the alert from `PaymentDocument` (`items()`, `billTo()`), always in Malay, and dispatches it through `SendTelegramAlert::about()`. |
| `app/Actions/SettlePayment.php` | `markPaid()` alerts after commit, only when it was the call that marked the payment paid. Covers callback, return, requery, admin "Tandakan dibayar" and admin-recorded Pro/Kenangan. |
| `app/Actions/VerifyManualPayment.php` | `handle()` alerts after the vendor confirms a couple's bank transfer. |
| `tests/Feature/PaymentAlertsTest.php` | New: Kenangan alerts once however often it is settled, Pro, verified bank transfer. |

## Rules
- One alert per payment: `markPaid()` returns early (no alert) when the payment was already paid, so Herepay retrying its callback or the payer also returning never alerts twice.
- Failed, pending and amount-mismatch payments do not alert.
- Uses the existing Telegram chat (Admin → Tetapan → Telegram). Nothing is sent while Telegram is switched off or not configured.

## Deploy notes
None beyond a normal deploy; the job is queued, so the queue worker must be running (it already is for signup alerts).

## Verification
- `tests/Feature/PaymentAlertsTest.php`: 3 passed.
- Related suites (PaymentReceipt, Herepay, ManualPayment, OnlineDepositHerepay, CameraPurchase, BoostPurchase, SignupAlerts, AdminCamera, AdminPayments, ProElite, VendorBoost): 105 passed.
- Not checked against a real Telegram chat.

## Watch out
- A payment marked paid outside `SettlePayment::markPaid()` / `VerifyManualPayment` would not alert; per `.ai/rules/herepay.md` there are no other paths.
