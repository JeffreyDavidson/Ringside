---
paths:
  - 'database/migrations/**'
---

# Migrations

## Store enums in string columns
Store PHP enum values in string columns rather than database enum columns.

## Model-backed foreign keys
Define application model-backed foreign keys with foreignIdFor(Model::class), supplying a custom column name when necessary. Use foreignId() only when no application model represents the referenced table.

## Forward-only migrations
Write forward-only migrations with an up() method and no down() method. Correct deployed schema changes with a new migration.
