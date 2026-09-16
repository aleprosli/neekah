---
paths:
  - 'app/Rules/**'
---

# Rules

## Turnstile is optional and fails open
App\Rules\Turnstile guards login, both register forms and the booking form via the 'cf-turnstile-response' field, paired with <x-turnstile /> in the view. It is implicit ($implicit = true) so a missing token still fails. It returns early when TurnstileSettings::isEnabled() is false (off, or either key blank), and it lets the submission through, with a warning logged, when Cloudflare cannot be reached — a captcha outage must never become a registration outage. Keep both behaviours when touching it; the test suite runs with Turnstile off, so any new public form should stay usable without keys.
