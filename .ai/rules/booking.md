---
paths:
  - 'app/Lifecycle/Roster/Booking/**'
---

# Booking

## Use strategies for type-specific booking eligibility
Keep roster booking behavior in typed strategies: individual roster members share employment, suspension, and injury checks, while tag teams add membership requirements and recursively evaluate current wrestlers. Keep type dispatch isolated in RosterBookingStrategyResolver; do not add booking predicates to Eloquent models.

## Keep bookable() scopes in parity with the strategies
IndividualBuilder::bookable() and TagTeamBuilder::bookable() are SQL projections of the booking strategies and feed only the match form search. When a booking predicate changes, change the scope in the same commit and extend BookableScopeParityTest; the IsBookable rules and RosterBookingEligibility remain the authority, so narrow the scope rather than widen it when SQL cannot express a rule exactly.
