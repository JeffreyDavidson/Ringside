---
paths:
  - 'app/{Models,Queries}/Titles/**'
---

# Titles

## Keep title reporting queries outside models
Keep championship relationships on Title. Put current-champion reads in CurrentChampionQuery and reign-length calculation in TitleReignService instead of model convenience methods.
