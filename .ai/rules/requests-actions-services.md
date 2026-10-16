---
paths:
  - 'app/{Livewire,Http/Requests,Actions,Services}/**'
---

# Requests Actions Services

## Validate input before actions
Apply request and form eligibility rules, including CanJoinTagTeam, at the Form Request or Livewire form boundary before constructing data objects. Actions receive validated data, lock the rows they change, check the business and state rules through Lifecycle and write; Services are read-only and hold no transactional invariants, locks or validation.
