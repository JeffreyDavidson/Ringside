---
paths:
  - 'app/{Actions/Matches,Services}/**'
---

# Matches Services

## Prevent match assignment scheduling conflicts
Wrestlers, tag teams, and titles may appear only once per event card and cannot be assigned to different events at the same exact date and time. Referees may officiate multiple matches on one card, but not different events at the same exact date and time. Unscheduled events conflict only within their own card. Enforce these rules transactionally through MatchAssignmentConflictService; keep current-state availability failures separate.
