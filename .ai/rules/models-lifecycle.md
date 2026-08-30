---
paths:
  - 'app/{Models,Lifecycle}/**'
---

# Models Lifecycle

## Centralize computed employment status reads
EmploymentStatusResolver owns reading employment and retirement lifecycle facts for computed model status. It reuses withEmploymentStatusState projections when present and otherwise queries the typed relationships. Keep status classification out of model concerns.

## Centralize computed activity status reads
StableStatusResolver and TitleStatusResolver own reading activity and retirement lifecycle facts for computed model status through ActivityStatusStateReader. Reuse withActivityStatusState projections when present and otherwise query the typed relationships; keep status classification and relationship reads out of model concerns.
