---
paths:
  - 'app/Data/**'
---

# Data

## Plain readonly data objects
Represent typed operation input and output with plain constructor-promoted data classes. Make data objects readonly unless their behavior requires mutable state. Keep only value-level operations such as emptiness and headcount on data objects; move persistence lookups and lifecycle eligibility decisions to their owning collaborators. A data object may have a static named constructor such as `fromValidated()` that maps validated input onto the promoted constructor, and no other methods.
