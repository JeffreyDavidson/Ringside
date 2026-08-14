# Builder Architecture

Ringside uses typed custom Eloquent builders for reusable persisted-state queries. Models bind their builders with `#[UseEloquentBuilder]`; local model scopes and repository wrappers are not used.

## Organization

```text
app/Builders/
├── Concerns/
├── Events/
├── Matches/
├── Roster/
├── Titles/
└── Users/
```

Builders are grouped by technical layer first and wrestling entity second. A concrete builder belongs to the model it queries. A concern is appropriate only when multiple builders share the same query semantics.

`ManagerAssignmentBuilder` owns the shared `current()` and `ended()` persistence queries for wrestler and tag-team manager assignment records.

## Shared Concerns

- `HasEmploymentScopes` provides relationship-backed `employed()`, `unemployed()`, `released()`, and `futureEmployed()` queries for individual roster members and tag teams.
- `HasRetirementScopes` provides the shared `retired()` query for individual roster members and tag teams whose tables filter retirement directly.
- `HasNameSearch` provides first-name and last-name matching for models that store those columns.

`EventMatchBuilder` owns reusable match-history queries for matches on past events and matches involving a specific wrestler or tag team.

`TitleChampionshipBuilder` owns current and previous reign constraints and the polymorphic champion constraint. Championship reporting and derived reign calculations remain in `TitleChampionshipQuery`.

## Boundaries

Builders express database queries over relationships and stored lifecycle periods. They may:

- filter current, previous, or future lifecycle relationships;
- compose reusable relationship-existence constraints;
- define stable ordering used by multiple callers;
- return the concrete typed builder for fluent composition.

Builders do not decide whether a transition may occur, validate commands, or determine match-booking eligibility. Those rules belong to lifecycle eligibility classes, validation rules, Actions, and Services. Reporting projections that calculate derived values across records belong in focused query classes under `app/Queries`.

## Usage

```php
$futureWrestlers = Wrestler::query()
    ->futureEmployed()
    ->oldest('name')
    ->get();
```

Callers should use an existing builder method instead of repeating its relationship constraints or querying a computed model attribute as though it were a database column.

## Testing

Builder behavior that depends on persisted relationships is tested in `tests/Integration/Builders`. A production integration such as a Livewire filter also receives focused coverage proving that it composes the intended builder method.
