---
paths:
  - 'app/{Actions,Exceptions,Lifecycle,Rules,Livewire}/Events/**'
---

# Events

## Keep occurred event dates immutable
Once an event has occurred, its persisted date cannot change, though other event details may be corrected. Enforce this through EventSchedulingEligibility in both Livewire validation and the locked Events UpdateAction so non-UI callers cannot bypass it.

## Lock venues before scheduling events
A venue may host only one event at a given date and time. Event CreateAction and UpdateAction must lock the selected venue inside their transaction before VenueSchedulingEligibility checks for an existing booking; unscheduled events do not reserve a venue time.
