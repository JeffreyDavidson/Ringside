---
paths:
  - 'database/migrations/**'
---

# Migrations

## Store enums in string columns
Store PHP enum values in string columns rather than database enum columns.

## Model-backed foreign keys
Define application model-backed foreign keys with foreignIdFor(Model::class), supplying a custom column name when necessary. Use foreignId() only when no application model represents the referenced table.

## Index foreign keys, history sides and tenant columns
Index foreign key and pivot columns that queries filter on and no existing index leads with. The partial unique indexes that enforce one open period only serve current rows, so history queries need their own index. Check existing indexes (including partial ones that already lead with the column) before adding one, and use plain Schema::table indexes: they do not rebuild SQLite tables.

## Forward-only migrations
Write forward-only migrations with an up() method and no down() method. Correct deployed schema changes with a new migration.

## Enforce exclusive open periods in the database
For lifecycle history where an owner may have only one open period, enforce that invariant with a database unique index in addition to Action-level locking. Use a filtered unique index on the owner key where ended_at is null; for MySQL or MariaDB, use a generated nullable owner key with a unique index.
