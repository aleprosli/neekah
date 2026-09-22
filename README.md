# Neekah

A wedding marketplace and planning platform for Malaysia — *Semua Urusan Majlis, Satu Platform*.

Couples find vendors, contact them directly and plan the wedding here (checklist, budget, timeline, guest list, digital invitation card). Vendors keep a public profile, a catalogue and a portfolio. Admins approve vendors, moderate reviews and run the categories.

Neekah is currently positioned as a **network**: couples deal and pay vendors directly, and Neekah takes no payment and no commission. The booking and payment-recording flows still exist in the code as an option, switched off behind `NEEKAH_BOOKINGS_ENABLED`. Public copy must never promise "tempah & bayar di Neekah" — see `.ai/rules/general.md`.

---

## Before you start: read the rules

`.ai/rules/` holds the settled decisions, the non-obvious traps and the standing constraints for this codebase. `.ai/rules/index.md` maps file globs to rule files.

**Read the rules that cover the files you are about to touch, before you touch them.** They are short, and most of them exist because something broke. This is not optional reading — several of them describe behaviour that looks wrong until you know why it is that way.

---

## Requirements

| | |
| --- | --- |
| PHP | 8.3+ (`composer.json` requires `^8.3`; production runs 8.5, local development is on 8.4) |
| Node | 22+ |
| Database | MySQL 8 in production. SQLite works locally and is what `.env.example` points at. |
| Extensions | `gd` (image processing), plus Laravel's usual set |

---

## Setting up

```bash
git clone https://github.com/aleprosli/neekah.git
cd neekah

composer run setup     # install, copy .env, generate key, migrate, npm install, build
php artisan migrate --seed
```

`composer run setup` does not seed. The seeders are worth running: they give you categories, the wedding checklist, the invitation card designs and a full set of demo data.

### Demo accounts

`DemoSeeder` creates these. **Every demo password is `password`.**

| Role | Email |
| --- | --- |
| Admin | `admin@neekah.test` |
| Couple | `aina@neekah.test` |
| Vendors | `<vendor-slug>@vendor.neekah.test` |

### Running it

```bash
composer run dev
```

That starts the server, the queue worker, the log tailer and Vite together.

Or separately:

```bash
php artisan serve
npm run dev
```

---

## The one local gotcha: invitation subdomains

Published wedding cards are served from a **subdomain** of `NEEKAH_SITE_DOMAIN`, for example `ainahakim.neekah.test`. `routes/web.php` declares them with `Route::domain('{subdomain}.'.config('neekah.site_domain'))`.

This means a plain `php artisan serve` on `127.0.0.1:8000` cannot reach a card. You need a host that resolves wildcard subdomains:

- **Laravel Herd / Valet** — `*.test` resolves automatically. Set `NEEKAH_SITE_DOMAIN=neekah.test` (the default in `.env.example`) and the cards work.
- **Anything else** — add the specific subdomains you need to `/etc/hosts`, or point `NEEKAH_SITE_DOMAIN` at a wildcard DNS service.

`NEEKAH_SITE_SCHEME` and `NEEKAH_SITE_PORT` are deliberately separate from `APP_URL`, so a published card never links to `:8000`.

---

## Environment

`.env.example` is commented where it matters. The ones worth understanding:

| Variable | Notes |
| --- | --- |
| `MEDIA_DISK` | `local` (storage/app/public) or `r2` (Cloudflare R2). Picks the driver behind the disk named `public`. Leave it `local` for development. |
| `R2_*` | Required only when `MEDIA_DISK=r2`. All five. `R2_URL` is the bucket's custom domain. |
| `NEEKAH_SITE_DOMAIN` | Host that invitation subdomains hang off. See above. |
| `NEEKAH_BOOKINGS_ENABLED` | Off. Turning it on brings the couple's booking flow back. |
| `CACHE_STORE` | `database` in production, and the public pages are tuned for that — every cache read is a round trip to MySQL. |
| `TURNSTILE_*` | Cloudflare Turnstile on the public forms. Blank in development means the challenge is skipped. |
| `GOOGLE_CLIENT_ID` / `SECRET` | Google sign-in through Socialite. Optional locally. |
| `TELEGRAM_BOT_TOKEN` / `CHAT_ID` | Admin notifications. Optional. |

---

## Tests

Pest, 700+ tests.

```bash
php artisan test --compact                       # everything
php artisan test --compact tests/Feature/VendorMarketplaceTest.php
vendor/bin/pest --filter=someTestName
```

Run the narrowest set that covers your change while working, then the whole suite before you push. Every change should come with a test — see `.ai/rules/` and the `testing-best-practices` skill.

---

## Frontend

Blade for the pages, **Vue 3 islands** for the interactive parts, Tailwind v4, Vite 8.

An island is mounted by `resources/js/vue.js` from a `data-vue` attribute and handed its props as JSON through the `@vueProps` Blade directive. There is no SPA router: `resources/js/navigation.js` intercepts same-origin clicks, fetches the page Laravel would have rendered and swaps `<main>` plus any `[data-nav-region]`. Read `.ai/rules/js.md` before touching it — the swap has a lot of deliberate bail-out conditions.

### `public/build` is committed

The built assets are **tracked in git**, because the production server does not run `npm run build` (building there dirties the tree and breaks the next pull).

So: **if you change anything under `resources/js` or `resources/css`, run `npm run build` and commit the result.** A change that is not built is a change that does not ship.

---

## Project commands

```bash
php artisan neekah:optimize-images              # re-encode images uploaded before optimisation existed
php artisan neekah:optimize-images --thumbnails # redraw every thumbnail at the current admin settings
php artisan neekah:robots                       # write public/robots.txt with this deployment's sitemap URL
php artisan neekah:mail-test you@example.com    # send a test email, report the sender actually used
```

Image sizes, quality and format are admin settings (Admin → Tetapan), not config. Changing the thumbnail width only affects the next upload until you run `--thumbnails`; see `.ai/rules/actions.md` for why that redraw renames files instead of overwriting them.

---

## Languages

Malay at the root, English under `/en`. Both are first-class — `lang/ms` and `lang/en` must stay in key parity, and a missing key falls back to Malay silently.

Invitation card pages and Malaysian state names stay in Malay on purpose.

`.ai/rules/lang.md` explains why grepping the source for untranslated strings does not work, and what to do instead.

---

## Deployment

Production is a single Ubuntu server behind Cloudflare, deployed by pulling `main`:

```bash
php artisan down --secret="..." --render="errors::503"
git fetch && git reset --hard origin/main && git clean -fd public/build
composer install --no-dev -o
php artisan migrate --force
php artisan neekah:robots
php artisan optimize
sudo supervisorctl restart "neekah-worker:*"
php artisan up
```

Two things that are **not** in this repo and must be restored by hand on a rebuild:

- The six security headers, including the Content-Security-Policy, live in nginx at `/etc/nginx/snippets/security-headers.conf`. `.ai/rules/general.md` records what the CSP has to allow and why.
- `.env`, including the R2 credentials.
