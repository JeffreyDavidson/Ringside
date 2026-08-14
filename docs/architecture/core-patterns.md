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
- Domain-organized builders in `app/Builders/{Domain}/`
- Domain-organized enums in `app/Enums/{Domain}/`

## Related Model Resolution

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
- Activity-period state uses the canonical predicates `isCurrentlyActive()`, `isInactive()`, `isUnactivated()`, `hasFutureActivity()`, and `wasActiveOn()` without duplicate aliases.
- Status computed from relationships (employment, retirement, injury, suspension)
- Priority order: Retired > Employed > FutureEmployment > Released > Unemployed

## Factory Method Patterns
- **Employable entities**: `employed()`, `unemployed()`, `retired()`, `released()`, `suspended()`, `injured()`
- **Bookable entities**: `bookable()` (alias for employed() for competitors and officials)
- **Non-bookable entities**: NO `bookable()` method (Managers, Stables, etc.)
- **Activation entities**: `active()`, `inactive()`, `unactivated()`
- **User entities**: `verified()`, `unverified()`
- **Relationships**: Set via `has()` relationships, never direct field assignment

## Essential Enum Usage
- **Employment Status**: `App\Enums\Shared\EmploymentStatus` for pure employment states
- **Activation Status**: `App\Enums\Shared\ActivationStatus` for general activation
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

**Data objects are pure data containers - NEVER add methods:**

```php
// ✅ CORRECT: Pure data container
readonly class StableData
{
    public function __construct(
        public string $name,
        public ?Carbon $start_date,
        public Collection $tagTeams,
        public Collection $wrestlers,
    ) {}
}

// ✅ CORRECT: Access properties directly in Actions
$stable = Stable::create([
    'name' => $stableData->name,
]);

// ❌ WRONG: Never add toArray() or other methods to Data objects
readonly class StableData
{
    public function toArray(): array { /* NEVER DO THIS */ }
}
```

**Key Principles:**
- Data objects should ONLY have constructor and public readonly properties
- Actions access data via properties: `$data->property`
- No `toArray()`, `validate()`, `transform()`, or other methods on Data objects
- Keep Data objects as simple, immutable data containers

## Policy Pattern

**All policies use before hook pattern:**
```php
public function before(User $user, string $ability): ?bool
{
    if ($user->isAdministrator()) {
        return true; // Bypass all checks for admins
    }
    return null; // Continue to individual method checks
}

public function viewList(User $user): bool
{
    return false; // Will be bypassed by before hook for administrators
}
```

**Benefits:** Eliminates repetitive administrator checks in every method.

## Controller Patterns

### Invokable Controllers
- Domain-organized in `app/Http/Controllers/{Domain}/`
- Always authorize using `Gate::authorize()` before business logic
- Return views with explicit data arrays when needed
- Example: `return view('tag-teams.index', ['data' => $data]);`

### View Naming Convention
- Controller view names use dot notation: `tag-teams.index`
- Maps to file path: `resources/views/tag-teams/index.blade.php`
- **Always use kebab-case** for view directories and files
