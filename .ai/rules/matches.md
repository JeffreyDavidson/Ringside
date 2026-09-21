---
paths:
  - 'app/{Actions,Models}/Matches/**'
---

# Matches

## Preserve match history with soft deletion
Delete matches by soft deleting only the EventMatch through DeletionStateManager. Preserve competitors, referee and title assignments, results, winners, losers, and championship references as historical records. Default queries exclude deleted matches; use withTrashed() only for explicit historical access.
