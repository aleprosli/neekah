---
paths:
  - database/seeders/ChecklistSeeder.php
---

# Seeders

## The master checklist stays in its eight phases, and an item's Malay title is its identity
The checklist is filed by phase (time order), not by category — the owner tried categories on 23 Sep 2026 and went back. Items from the engagement, hantaran, bertandang and final-week lists live inside the phases as groups (e.g. "Pertunangan" in Perancangan Awal, the hantaran groups in Persediaan Pengantin). Keep every Malay title unique across the list and never rename an existing one here: the seeder and SeedWeddingChecklist's adoption both match by it, so a rename gives every couple a second copy. Run anything that copies item titles onto tasks under the ms locale — a tinker run with APP_LOCALE=en creates English-titled duplicates.
