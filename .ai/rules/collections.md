---
paths:
  - 'app/Collections/**'
---

# Collections

## Collections give a model's rows typed behaviour
A custom collection (`MatchCompetitorsCollection`) holds read-only helpers for a model's loaded rows, such as partitioning polymorphic competitors by type or grouping them by side. Attach it with `#[CollectedBy]` on the model, extend Eloquent's collection with a documented `@extends Collection<int, Model>`, and return typed base collections from helpers that change the item type.

## No queries, writes or eligibility decisions
A collection works on what is already loaded: it does not query, lazy load, write or decide whether something is allowed. Eligibility belongs to the lifecycle and booking classes (see actions-collections-lifecycle-models-rules.md). Create a collection only when callers repeat the same filtering or grouping of loaded rows.
- `EventBudgetLineCollection` (`unarchived()`, `archived()`, `inDirection()`) and `EventBudgetMovementCollection` (`unvoided()`, `refundsOf()`) replaced the hand-written "skip archived" and "skip voided" filters in the budget settlement service, the budget Actions and the budget ViewModel. They only filter and sum loaded rows: the refund cap, the excess check and the dependent-refund refusal stay in the Actions.
- Every method on a collection returns the same collection type and re-indexes (`->values()`), so calls chain (`refundsOf($payment)->unvoided()`) and callers can rely on list keys. `refundsOf()` keeps voided refunds on purpose; the caller chains `unvoided()` when it wants only those in force.
- Each new collection gets a test under `tests/Integration/Collections` that loads the rows through the relation (`with()`), so it also proves the model's `#[CollectedBy]` is wired and eager loading returns the typed class.
- Example: `TitleChampionshipCollection` answers the reign lookups `ChampionshipReignManager` repeated: `forTitle()` (active reigns only), `wonAt()`, `lostAt()`, `current()`, `laterThan()`, `heldBefore()` and `covering()`. `heldBefore()` and `covering()` keep their different date bounds on purpose; do not merge them. The not-soft-deleted check runs on every call and is never cached, because a shared collection can change while an Action works through it.
- `MatchCompetitorsCollection::bySidePosition()` and `competitorModels()` are the one place a match's entries are grouped by side position or mapped to their wrestlers and tag teams; callers use them instead of repeating `groupBy(side->position)` or `map(fn ($entry) => $entry->competitor)`. They only read the loaded `side` and `competitor` relations; load them before calling.
