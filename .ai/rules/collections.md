---
paths:
  - 'app/Collections/**'
---

# Collections

## Collections give a model's rows typed behaviour
A custom collection (`MatchCompetitorsCollection`) holds read-only helpers for a model's loaded rows, such as partitioning polymorphic competitors by type or grouping them by side. Attach it with `#[CollectedBy]` on the model, extend Eloquent's collection with a documented `@extends Collection<int, Model>`, and return typed base collections from helpers that change the item type.

## No queries, writes or eligibility decisions
A collection works on what is already loaded: it does not query, lazy load, write or decide whether something is allowed. Eligibility belongs to the lifecycle and booking classes (see actions-collections-lifecycle-models-rules.md). Create a collection only when callers repeat the same filtering or grouping of loaded rows.

- Example: `TitleChampionshipCollection` answers the reign lookups `ChampionshipReignManager` repeated: `forTitle()` (active reigns only), `wonAt()`, `lostAt()`, `current()`, `laterThan()`, `heldBefore()` and `covering()`. `heldBefore()` and `covering()` keep their different date bounds on purpose; do not merge them. The not-soft-deleted check runs on every call and is never cached, because a shared collection can change while an Action works through it.
