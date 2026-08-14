# Builder Architecture

## Responsibility

Ringside uses typed Eloquent builders for reusable queries over persisted state. Builders answer questions such as whether a roster member is currently employed, injured, suspended, retired, or generally available. They do not decide whether a model may perform a lifecycle transition or be assigned to a match.

Models bind their builders with Laravel's `#[UseEloquentBuilder]` attribute. Reusable builder behavior is shared through typed parent builders and concerns rather than local model scopes.

## Organization

```text
app/Builders/
├── Concerns/           # Shared query behavior
├── Events/             # Event and venue builders
├── Roster/             # Roster and stable builders
├── Titles/             # Title and championship builders
└── Users/              # User builders
```

`IndividualBuilder` contains persisted-state queries shared by wrestlers, managers, and referees. `WrestlerBuilder`, `ManagerBuilder`, and `RefereeBuilder` provide the model-specific builder types. Tag teams, stables, events, venues, titles, title championships, and users each have their own typed builder.

## Persisted-State Queries

Builders may compose database relationships to select records in a known state:

```php
$availableWrestlers = Wrestler::query()
    ->available()
    ->orderBy('last_name')
    ->get();

$injuredWrestlers = Wrestler::query()
    ->injured()
    ->get();

$activeStables = Stable::query()
    ->currentlyActive()
    ->withMinimumMembers()
    ->get();
```

Shared activity-period queries live in `App\Builders\Concerns\HasActivityPeriodScopes` and are composed by `StableBuilder` and `TitleBuilder`.

## Booking Boundary

`available()` narrows a query to roster members whose persisted lifecycle state makes them candidates for booking. It is not the final assignment decision.

Use `App\Lifecycle\RosterBookingEligibility` to decide whether a loaded wrestler, referee, or tag team satisfies booking rules. Use `App\Services\MatchAssignmentConflictService` when assigning models to a match so conflicts with existing event and match assignments are checked transactionally.

```php
$candidates = Wrestler::query()
    ->available()
    ->get()
    ->filter(RosterBookingEligibility::allows(...));
```

Do not add date-specific booking or scheduling methods to a model builder. Those decisions depend on the assignment context and belong in the booking eligibility and scheduling collaborators.

## Guidelines

- Keep reusable filtering and relationship-existence queries on typed builders.
- Keep lifecycle transition rules in the established lifecycle or validation collaborators.
- Keep match booking eligibility in `RosterBookingEligibility`.
- Keep event and match assignment conflicts in `MatchAssignmentConflictService`.
- Eager-load relationships on the query that consumes them rather than adding model-level eager-loading defaults.
- Test builders against realistic factory states and assert the exact records returned.
