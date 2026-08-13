---
paths:
  - 'app/{Actions,Lifecycle,Models}/**'
---

# Actions Lifecycle Models

## Audit soft deletion atomically
Keep each model's deleted_at value authoritative for soft-deletion state. Typed DeleteAction and RestoreAction workflows delegate only the state mutation plus Deleted/Restored audit write to DeletionStateManager in one transaction; validation, period closure, and relationship cleanup remain in the typed Actions.

## Keep individual injury eligibility outside models
Store individual injury state through the shared polymorphic Injury model, but keep wrestler, manager, and referee injury/healing eligibility in IndividualInjuryEligibility. Typed Actions must reload and lock the individual inside a database transaction before checking eligibility and changing the injury period.
