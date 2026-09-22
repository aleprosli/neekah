---
paths:
  - 'resources/views/components/layouts/**'
---

# Layouts

## Three shells: site, dashboard (web app), auth — none borrows the others' furniture
layouts/dashboard is the web-app shell for all three roles: a full-height sidebar flush left (w-[17rem], fixed), a thin top bar carrying the page's context (the wedding and its countdown, the business and its status, or the admin panel and today), and <main> beside them capped at 1400px. It is dressed for weddings, not as a generic admin: ivory paper (--color-ivory) instead of grey, blush, gold section labels in the display serif, the brand's two rings. `nav` is a list of groups ({label, items}), so each role's menu reads as a few short ones; the account is the card at the foot of the sidebar, not a menu item. The sidebar background must stay opaque — a translucent gradient is invisible on desktop but lets the page show through the phone drawer. No site header, footer or phone bottom bar. Below lg the sidebar is a drawer driven by the #dashboard-drawer checkbox — no JavaScript — and the checkbox sits inside the single [data-nav-region], so every navigation.js swap closes it. Anything `fixed` inside a dashboard page must clear the sidebar with lg:left-[17rem] (the checklist save bar does).

layouts/auth is for signing in and up: logo, decoration, no header/footer, page-shell "auth". x-auth-card (noindex) wraps it for login, register, password and phone pages. /login and /register ask pengantin or vendor first (AuthAudience, ?as=); the choice is wording and where "daftar" leads — never a gate, since the account decides which dashboard a sign-in lands on. Google is left off the vendor form because it signs newcomers up as couples. /vendor/register uses layouts/auth directly and stays indexable — it is also the page vendors find from Google.

Covered by PageSwapNavigationTest and Auth/AuthRoleChooserTest.

## The preloader is for the first arrival in a tab only
An inline script in layouts/app.blade.php head reads sessionStorage "neekah:arrived" before the first paint and puts .nk-arrived on <html>; app.js sets the flag once a page is up. CSS then hides #nk-preloader:not(.nkc-card), so a visitor already in this tab never sees the curtain again, and never a flash of it. Running before paint is the whole point: doing this from app.js would show it and then snatch it away.

The invitation keeps its own curtain (.nkc-card) on every arrival. It carries the couple's names and is part of the card, not a loading state.

The CSP allows inline script ('unsafe-inline'), so no nonce is needed today; adding one means covering this tag as well as the ld+json in seo/tags.blade.php.
