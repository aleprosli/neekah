---
name: neekah-change-docs
description: Write or update the change doc for every Neekah code change. Bug fixes go in docs/bugs, new features in docs/features, improvements to existing behaviour in docs/enhancements. Use after finishing (or while finishing) any fix, feature or enhancement in this repo, when the user asks to "document", "write up", "simpan dalam docs", or before #OpenPR if the branch has no doc yet. Not for docs/api (partner API reference), docs/ARCHITECTURE.md or docs/FEATURES.md, which are maintained by hand.
---

# Neekah change docs

Every change to Neekah leaves a short doc behind, so the next developer can find out
**what** changed, **why**, **where** the code lives and **what to watch out for** without
reading the whole diff or asking around. Code and docs are in English. Quote Malay UI copy
exactly as it appears on screen.

## 1. Pick the folder

| Folder | Use when | File name |
| --- | --- | --- |
| `docs/bugs/` | Something was broken and is now fixed (500 page, wrong price, broken upload, wrong vendor shown, data error). Production hotfixes always go here. | `YYYY-MM-DD-<slug>.md` (one file per fix) |
| `docs/features/` | A new capability that did not exist before (new page, new module, new payment purpose, new integration, new role screen). | `<feature-slug>.md` (one **living** file per feature; update it on later changes) |
| `docs/enhancements/` | An existing feature works better or shows more (new column, filter, UX/design change, mobile layout, performance, copy, reordering). | `YYYY-MM-DD-<slug>.md` (one file per change) |

Rules of thumb:

- The branch prefix is a hint, not the rule: `hotfix/*` or `fix/*` → bugs, `feature/*` → features,
  `enhancement/*` / `enhance/*` → enhancements. Classify by what the change actually does.
- A branch with several unrelated changes gets one doc per change (or one enhancement doc
  with a section per change when they ship together on the same pages).
- A change to a feature that already has a `docs/features/<slug>.md`: write the
  bug/enhancement doc **and** update the feature doc so it stays the current truth.
- Date = the day the doc is written, in `Asia/Kuala_Lumpur`.
- Slug: lowercase kebab-case, describing the outcome (`about-dropdown-feature-pages`,
  `kenangan-upload-blocked-by-csp`), not the ticket or the branch.
- A settled decision or non-obvious trap ALSO goes into `.ai/rules` through `record-rule`.
  The change doc tells the story of one change; the rule is what every later change must obey.

## 2. Gather the facts first

Write only what the code and the user's request support. Before writing:

1. `git branch --show-current`, `git log --oneline origin/main..HEAD`,
   `git diff origin/main...HEAD --stat`, plus `git status` for uncommitted work.
2. Read the changed controllers, Blade views, Vue islands, migrations, jobs, settings
   groups (`app/Support/*Settings.php`), routes and lang keys (`lang/ms`, `lang/en`).
3. Check `docs/features/`, `docs/enhancements/` and `docs/bugs/` for an existing doc on the same
   area, and link to it (or update it) instead of duplicating.

Never invent verification. If nothing was tested, write "Not run" in the Verification section.

## 3. Write the doc

Use the matching template below. Keep it short and scannable: tables for files, bullets for
rules, no release-note prose. Delete sections that would be empty. Link files as repo-relative
paths in backticks (`app/Http/Controllers/FeaturePageController.php`), and mention methods,
props, route names and lang keys by name.

`Area` is one of: Public site, Marketplace, Vendor profile, Couple dashboard, Vendor dashboard,
Admin, Kad jemputan, Neekah Kenangan, Neekah Pro, Boost, Payments (Herepay), Partner API,
Notifications, SEO, Infrastructure.

### Bug template (`docs/bugs/YYYY-MM-DD-<slug>.md`)

```markdown
# <Short outcome title>

| | |
| --- | --- |
| Type | Bug fix |
| Area | <Area> |
| Roles | <Guest / Couple / Vendor / Admin — who was affected> |
| Branch | `<branch>` |
| Reported by | <who, if known> |
| Date | YYYY-MM-DD |

## Symptom
What users/admins saw. A concrete example (URL, vendor, amount) if known.

## Root cause
Why it happened, pointing at the code that was wrong.

## Fix
What changed, and why this fixes it.

| File | Change |
| --- | --- |
| `path/to/File.php` | ... |

## Data repair
Commands or queries run, or still needed, on production (or "None").

## Verification
What was actually checked, or "Not run".

## Watch out
Edge cases, follow-ups, things that can regress.
```

### Feature template (`docs/features/<feature-slug>.md`)

```markdown
# <Feature name>

One or two sentences: what it does and who it is for.

| | |
| --- | --- |
| Type | Feature |
| Area | <Area> |
| Roles | <Guest / Couple / Vendor / Admin> |
| Branch | `<branch>` (first shipped) |
| Last updated | YYYY-MM-DD |

## How it works
The flow from the user's point of view, then the system's.

## Routes
| Route name | URL | What |
| --- | --- | --- |

## Files
| File | What |
| --- | --- |

## Data
Tables, columns, migrations, enums and their values, settings keys (Admin → Tetapan), env vars.

## Rules
Business rules and limits, each as a bullet (what is free, what is Pro, who sees what).

## Deploy notes
Migrations, `npm run build` (public/build is committed), `config:cache` / `route:cache`, queues, schedules, settings to switch on.

## Known limits
What it deliberately does not do yet.

## History
- YYYY-MM-DD — <change> (`<branch>`, link to the bug/enhancement doc)
```

### Enhancement template (`docs/enhancements/YYYY-MM-DD-<slug>.md`)

```markdown
# <Short outcome title>

| | |
| --- | --- |
| Type | Enhancement |
| Area | <Area> |
| Roles | <Guest / Couple / Vendor / Admin> |
| Branch | `<branch>` |
| Requested by | <who, if known> |
| Date | YYYY-MM-DD |

## Request
What was asked for, in plain words (keep the user's wording when it helps).

## Before → After
| | Before | After |
| --- | --- | --- |

## Changes
| File | Change |
| --- | --- |

## Rules
How values are computed, and what counts as what.

## Deploy notes
Migrations, `npm run build`, cache, config (or "None").

## Verification
What was actually checked, or "Not run".

## Watch out
Performance, edge cases, follow-ups.
```

## 4. Update the index

Each folder has a `README.md` with a table, newest first. Add (or update) one row:

```markdown
| Date | Doc | Summary | Branch |
| --- | --- | --- | --- |
| 2026-11-16 | [Title](2026-11-16-slug.md) | One line | `branch` |
```

The feature index uses `| Feature | Doc | Summary | Last updated |`. Create the folder and its
README from these headers if it does not exist yet.

## 5. Finish

- Tell the user which doc(s) were created or updated (clickable paths).
- The doc ships in the same branch and PR as the code. Do not commit unless the user asks
  (`#OpenPR` counts as asking).
- When the branch merges, the doc is already there, and `#OpenPR` can quote it.
