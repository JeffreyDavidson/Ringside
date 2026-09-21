---
paths:
  - 'app/Rules/**'
---

# Rules

## Separate shared and model-specific Stable eligibility
Put employment, suspension, current-Stable exclusivity, and membership-date checks in App\Rules\Stables\CanJoinStable, parameterized by the eligible member model class. Keep constraints that apply only to one member type, such as wrestler injuries or duplicate representation through a selected tag team, in focused model-specific validation rules.

## Fail invalid records through validation
Custom validation rules must report missing or malformed selected records through the validation failure callback. Do not use findOrFail(), firstOrFail(), or sole() for user-supplied identifiers inside a rule; an exists rule may precede the custom rule, but the rule must remain safe when invoked independently.
