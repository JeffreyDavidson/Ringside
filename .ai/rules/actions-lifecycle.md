---
paths:
  - 'app/{Actions,Lifecycle}/**'
---

# Actions Lifecycle

## Centralize championship reign writes
Use ChampionshipReignManager as the single persistence boundary for opening, closing, and reconciling title championship reigns. Match and title Actions retain eligibility, locking, and workflow orchestration; TitleChampionshipQuery remains read-only reporting.

## Lock cascade members in ascending id order
Cascades over collections lock rows in ascending primary key order: order any collection you iterate before locking its members, and never lock a member row before the parent row that owns the cascade.
