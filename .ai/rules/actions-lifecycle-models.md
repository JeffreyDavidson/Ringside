---
paths:
  - 'app/{Actions,Lifecycle,Models}/**'
---

# Actions Lifecycle Models

## Audit soft deletion atomically
Keep each model's deleted_at value authoritative for soft-deletion state. Typed DeleteAction and RestoreAction workflows delegate only the state mutation plus Deleted/Restored audit write to DeletionStateManager in one transaction; validation, period closure, and relationship cleanup remain in the typed Actions.
