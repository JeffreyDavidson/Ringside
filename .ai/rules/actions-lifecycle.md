---
paths:
  - 'app/{Actions,Lifecycle}/**'
---

# Actions Lifecycle

## Centralize championship reign writes
Use ChampionshipReignManager as the single persistence boundary for opening, closing, and reconciling title championship reigns. Match and title Actions retain eligibility, locking, and workflow orchestration; TitleChampionshipQuery remains read-only reporting.
