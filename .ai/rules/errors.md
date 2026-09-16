---
paths:
  - 'resources/views/errors/**'
---

# Errors

## Error pages must render without the database
Every errors/*.blade.php renders <x-errors.layout>, which is standalone HTML: no x-layouts.app, no site header or footer, no ContactSettings, no auth() lookup. An error page has to survive the failure that caused it, and the ordinary layout queries settings and the session user. ErrorPagesTest fails the suite if a query runs while a 404 renders. Assets come from @vite/@fonts (files, not queries), the logo from config('neekah.brand.lockup').
