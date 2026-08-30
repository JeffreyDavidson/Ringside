---
paths:
  - 'app/{Models,Lifecycle}/**'
---

# Models Lifecycle

## Centralize computed employment status reads
EmploymentStatusResolver owns reading employment and retirement lifecycle facts for computed model status. It reuses withEmploymentStatusState projections when present and otherwise queries the typed relationships. Keep status classification out of model concerns.
