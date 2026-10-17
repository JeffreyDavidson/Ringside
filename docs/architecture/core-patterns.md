# Core Architecture Patterns

## Relationship Patterns

Events define their match relationship directly, and venues define their event history
relationships directly. These relationships have one model owner and do not use
single-consumer relationship traits or speculative override helpers.

### Employment Relationships (hired/fired)
- Managers ↔ Wrestlers: `wrestlers_managers` table with `hired_at`/`fired_at`
- Managers ↔ Tag Teams: `tag_teams_managers` table with `hired_at`/`fired_at`
- These represent business employment contracts
- **Business Rule**: Both entities must be employed, but managed entity doesn't need to be bookable
  - Injured/suspended wrestlers can still have managers
  - Managers provide career guidance regardless of competition availability

### Stable Membership (joined/left)  
- Stables ↔ Wrestlers: `stables_wrestlers` table with `joined_at`/`left_at`
- Stables ↔ Tag Teams: `stables_tag_teams` table with `joined_at`/`left_at`
- These represent stable membership relationships
- The nullable `left_at` column is the authoritative current/previous membership state; pivot models do not expose duplicate state aliases.
- Stable directly owns its wrestler and tag-team membership relationships; do not add a single-consumer relationship trait.
- **DECISION: Use separate tables (not polymorphic)** for type safety and clear relationships

## Key Architecture Decisions
- NO direct stable-manager relationships
- Separate tables approach over polymorphic for better performance
- Employment status uses `App\Enums\Shared\EmploymentStatus`
- Roster-owned Eloquent models live under `app/Models/Roster/{Entity}/`; lifecycle records, matches, titles, events, and users remain in their owning model namespaces.
- `AppServiceProvider` retains aliases for the former top-level roster model class names because immutable historical migrations reference them. Application code must use the `App\\Models\\Roster` classes directly.
- Domain-organized builders in `app/Builders/{Domain}/`
- Domain-organized, read-only services in `app/Services/{Domain}/` for cross-record calculations, lookups and the request's promotion context. Services throw no business exceptions, take no locks, validate nothing and never depend on `app/Lifecycle`; guards and locks live in Lifecycle and state-changing workflows belong in Actions.
- Domain-organized enums in `app/Enums/{Domain}/`
- Domain-organized queries in `app/Queries/{Domain}/`: one `{Purpose}Query` runs one database query (plus its eager loads) and returns the results or the prepared builder; the caller finishes it. A page that needs several reads has a ViewModel in `app/ViewModels/{Domain}/`, called only from controllers and Livewire components, which returns a ready-to-render array. See `.ai/rules/queries.md` and `.ai/rules/view-models.md`.
- Lifecycle (`app/Lifecycle`) holds eligibility guards, requirement checks, period writers and row locks; Services read; Actions lock, check the rules, write and throw business exceptions. See [Lifecycle Operation Boundaries](lifecycle-operation-boundaries.md).

## Related Model Resolution

- Eloquent polymorphic relationships use an enforced morph map. Persist the stable lowercase aliases `event`, `manager`, `match`, `referee`, `stable`, `tag_team`, `title`, `venue`, and `wrestler`; never persist PHP class names or ad hoc aliases.
- Obtain a model's persisted alias through `getMorphClass()`. `LifecycleOwnerType` uses the same backed values for lifecycle transition subjects.
- Employment history uses the shared `App\Models\Lifecycle\Employment` model and an `employable` polymorphic owner.
- Wrestlers, managers, referees, and tag teams expose the same typed employment relationships through `IsEmployable`.
- Employment state uses one predicate per distinct meaning: `isEmployed()`, `hasFutureEmployment()`, `hasNoCurrentOrFutureEmployment()`, `isReleased()`, and `hasEmploymentHistory()`. Do not add aliases for those states.
- Employment models are not resolved from entity naming conventions and entity-specific employment record classes must not be introduced.
- Injury history uses the shared `App\Models\Lifecycle\Injury` model and an `injurable` polymorphic owner.
- Suspension history uses the shared `App\Models\Lifecycle\Suspension` model and a `suspendable` polymorphic owner.
- Suspension models are not resolved from entity naming conventions and entity-specific suspension record classes must not be introduced.
- Retirement history uses the shared `App\Models\Lifecycle\Retirement` model and a `retirable` polymorphic owner.
- Retirement models are not resolved from entity naming conventions and entity-specific retirement record classes must not be introduced.
- Wrestlers, managers, and referees expose the same typed injury relationships through `IsInjurable`; eligibility and transition orchestration remain in the existing individual concerns and Actions.
- Other lifecycle dimensions retain their existing persistence models until they are reviewed and migrated independently.

## Computed Status Pattern
- **Status fields are computed, not stored** - eliminates data inconsistency
- Models use computed attributes: `protected function status(): Attribute`
- Membership-derived calculations belong to typed membership data objects. For example, `TagTeamMembershipData` calculates combined wrestler weight without adding a presentation aggregate to the Eloquent model.
- Factory methods NEVER set status fields manually
- Activity-period state uses the canonical predicates `hasActivityPeriods()`, `isCurrentlyActive()`, `isInactive()`, and `hasFutureActivity()` without duplicate aliases. Date-change validation compares the requested date with the current activity-period record inside its typed validation rule rather than exposing validation-specific helpers on models.
- Status computed from relationships (employment, retirement, injury, suspension)
- Priority order: Retired > Employed > FutureEmployment > Released > Unemployed

