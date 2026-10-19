---
paths:
  - 'app/Data/**'
---

# Data

## Plain readonly data objects
Represent typed operation input and output with plain constructor-promoted data classes. Make data objects readonly unless their behavior requires mutable state. A data object may have a static named constructor such as `fromValidated()` (a static method that returns `static`, `self` or its own class) that maps validated input onto the promoted constructor, and simple value-level checks on its own fields (emptiness, headcount); it has no methods other than the constructor, static named constructors and these checks, no `now()` and no queries. Persistence lookups and lifecycle eligibility decisions belong to their owning collaborators. `tests/Feature/Architecture/DataArchitectureTest.php` enforces the allowed methods.

## Name by operation and group by domain
Name a data object for the thing it carries, with a `Data` suffix (`EventMatchData`, `StableMembershipData`), and keep it in its domain folder under `app/Data`; `DataArchitectureTest` only discovers classes one folder deep. A data object may carry already-loaded models for the Action to use, but it never loads them itself.
