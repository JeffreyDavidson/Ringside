---
paths:
  - 'app/Lifecycle/**'
---

# Lifecycle

## Reuse lifecycle projection reads
Use LifecycleStateReader::readProjectedBoolean for boolean query projections before relationship fallbacks. Status readers should share this helper so projected list queries remain query-free without duplicating attribute inspection logic.

## Hold the guards and locks that queries must not
Row locks, `ensure…` guards and the business exceptions they throw live in app/Lifecycle, not in app/Queries (see queries.md). A Query returns what it read, or null when there is nothing to return; the Lifecycle class decides what that means and throws. EventRosterSchedulingEligibility locks the roster on an event's card and checks the new date; LiveShowSourceRequirements refuses a live show source that needs preparing.
