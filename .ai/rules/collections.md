---
paths:
  - 'app/Collections/**'
---

# Collections

## Collections give a model's rows typed behaviour
A custom collection (`MatchCompetitorsCollection`) holds read-only helpers for a model's loaded rows, such as partitioning polymorphic competitors by type or grouping them by side. Attach it with `#[CollectedBy]` on the model, extend Eloquent's collection with a documented `@extends Collection<int, Model>`, and return typed base collections from helpers that change the item type.

## No queries, writes or eligibility decisions
A collection works on what is already loaded: it does not query, lazy load, write or decide whether something is allowed. Eligibility belongs to the lifecycle and booking classes (see actions-collections-lifecycle-models-rules.md). Create a collection only when callers repeat the same filtering or grouping of loaded rows.
