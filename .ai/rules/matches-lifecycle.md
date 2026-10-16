---
paths:
  - 'app/{Actions/Matches,Lifecycle/Matches}/**'
---

# Matches Scheduling Conflicts

## Prevent match assignment scheduling conflicts
Wrestlers, tag teams, and titles may appear only once per event card and cannot be assigned to different events at the same exact date and time. Referees may officiate multiple matches on one card, but not different events at the same exact date and time. Unscheduled events conflict only within their own card. Enforce these rules transactionally: MatchSchedulingLock takes the event-set and match row locks, and MatchAssignmentEligibility (with the competitor, referee and title eligibility classes it composes) refuses the conflicting assignment; keep current-state availability failures separate.
