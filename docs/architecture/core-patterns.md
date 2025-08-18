# Core Architecture Patterns

## Relationship Patterns

### Employment Relationships (hired/fired)
- Managers ↔ Wrestlers: `managers_wrestlers` table with `hired_at`/`fired_at`
- Managers ↔ Tag Teams: `managers_tag_teams` table with `hired_at`/`fired_at`
- These represent business employment contracts
- **Business Rule**: Both entities must be employed, but managed entity doesn't need to be bookable
  - Injured/suspended wrestlers can still have managers
  - Managers provide career guidance regardless of competition availability

### Stable Membership (joined/left)  
- Stables ↔ Wrestlers: `stables_wrestlers` table with `joined_at`/`left_at`
- Stables ↔ Tag Teams: `stables_tag_teams` table with `joined_at`/`left_at`
- These represent stable membership relationships
- **DECISION: Use separate tables (not polymorphic)** for type safety and clear relationships

## Key Architecture Decisions
- NO direct stable-manager relationships
- Separate tables approach over polymorphic for better performance
- Employment status uses `App\Enums\Shared\EmploymentStatus`
- Domain-organized builders in `app/Builders/{Domain}/`
- Domain-organized enums in `app/Enums/{Domain}/`

## Computed Status Pattern
- **Status fields are computed, not stored** - eliminates data inconsistency
- Models use computed attributes: `protected function status(): Attribute`
- Factory methods NEVER set status fields manually
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
- Use `IsBookableCompetitor` trait
- Relationship: Many-to-many polymorphic through `event_match_competitors` table
- Method: `$this->morphToMany(EventMatch::class, 'competitor', 'event_match_competitors')`

### Officials (Referees)
- Use `OfficiatesMatches` trait  
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