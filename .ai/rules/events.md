---
paths:
  - 'app/{Actions,Exceptions,Lifecycle,Rules,Livewire}/Events/**'
---

# Events

## Keep occurred event dates immutable
Once an event has occurred, its persisted date cannot change, though other event details may be corrected. Enforce this through EventSchedulingEligibility in both Livewire validation and the locked Events UpdateAction so non-UI callers cannot bypass it.

## Lock venues before scheduling events
A venue may host only one event at a given date and time. Event CreateAction and UpdateAction must lock the selected venue inside their transaction before VenueSchedulingEligibility checks for an existing booking; unscheduled events do not reserve a venue time.

## Validate venue conflicts during event restoration
Restoring a soft-deleted event must lock its event and selected venue inside a transaction, then run VenueSchedulingEligibility before restoring. Restoration must not recreate a venue/date conflict created after deletion. It must also take the date-slot lock first and run MatchAssignmentConflictService::ensureEventCanBeRescheduled for the event's own date, so a restore cannot double-book a wrestler, tag team, referee, or title that another event booked at that time while it was deleted.
