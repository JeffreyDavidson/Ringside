---
paths:
  - 'app/{Models,Queries}/Titles/**'
---

# Titles

## Keep title reporting queries outside models
Keep championship relationships on Title. Put current-champion and reign-length reporting in TitleChampionshipQuery instead of model convenience methods.
