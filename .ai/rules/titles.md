---
paths:
  - 'app/{Models,Queries}/Titles/**'
---

# Titles

## Keep title reporting queries outside models
Keep championship relationships on Title. Put current, previous, first, longest, vacancy, and reign-count reporting in TitleChampionshipQuery instead of model convenience methods.
