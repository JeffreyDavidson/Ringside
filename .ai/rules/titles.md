---
paths:
  - 'app/{Models,Queries}/Titles/**'
---

# Titles

## Keep title reporting queries outside models
Keep championship relationships on Title. Eager load a list's current champions with `TitleBuilder::withCurrentChampion()` and read them from `currentChampionship.champion` on the row (Blade never resolves a Query); put reign-length calculation in TitleReignService instead of model convenience methods.
