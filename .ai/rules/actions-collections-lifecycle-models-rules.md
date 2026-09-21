---
paths:
  - 'app/{Actions,Collections,Lifecycle,Models,Rules}/**'
---

# Actions Collections Lifecycle Models Rules

## Keep roster booking eligibility outside models
Use RosterBookingEligibility for wrestler, referee, and tag-team booking decisions. Models expose lifecycle and relationship state but do not implement a Bookable contract or decide assignment eligibility.
