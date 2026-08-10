---
paths:
  - 'app/Rules/**'
---

# Rules

## Separate shared and model-specific Stable eligibility
Put employment, suspension, current-Stable exclusivity, and membership-date checks in App\Rules\Stables\CanJoinStable, parameterized by the eligible member model class. Keep constraints that apply only to one member type, such as wrestler injuries or duplicate representation through a selected tag team, in focused model-specific validation rules.
