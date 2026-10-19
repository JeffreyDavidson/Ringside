---
paths:
  - 'app/Lifecycle/Roster/Booking/**'
---

# Booking

## Use strategies for type-specific booking eligibility
Keep roster booking behavior in typed strategies: individual roster members share employment, suspension, and injury checks, while tag teams add membership requirements and recursively evaluate current wrestlers. Keep type dispatch isolated in RosterBookingStrategyResolver; do not add booking predicates to Eloquent models.

## Keep bookable() scopes in parity with the strategies
IndividualBuilder::bookable() and TagTeamBuilder::bookable() are SQL projections of the booking strategies and feed only the match form search. When a booking predicate changes, change the scope in the same commit and extend BookableScopeParityTest; the IsBookable rules and RosterBookingEligibility remain the authority, so narrow the scope rather than widen it when SQL cannot express a rule exactly.

## Compose event-day restrictions without changing result eligibility

Keep context-free `RosterBookingEligibility::allows()` and `bookable()` lifecycle-only. Given an event date, `allows($member, $on)` and `bookableOn($day, $at)` read employment on that date (tag teams: the team and every current wrestler) while retirement, suspension and injury stay as they stand now; keep the two in parity and pass the event's date from every booking caller. `RosterDateAvailability` owns unavailable promotion-local days, and `bookableOn()` must stay in parity with composed lifecycle/date evaluation. Keep unavailable dates independent of injury/employment and out of result/title eligibility. During a match repair, only the locked existing assignment snapshot may retain a calendar restriction; client input never supplies a bypass.

## Lock represented team members for calendar checks

After the existing ordered event-set/match locks, prelock referees, selected team parents, then the complete direct/represented wrestler set in ascending IDs before per-side assignment checks. Use `MatchRosterLock`; do not take a member before a selected parent team. Calendar entry writes lock their person and never acquire event/match locks afterwards. Schedule/restore Actions validate the whole represented roster at the proposed local date even when no same-instant booking conflict exists.
