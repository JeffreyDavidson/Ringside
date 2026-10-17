---
paths:
  - 'app/Data/**'
---

# Data

## Plain readonly data objects
Represent typed operation input and output with plain constructor-promoted data classes. Make data objects readonly unless their behavior requires mutable state. A data object may have a static named constructor such as `fromValidated()` that maps validated input onto the promoted constructor, and simple value-level checks on its own fields (emptiness, headcount); it has no methods other than the constructor, static named constructors and these checks, no `now()` and no queries. Persistence lookups and lifecycle eligibility decisions belong to their owning collaborators.
