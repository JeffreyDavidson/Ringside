---
paths:
  - 'app/Actions/Matches/**'
---

# Actions Matches

## Apply championship lineage from match outcomes
RecordResultAction must apply match result metadata and all attached title outcomes in one transaction. Finishes that allow title changes transfer or establish each reign using the event date; DQ, countout, draws, and no-decisions retain the existing champion. Corrections may void reigns created by that match and restore the prior reign only while the title is still active and the prior champion is still employed (otherwise the title stays vacant), and must reject rewriting lineage once a later reign depends on it. Results are rejected for events that have not happened, and title changes re-check that the title and winner still exist, plus current title activity and winner booking eligibility unless the event is from an earlier local day in its promotion's time zone (back-fill).

## Lock order for scheduling
Lock order for scheduling: the complete same-date event set (ascending id, including the action's own event) is locked first, then the match, then competitors/resources in ascending id; never lock an event or match row before the event set.

## Lock date slots before rescheduling
Schedule changes that move an event to a date (including Events RestoreAction, for the event's own date) take the date-slot lock(s), ascending, before any event row lock; the slot lock is an upsert of the slot's row in scheduling_slot_locks, which holds that row's lock until the transaction ends on MySQL, PostgreSQL and SQLite alike.

## Record title results in date order
Title results are recorded in date order: a result that would create or change a reign earlier than the title's latest recorded reign, or inside the closed interval of an earlier vacated reign, is rejected with a domain exception, never written. A correction that leaves the champion unchanged is not rejected.
