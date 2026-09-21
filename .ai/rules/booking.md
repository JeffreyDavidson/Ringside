---
paths:
  - 'app/Lifecycle/Roster/Booking/**'
---

# Booking

## Use strategies for type-specific booking eligibility
Keep roster booking behavior in typed strategies: individual roster members share employment, suspension, and injury checks, while tag teams add membership requirements and recursively evaluate current wrestlers. Keep type dispatch isolated in RosterBookingStrategyResolver; do not add booking predicates to Eloquent models.
