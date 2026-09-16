---
paths:
  - 'app/Jobs/**'
---

# Jobs

## Admin Telegram alerts go through SendTelegramAlert::about()
New signups tell the admin Telegram chat through SendTelegramAlert::about($headline, $rows), called from RegisterVendor, Auth\RegisterController and Auth\GoogleController. about() checks TelegramSettings::isEnabled() and simply does nothing when Telegram is not configured, so nothing needs to guard the call site. The job is queued and logs a warning instead of throwing when Telegram refuses — a signup must never fail because of an alert. Rows are escaped; only the headline may carry HTML, and it is always our own literal string.
