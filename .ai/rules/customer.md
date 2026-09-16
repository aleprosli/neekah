---
paths:
  - 'app/Http/Controllers/Customer/**'
---

# Customer

## Planning tools resolve the wedding through the `wedding` middleware
The customer planning pages (/checklist, /tetamu, /timeline, /budget, /kad, /kad/preview) each resolve the couple's wedding with `$request->user()->weddings()->latest('event_date')->firstOrFail()`. On its own that gives a signed-in couple who has not created a wedding yet a bare 404, which reads as a broken site — it shipped to neekah.my that way.

Any new page that resolves the wedding this way must carry `->middleware('wedding')` (App\Http\Middleware\EnsureUserHasWedding), which redirects to `weddings.create` with a status message. Keep the firstOrFail() as the safety net; the middleware is what the user actually sees. Covered by tests/Feature/Customer/WeddingRequiredTest.php — add the new route name to its dataset.
