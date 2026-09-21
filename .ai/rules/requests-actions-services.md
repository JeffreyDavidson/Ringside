---
paths:
  - 'app/{Livewire,Http/Requests,Actions,Services}/**'
---

# Requests Actions Services

## Validate input before actions
Apply request and form eligibility rules, including CanJoinTagTeam, at the Form Request or Livewire form boundary before constructing data objects. Actions receive validated data and orchestrate persistence; Services retain only transactional domain invariants needed to protect relationship integrity.
