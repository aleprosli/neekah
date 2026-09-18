---
paths:
  - 'app/Http/Controllers/Customer/**'
  - app/Http/Controllers/Customer/WeddingTaskController.php
---

# Customer

## Planning tools resolve the wedding through the `wedding` middleware
The customer planning pages (/checklist, /tetamu, /timeline, /budget, /kad, /kad/preview) each resolve the couple's wedding with `$request->user()->weddings()->latest('event_date')->firstOrFail()`. On its own that gives a signed-in couple who has not created a wedding yet a bare 404, which reads as a broken site — it shipped to neekah.my that way.

Any new page that resolves the wedding this way must carry `->middleware('wedding')` (App\Http\Middleware\EnsureUserHasWedding), which redirects to `weddings.create` with a status message. Keep the firstOrFail() as the safety net; the middleware is what the user actually sees. Covered by tests/Feature/Customer/WeddingRequiredTest.php — add the new route name to its dataset.

## The checklist saves a handful of ticks at once, and counts progress in the browser
PUT /weddings/{wedding}/tasks (weddings.tasks.update) takes done[] and undone[] — whole lists, not one task. There is deliberately no per-task tick route: someone sitting with their folder ticks eight documents in a row, and a post per tick was eight page reloads that each scrolled them back to the top and folded the phase they were in. CustomerChecklistPage.vue holds the ticks locally and shows a save bar; an id belonging to another wedding is dropped by the ->tasks() scope, never trusted from the request.

The controller sends no stats or progress props. The percentages, the phase counters and the three stat cards are all computed in the component from `sections`, so they move as the couple ticks instead of lying until the save lands. Do not add them back server-side.
