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

`ManagerAssignmentBuilder` owns the shared manager filter, lifecycle-state constraints, and most-recent-hire ordering for wrestler and tag-team manager assignment records.

`MembershipPeriodBuilder` owns the shared `current()`, `ended()`, and most-recent-join ordering queries for tag-team and stable membership records.

`TagTeamMembershipBuilder` extends the shared membership-period queries with tag-team and wrestler filters and historical period-overlap constraints specific to `TagTeamWrestler` records.

`StableMembershipBuilder` extends the shared membership-period queries with stable filtering for both `StableWrestler` and `StableTagTeam` records. Stable membership-history tables query these typed records directly so membership dates remain first-class persisted data rather than manually extracted pivot attributes.

`StableBuilder` owns stable lifecycle-state filters and historical stable-membership projections for wrestlers and tag teams. The history methods select the persisted membership dates required by the table layer.

`EventBuilder` owns event scheduling-state filters and the canonical event-list ordering: dated events newest first, followed by unscheduled events.

## Shared Concerns

- `FiltersByEmploymentStatus` provides relationship-backed `employed()`, `unemployed()`, `released()`, and `futureEmployed()` filters for individual roster members and tag teams.
- `FiltersByRetirementStatus` provides the shared `retired()` filter for individual roster members and tag teams.
- `HasNameSearch` provides first-name and last-name matching for models that store those columns.

`EventMatchBuilder` owns reusable match-history and persisted assignment queries for event identifiers, matches on past events, competitors, referees, titles, and deterministic ordering by event date, card, and match number. Competitor and referee history relationships reuse its persisted past-event constraint rather than defining their own date comparisons. Scheduling policy and conflict exceptions remain in `MatchAssignmentConflictService`.

`TitleChampionshipBuilder` owns current and previous reign constraints, title and polymorphic champion filters, and persisted win/loss ordering. Championship reporting and derived reign calculations remain in `TitleChampionshipQuery`.

`MatchCompetitorBuilder` owns persisted competitor-record filters by competitor model type, competitor identifiers, and event identifiers. Scheduling policy and conflict exceptions remain in `MatchAssignmentConflictService`.

`MatchSideBuilder` owns canonical match-side ordering by persisted position. `EventMatch::sides()` applies the same ordering so side collections remain deterministic regardless of insertion order.

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
