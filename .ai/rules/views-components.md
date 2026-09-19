---
paths:
  - resources/views/components/analytics.blade.php
---

# Views Components

## A new third-party script needs the nginx CSP opened first
Production sends a Content-Security-Policy from /etc/nginx/snippets/security-headers.conf on the server (see the rule in general.md). A script tag added to a layout is blocked by the browser, silently, until that host is in script-src — and its beacons until connect-src allows them too. Add the host there and reload nginx in the same deploy, then confirm in the browser console, not just by looking at the markup.

Google Analytics needs script-src https://www.googletagmanager.com and connect-src for googletagmanager plus *.google-analytics.com, *.analytics.google.com and stats.g.doubleclick.net.

GOOGLE_ANALYTICS_ID (config services.google_analytics.measurement_id) is the only setting; an empty or malformed value renders nothing, because the id is printed inside a <script> where Blade escaping would not make a stray value safe. Config is cached in production, so a deploy that touches config/ must rebuild it.

navigation.js swaps pages without a page load, so it dispatches window 'neekah:navigated' after the history entry and the analytics snippet counts a page_view from it. Anything else that counts visits listens to that event too — gtag alone would only ever see the first page of a visit.
