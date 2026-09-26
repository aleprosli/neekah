---
paths:
  - 'app/Support/Herepay/**'
  - 'app/Support/Payments/**'
  - 'app/Actions/SettlePayment.php'
  - 'app/Actions/StartPayment.php'
  - 'app/Actions/RequeryPayment.php'
  - 'app/Http/Controllers/Payments/**'
  - 'app/Models/Payment.php'
  - 'app/Models/PaymentEvent.php'
---

# Payments and Herepay

## One ledger: every payment is a row of `payments`, never a table of its own
Owner, 26 Sep 2026: booking payments, Neekah Pro, boost packs and Neekah Kenangan used to live in four tables (payments, vendor_subscriptions, boost_purchases, camera_purchases) with four webhooks; migration unify_payments_into_one_ledger folded them into `payments`. A row says what it is for (`purpose`: booking, vendor_pro, boost_tokens, kenangan — App\Enums\PaymentPurpose), whose account took the money (`merchant`: neekah for Pro/boost/Kenangan, vendor for a booking; set from the purpose on create) and through what (`gateway`: herepay, manual, and any gateway added later). What it paid for is an explicit nullable FK (booking_id, vendor_id, wedding_id, camera_album_id), not a morph pair: the DB keeps integrity and cascades, and a ledger row outlives what it bought. Purpose-only facts sit in `details` (plan and Pro period, pack and tokens, tier/kind/album_title/album_event_date). Gateway facts are columns: gateway_reference (Herepay payment_code), gateway_invoice (reference_code), gateway_transaction_id, gateway_status, method (FPX…). A new product that takes money is a new PaymentPurpose case and a fulfil() — never another table or webhook. Admin → Kewangan (Admin\PaymentController) lists them all; the old admin/transactions redirects there.

## Every exchange with a gateway is kept in payment_events
payment_events is append-only: link_created/link_failed (what we asked and what came back), callback (the body exactly as posted, even unverified or for an unknown ref — payment_id null), return (the payer's query string), requery (the gateway's answer), manual_recorded/manual_verified/manual_rejected, invoice_attached — each with verified, outcome, http_status, meta (ip, error) and user_id. Payloads over 20 KB are truncated. Never store keys. The admin payment page renders this as the gateway log; it is how a disputed payment is investigated.

## Three ways to settle, one SettlePayment
A payment is settled by (1) the gateway callback, (2) the payer's return, which Herepay also checksums, or (3) a requery — all through SettlePayment::apply(), and admins through markPaid(). markPaid locks the row, returns false if it was already paid (Herepay retries; the return may also arrive), marks it paid and calls the purpose's fulfil() inside the same transaction (ActivateVendorPro, ActivateBoostPurchase, ActivateCameraAlbum, ConfirmOnlineDeposit); fulfillers notify with DB::afterCommit. A paid amount below the payment's is refused (amount_mismatch, 422, reported). References are kept even while the gateway says pending, so a requery has an invoice to ask by. Only SettlePayment marks a payment paid — except VerifyManualPayment, the vendor confirming a couple's bank transfer.

## Gateways are classes behind App\Support\Payments\PaymentGateway
PaymentGateways::DRIVERS maps the name stored on payments.gateway to a class (herepay → HerepayGateway). StartPayment asks the payment's gateway for a link with callback = signed route payments.webhook (POST /webhooks/{gateway}?ref=…) and return = payments.return (GET /bayaran/{reference}/kembali, NOT signed: the gateway appends its own query, which would break our signature — its checksum is the proof). The webhook checks hasValidRelativeSignature() FIRST, then loads the payment by the signed ref, then verifies the body with that payment's merchant's keys (a vendor's for a deposit — Neekah's key must fail — Neekah's otherwise). The old /webhooks/herepay/{kamera,boost,tempahan} addresses still route to the same controller for links made before 26 Sep 2026.

## Herepay specifics
Links: POST {base}/api/integration/create-payment-link, header SecretKey, usage_type single. Checksum (callback body and return query alike, minus our ref/signature/cuba): every field but checksum, ksort, values comma-joined (arrays JSON), HMAC-SHA256 with the account's private key. status_code 00 paid, 30 failed, else pending; a transaction lookup has answered "1"/"Completed" for paid, so words count too (HerepayTransport::statusOf). Requery: GET {base}/api/v1/herepay/transactions/{code} with SecretKey AND XApiKey — so it needs HEREPAY_API_KEY (Neekah) or the vendor's optional herepay_api_key. Its docs say {code} is the reference_code (HP-INV-…), but on 26 Sep 2026 UAT answered 404 "No transactions found for payment code" for that and found the payment by its payment_code (HP-PAY-…), so HerepayGateway::lookupCode() asks by gateway_reference first and gateway_invoice only as a fallback. The code comes from a callback/return, or an admin pastes it from the Herepay dashboard. neekah:requery-payments (every 10 min) asks about pending online payments with a code (10 min–3 days old) and marks links expired an hour past expires_at. Keys live only in .env (HEREPAY_BASE_URL, HEREPAY_SECRET_KEY, HEREPAY_PRIVATE_KEY, HEREPAY_API_KEY); HerepaySettings is the admin switch, refused while one of the first three is missing. A vendor's first verified callback or return stamps herepay_verified_at. Covered by HerepayTest, OnlineDepositHerepayTest, AdminPaymentsTest.
