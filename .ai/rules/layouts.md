---
paths:
  - 'resources/views/components/layouts/**'
---

# Layouts

## Three shells: site, dashboard (web app), auth — none borrows the others' furniture
layouts/dashboard is the web-app shell for all three roles: a full-height sidebar flush left (w-64, fixed), a thin top bar, and <main> beside them capped at 1440px. No site header, footer or phone bottom bar. Below lg the sidebar is a drawer driven by the #dashboard-drawer checkbox — no JavaScript — and the checkbox sits inside the single [data-nav-region], so every navigation.js swap closes it. Anything `fixed` inside a dashboard page must clear the sidebar with lg:left-64 (the checklist save bar does).

layouts/auth is for signing in and up: logo, decoration, no header/footer, page-shell "auth". x-auth-card (noindex) wraps it for login, register, password and phone pages. /login and /register ask pengantin or vendor first (AuthAudience, ?as=); the choice is wording and where "daftar" leads — never a gate, since the account decides which dashboard a sign-in lands on. Google is left off the vendor form because it signs newcomers up as couples. /vendor/register uses layouts/auth directly and stays indexable — it is also the page vendors find from Google.

Covered by PageSwapNavigationTest and Auth/AuthRoleChooserTest.
