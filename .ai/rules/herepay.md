---
paths:
  - 'app/Support/Herepay/**'
---

# Herepay

## Herepay is payment links only; the callback URL carries our signed reference
Owner, 25 Sep 2026: Neekah uses only Herepay's Create Payment Link API (POST {base}/api/integration/create-payment-link, header SecretKey), never the wider API. HerepayClient creates a usage_type=single link, redirects to data.pay_url, and sets redirect_url (vendor.pro.done?ref=) and callback_url per link. Herepay's callback body has no field of ours, so callback_url is URL::signedRoute('webhooks.herepay', ['ref' => ...], absolute: false) and parseCallback requires hasValidRelativeSignature() AND the body checksum (HMAC-SHA256 of body values sorted by key, comma-joined, with HEREPAY_PRIVATE_KEY). Read only the body for the checksum, never $request->all(), which would mix in our ref/signature. status_code 00 paid, 30 failed, anything else pending (left untouched). The webhook refuses a paid amount below the subscription amount. Keys live only in .env (HEREPAY_BASE_URL, HEREPAY_SECRET_KEY, HEREPAY_PRIVATE_KEY); the admin switch is HerepaySettings (Admin → Tetapan → Gateway bayaran) and saving it on is refused while a key is missing. isConfigured() = switched on AND keys present. Do not type-hint both PaymentLinkGateway and HerepayClient in one controller method: Laravel skips the second because an instance of it is already resolved. Covered by HerepayTest.