## Factory Method Patterns
- **Employable entities**: `employed()`, `unemployed()`, `retired()`, `released()`, `suspended()`, `injured()`
- **Bookable fixtures**: Competitor and official factories may use `bookable()` to construct an eligible test fixture
- **Persisted availability queries**: Roster builders use `available()` to select candidates; tag-team builders use explicit minimum-wrestler queries, and `RosterBookingEligibility` makes the final booking decision through the typed individual and tag-team booking strategies
- **Non-bookable entities**: Managers and stables do not expose a `bookable()` factory state
- **Activation entities**: `active()`, `inactive()`, `unactivated()`
- **User entities**: `verified()`, `unverified()`
- **Relationships**: Set via `has()` relationships, never direct field assignment

## Essential Enum Usage
- **Employment Status**: `App\Enums\Shared\EmploymentStatus` for pure employment states
- **Title Status**: `App\Enums\Titles\TitleStatus` for title-specific states
- **User Enums**: `App\Enums\Users\Role` and `App\Enums\Users\UserStatus`

## Bookable Interface Implementation

**Two distinct patterns for match participation:**

### Competitors (Wrestlers, Tag Teams)
- Use the `HasMatchParticipations` trait for persisted competitor-match relationships
- Relationship: Many-to-many polymorphic through `event_match_competitors` table
- Method: `$this->morphToMany(EventMatch::class, 'competitor', 'event_match_competitors')`

### Officials (Referees)
- Define officiated match relationships directly on `Referee`, the sole official model
- Relationship: Many-to-many direct through `events_matches_referees` table
- Method: `$this->belongsToMany(EventMatch::class, 'events_matches_referees')`

**Key Principle:** Different entity types have different relationships with matches - competitors participate, officials officiate.

### Non-Bookable Entities (Managers)
- **Managers are NOT bookable** - they manage other entities but don't participate in matches
- Factory pattern: Use `employed()`, `suspended()`, `injured()`, etc., but NO `bookable()` method
- Relationship pattern: Managers have employment relationships with wrestlers/tag teams, not match participation
- **Key Business Rule**: Wrestlers don't need to be bookable to have a manager - only employed
  - Manager ↔ Wrestler relationship requires both to be employed
  - Wrestler bookability is separate (affected by injury, suspension, etc.)
  - An injured wrestler can still have a manager managing their career

## Data Object Pattern

Actions take typed readonly Data objects from `app/Data`, never arrays, so they are safe to call from a controller, a Livewire component, a job or a command. A Data object carries data only: constructor-promoted public readonly properties, an optional static named constructor such as `fromValidated()`, and simple value-level checks on its own fields (`StableMembershipData::isEmpty()`, `getTotalMemberCount()`). It never reads the clock, queries the database or decides whether an operation is allowed. `tests/Feature/Architecture/DataArchitectureTest.php` enforces this. See `.ai/rules/data.md`.

```php
// Pure data container with a named constructor
readonly class StableData
{
    public function __construct(
        public string $name,
        public ?Carbon $start_date,
        public StableMembershipData $members,
        public ?Carbon $end_date = null,
    ) {}
}

// Actions read the properties
$stable = Stable::query()->create(['name' => $stableData->name]);
```

Input is validated before the Data object is built: in the Form Request or Livewire form, with permissions checked at the route (`->can()`) or the component (`Gate::authorize`). Actions do not call `Validator::make`, `Gate::authorize` or `abort()`; they lock, check business and state rules, write, and throw business exceptions. A non-HTTP caller validates its own input first.

## Policy Pattern

`PromotionGate` (`Gate::before`) decides every ability on promotion-owned models from the user's membership role, so the model policies are stubs that deny whatever the gate leaves alone. The single `before()` hook lives in `PromotionGate`, not in each policy. This is a deliberate exception to the usual "super-admin bypass only" use of `Gate::before`; the reasons and costs are in [Decision 0001](../decisions/0001-promotion-gate-is-the-permission-engine.md).

```php
class WrestlerPolicy
{
    public function view(User $user, Wrestler $wrestler): bool
    {
        return false; // PromotionGate answers first; this only runs when it falls through
    }
}
```

New role rules go in `MembershipRole` or the gate's subject tables, with a case per role in `tests/Integration/Policies/PromotionGateTest.php`.

## Controller Patterns

### Resource and invokable controllers
- Domain-organized in `app/Http/Controllers/{Domain}/`, with no base controller; each class stands alone.
- A resource controller is named after the singular resource (`WrestlerController`, `EventPackController`) and uses only the standard resource methods it needs. Plural URIs do not make the class name plural. A standalone action is an invokable controller named after the action (`UnpublishEventController`).
- Authorize in the route file with `->can('view', 'wrestler')` or `->can('viewAny', Wrestler::class)`, not inside the controller. Controllers return views with explicit data arrays: `return view('wrestlers.show', ['wrestler' => $wrestler]);`.
- A controller that writes validates through a Form Request and hands a Data object to an Action (`EventPackController::update`).

### View Naming Convention
- Controller view names use dot notation: `tag-teams.index`
- Maps to file path: `resources/views/tag-teams/index.blade.php`
- **Always use kebab-case** for view directories and files
