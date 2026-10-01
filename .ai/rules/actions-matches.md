---
paths:
  - 'app/Actions/Matches/**'
---

# Actions Matches

## Apply championship lineage from match outcomes
RecordResultAction must apply match result metadata and all attached title outcomes in one transaction. Finishes that allow title changes transfer or establish each reign using the event date; DQ, countout, draws, and no-decisions retain the existing champion. Corrections may void reigns created by that match and restore the prior reign, but must reject rewriting lineage once a later reign depends on it.

## Lock order for scheduling
Lock order for scheduling: the complete same-date event set (ascending id, including the action's own event) is locked first, then the match, then competitors/resources in ascending id; never lock an event or match row before the event set.

## Lock date slots before rescheduling
Schedule changes that move an event to a date (including Events RestoreAction, for the event's own date) take the date-slot lock(s), ascending, before any event row lock; the slot lock is a transaction-scoped advisory lock on PostgreSQL and a no-op on SQLite.

## Record title results in date order
Title results are recorded in date order: a result that would create or change a reign earlier than the title's latest recorded reign is rejected with a domain exception, never written.
