---
paths:
  - 'app/{Models,ValueObjects,Casts}/**'
---

# Models Value Objects Casts

## Keep models persistence-only
Keep Eloquent models and model concerns limited to persistence metadata, casts, relationships, and query definitions. Move lifecycle eligibility, can/ensure decisions, transitions, orchestration, and cross-model business rules into focused Actions, policies, rules, or state evaluators. Use immutable value objects only for domain values with real invariants or behavior; value objects must not query or orchestrate persistence.
