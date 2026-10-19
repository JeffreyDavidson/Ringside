---
paths:
  - 'app/{Models,ValueObjects,Casts}/**'
---

# Models Value Objects Casts

## Keep models persistence-only
Keep Eloquent models and model concerns limited to persistence metadata, casts, relationships, and query definitions. Move lifecycle eligibility, can/ensure decisions, transitions, orchestration, and cross-model business rules into focused Actions, policies, rules, or state evaluators. Use immutable value objects only for domain values with real invariants or behavior; value objects must not query or orchestrate persistence.

## Value objects reject invalid values when built
A value object in `app/ValueObjects` is `readonly`, validates in its constructor and throws `InvalidArgumentException` for a bad value (`Height` refuses negative feet, inches above 11 and zero), compares by value, and implements `Castable` when it lives in a column. It knows nothing about persistence, the request or the current user.

## Casts only translate columns
A class in `app/Casts` turns one or more columns into a value object and back (`AddressCast`, `HeightCast`, `PhoneNumberCast`, `WeightCast`). `get()` builds the value object from the attributes; `set()` returns the column values and rejects anything that is not the expected value object with `InvalidArgumentException`. A cast does no queries and applies no business rule beyond the value object's own invariants. Test casts with a persisted round trip and a fresh reload.
