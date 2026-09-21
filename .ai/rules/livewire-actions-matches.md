---
paths:
  - 'app/{Livewire,Actions}/Matches/**'
---

# Livewire Actions Matches

## Validate championship defenders at both boundaries
Apply a data-aware form rule to each selected title so a non-vacant title's current wrestler or tag-team champion must appear in the nested competitor payload. Repeat the invariant authoritatively in AddTitlesToMatchAction after competitors are assigned; vacant titles require no champion.
