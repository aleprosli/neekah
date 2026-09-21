---
paths:
  - 'database/migrations/**'
---

# Migrations

## Schema::getTables() reaches every database on the connection
With no argument it returns tables from every schema the MySQL user can see, not just the current database: on a dev machine holding several projects that is the same table name once per schema. A data migration that iterated it updated each row once per appearance — 399 columns where the schema has 87, so a "+8 hours" shift moved everything +16.

Pass the database name: `Schema::getTables(DB::getDatabaseName())` on MySQL/Postgres, plain `Schema::getTables()` on SQLite, which has one schema and rejects the name. Key the result by "table.column" as well, so a duplicate can only ever be applied once.

Rehearse any data migration against a real dump before it touches production; the suite runs on SQLite, where a single schema hides this entirely.
