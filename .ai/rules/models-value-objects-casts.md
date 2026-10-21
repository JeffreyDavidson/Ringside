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

## Read-side value objects have no column or cast
A value object does not need a column. A `readonly` class in `app/ValueObjects` may wrap a validated shape that is only read, such as the competitors picked on a form (`CompetitorSelection`), so its callers stop re-filtering and re-unpacking a raw array. It validates its entries in the constructor and throws `InvalidArgumentException`, offers named constructors (`fromSides()`) for the raw input and named questions (`idsByType()`, `onSide()`, `offSide()`, `isHeadToHead()`) in place of `array_filter` and `array_column` at each call site, and returns plain arrays only where a caller needs the entries themselves (`entries()`). It implements neither `Castable` nor a cast, and it does not query or write. Test it as a unit under `tests/Unit/ValueObjects/`, including each constructor refusal.

It can equally wrap a loose array shape that Actions and Lifecycle guards share, such as the sides of a match being booked (`MatchSides`: the wrestlers and tag teams on each side). It then replaces every `$side['wrestlers'] ?? []` unpacking with named questions (`competitors()`, `entriesPerSide()`), and a Data object carries it as a property instead of the raw array.

A read-side value object may also wrap a saved JSON section read by several callers, such as a pack's production (`ProductionPlan`), so the sheet, the live source and the editor stop repeating the same active filter and per-item filter. It takes the section as the pack's phpstan types describe it, answers with the saved order unchanged (`activeAssignments()`, `cuesFor($itemKey)`) and leaves writing the section to the Lifecycle class that owns it. A read-side value object wrapping data that was already validated when it was saved (`ProductionPlan` over the stored pack's production section) may skip constructor validation and rely on the stored shape's phpstan types; value objects built from user input still validate.

A read-side value object may be built from one scalar and answer only arithmetic about it, such as a tournament bracket from its capacity (`BracketShape`: `rounds()`, `isFinal()`, `seeds()`, `slots()`, `feeders()`, `ancestors()`, `descendants()`). Callers ask it for the final round and for which nodes feed which, instead of repeating `log($capacity, 2)` and `$position * 2 - 1`; it returns round/position pairs and never reads nodes, so the service that owns the query turns the pairs into one lookup.
