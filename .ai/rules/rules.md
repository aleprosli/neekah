---
paths:
  - 'app/Rules/**'
---

# Rules

## Turnstile is optional and fails open
App\Rules\Turnstile guards login, both register forms and the booking form via the 'cf-turnstile-response' field, paired with resources/js/components/ui/UiTurnstile.vue (or <x-turnstile />, which mounts it). The widget must be rendered explicitly: Turnstile's automatic mode scans the document once as its script loads and never looks again, so a widget Vue adds afterwards — or one swapped in by navigation.js — is never drawn, produces no token, and makes the form refuse every submission. It is implicit ($implicit = true) so a missing token still fails. It returns early when TurnstileSettings::isEnabled() is false (off, or either key blank), and it lets the submission through, with a warning logged, when Cloudflare cannot be reached — a captcha outage must never become a registration outage. Keep both behaviours when touching it; the test suite runs with Turnstile off, so any new public form should stay usable without keys.
