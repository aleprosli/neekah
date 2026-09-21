---
paths:
  - 'lang/**'
---

# Lang

## Find untranslated strings by rendering pages, not by grepping source
A regex over source for `'key' => 'Capital...'` misses four real categories, each of which shipped Malay to English pages: strings inside PHP arrays (InvitationSetup::steps, enum description()), units concatenated onto numbers ($days.' hari lagi'), lowercase or `{{ }}`-containing text in Blade, and ternaries picking between two literals.

The only reliable sweep: write a throwaway Pest test that GETs every /en/ route as guest/couple/vendor/admin, dumps the HTML, then strips <script>/<style>/tags and greps the visible text for Malay function words (yang, dan, untuk, anda, belum, boleh, supaya, sila...). Delete the test afterwards. Run it after every translation batch — three separate batches each looked finished and were not.

Card pages (/kad-jemputan previews, the wedding card itself) stay Malay by decision; state names are proper nouns. Both will show up in the sweep — ignore them.

Check key parity between lang/ms and lang/en after every batch; a guessed key silently falls back to Malay.
