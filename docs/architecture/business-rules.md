# Business Rules & Domain Logic

This document outlines the core business rules that govern the wrestling promotion management system.

## Core Business Capabilities

### Injury Capability
**Rule**: Only individual people can be injured
- **Eligible**: Wrestlers, Referees, Managers
- **Not Eligible**: TagTeams, Stables, Titles
- **Rationale**: Injuries affect individual people, not groups or objects

### Suspension Capability
**Rule**: Only entities that can perform actions can be suspended
- **Eligible**: Wrestlers, Referees, Managers, TagTeams
- **Not Eligible**: Stables, Titles
- **Rationale**: Suspension prevents participation in activities

### Retirement Capability
**Rule**: All active entities can be retired
- **Eligible**: Wrestlers, Managers, Referees, TagTeams, Titles, Stables
- **Rationale**: Any entity can cease active participation

### Employment Capability
**Rule**: Only entities that can work can be employed
- **Eligible**: Wrestlers, Managers, Referees, TagTeams
- **Not Eligible**: Titles, Stables
- **Rationale**: Employment represents a working relationship

### Pull Capability
**Rule**: Only titles can be pulled from circulation
- **Eligible**: Titles only
- **Not Eligible**: All other entities
- **Rationale**: Pulling is a title-specific business action

### Debut Capability
**Rule**: Only titles and stables can be debuted
- **Eligible**: Titles, Stables
- **Not Eligible**: Wrestlers, Managers, Referees, TagTeams
- **Rationale**: Debuts represent the first time a title is contested or a stable is formed

### Booking Capability
**Rule**: Only entities that can compete in matches can be booked
- **Eligible**: Wrestlers, TagTeams
- **Not Eligible**: Managers, Referees, Titles, Stables
- **Rationale**: Booking is for match competition, not management or officiating

## Match System Architecture

### Match Types and Competitor Rules
**Rule**: Match types have specific competitor type restrictions

#### Wrestler-Only Match Types
- **Singles**: Only wrestler vs wrestler
- **Royal Rumble**: Only individual wrestlers
- **Rationale**: These match types require individual competitor mechanics

#### Mixed Competitor Match Types  
- **Tag Team**: Can be wrestlers, tag teams, or mixed combinations
- **Triple Threat**: Can be wrestlers, tag teams, or mixed
- **Fatal 4-Way**: Can be wrestlers, tag teams, or mixed
- **6/8/10 Man Tag Team**: Can be wrestlers, tag teams, or mixed
- **Handicap Matches**: Can be wrestlers, tag teams, or mixed
- **Battle Royal**: Can be wrestlers, tag teams, or mixed
- **Tornado Tag Team**: Can be wrestlers, tag teams, or mixed
- **Gauntlet**: Can be wrestlers, tag teams, or mixed
- **Rationale**: These match types support flexible competitor configurations

### Winner/Loser System
**Rule**: Matches can have multiple winners and multiple losers

#### Winner/Loser Assignment
- **Multiple Winners**: Tag team matches, handicap matches, etc. can have multiple winners
- **Multiple Losers**: Battle royals, elimination matches can have multiple losers
- **No-Outcome Matches**: Some match decisions result in no winners or losers
  - Time Limit Draw
  - No Decision
  - Reverse Decision
- **Rationale**: Wrestling matches have complex outcome scenarios

#### Match Result Architecture
- **EventMatchResult**: Central result record linking to match decision
- **EventMatchWinner**: Polymorphic pivot for all match winners
- **EventMatchLoser**: Polymorphic pivot for all match losers
- **MatchDecision**: Determines if winners/losers should be recorded

### Championship System
**Rule**: Title matches follow specific championship validation

#### Title Type Matching
- **Singles Titles**: Can only be held by individual wrestlers
- **Tag Team Titles**: Can only be held by tag teams
- **Match Validation**: Title matches must use compatible competitor types
- **Champion Defense**: Current champions can defend against appropriate challengers

## Derived Business Rules

### Release Rules
**Rule**: Only models that can be employed can be released
- **Eligible**: Wrestlers, Managers, Referees, TagTeams
- **Logic**: You can only release what you can employ

### Reinstatement Rules
**Rule**: Only models that can be suspended can be reinstated
- **Eligible**: Wrestlers, Managers, Referees, TagTeams
- **Logic**: You can only reinstate what you can suspend

### Repository Method Availability
**Rule**: Not all repositories have all methods based on business capabilities
- `TagTeamRepository` lacks `endInjury()` - TagTeams cannot be injured
- `TitleRepository` lacks `endSuspension()`, `endInjury()`, `endEmployment()` - Titles cannot be suspended, injured, or employed
- `StableRepository` has deprecated manager methods - Managers associate through wrestlers/tag teams

## Stable Membership Architecture

### Core Principle
**Rule**: Only Wrestlers and TagTeams can be direct stable members
- **Direct Members**: Wrestlers, TagTeams
- **Indirect Association**: Managers associate through the wrestlers/tag teams they manage
- **Architecture**: Uses polymorphic `StableMember` model with `stables_members` table

### Implementation Details
- **Table**: `stables_members` with `member_type` and `member_id` columns
- **Morph Map**: `'wrestler'` → `Wrestler::class`, `'tagTeam'` → `TagTeam::class`
- **Migration**: Consolidated from three separate models for better maintainability

### Manager Association
**Rule**: Managers are NOT direct stable members and stables do NOT implement Manageable
- **How Managers Associate**: Through the wrestlers/tag teams they manage
- **Business Logic**: A manager's stable affiliation follows their clients
- **Repository Impact**: Manager methods in StableRepository are deprecated
- **Interface Compliance**: Stables do NOT implement `Manageable` interface
- **Property Access**: `$stable->currentManagers` does NOT exist - this would be a business logic error
- **Indirect Access**: To get managers associated with a stable, query through `$stable->currentWrestlers` and `$stable->currentTagTeams` relationships

## Employment Status Dependencies

### Critical Employment Rule
**Rule**: Injury/suspension should only be possible when the entity is currently employed
- **Validation**: Always check employment status before applying injury or suspension
- **Non-Employed States**: Retired, released, unemployed, or future scheduled employment
- **Business Logic**: You can't injure or suspend someone who isn't working

### Status Transition Workflows

#### Release-to-Retirement Workflow
**Rule**: Released entities CAN be retired
- **Valid Transition**: Release → Retirement
- **Business Rationale**: An entity can be released from employment and later retire
- **Implementation**: `IndividualRetirementValidation` strategy allows this transition

#### Employment Dependency Chain
1. **Employment Required**: For injury and suspension actions
2. **Status Validation**: Check current employment before status changes
3. **Transition Rules**: Specific workflows for each status change

## Model Attribute Patterns

### Individual People Models
**Rule**: Use separate name fields for people
- **Models**: Managers, Referees
- **Fields**: `first_name` and `last_name` instead of single `name` field
- **Rationale**: People have structured names for formal documentation

### Entity Models
**Rule**: Use single name field for entities
- **Models**: Wrestlers, TagTeams, Stables, Events, Venues, Titles
- **Fields**: Single `name` field
- **Rationale**: Entities have single identifying names

### Address Models
**Rule**: Use structured address fields
- **Models**: Venues
- **Fields**: `street_address`, `city`, `state`, `zipcode`
- **Rationale**: Venues need complete address information for events

### Time-Based Tracking Models
**Rule**: Use consistent field naming for period tracking
- **Start Fields**: Always use `started_at` (not `injured_at`, `suspended_at`, `retired_at`)
- **End Fields**: Always use `ended_at` (not `cleared_at`, `reinstated_at`, `unretired_at`)
- **Models**: All injury, suspension, retirement, and employment tracking models
- **Examples**:
  - `ManagerInjury`: `started_at`, `ended_at`
  - `WrestlerSuspension`: `started_at`, `ended_at`
  - `WrestlerRetirement`: `started_at`, `ended_at`
  - `WrestlerEmployment`: `started_at`, `ended_at`
- **Rationale**: Consistent naming simplifies queries and business logic across all time-based tracking

## Interface-Based Architecture

### Replacing method_exists() with Type-Safe Interfaces
**Rule**: Use interface contracts instead of runtime method checking

#### Anti-Pattern (Avoid)
```php
// Don't use method_exists - this is runtime checking with no type safety
if (method_exists($entity, 'currentWrestlers')) {
    $wrestlers = $entity->currentWrestlers()->get();
}
```

#### Preferred Pattern (Use This)
```php
// Use type-safe interface checking with compile-time validation
if ($entity instanceof ProvidesCurrentWrestlers) {
    $wrestlers = $entity->currentWrestlers()->get();
}
```

### Interface Design Principles
1. **Marker Interfaces**: `ProvidesCurrentWrestlers` for method access
2. **Comprehensive Interfaces**: `Validatable` for full behavioral contracts
3. **Domain-Specific**: `HasTagTeamWrestlers` over generic interfaces
4. **Naming Conventions**: `Has*`, `Can*`, `Is*`, `Provides*`

## Complex Stable Operations

### Stable Merger Rules
**Rule**: Two active stables can be merged to consolidate membership
- **Requirements**: Both stables must be active or inactive (not retired)
- **Process**: All members transfer from secondary to primary stable
- **Result**: Secondary stable is soft-deleted, primary retains all members
- **Data Integrity**: All membership history is preserved
- **Transaction Safety**: Operation is atomic - all changes succeed or fail together

### Stable Splitting Rules
**Rule**: Active stable can be split to create new stable with selected members
- **Requirements**: Original stable must be active or inactive (not retired)
- **Process**: Selected members transfer to new stable, remaining stay in original
- **Result**: Two active stables with divided membership
- **Member Selection**: Can transfer wrestlers, tag teams, and managers selectively
- **Minimum Requirements**: Both resulting stables should meet minimum member requirements
- **Data Integrity**: Membership history preserved, proper date tracking maintained

### Stable Reunification Rules
**Rule**: Inactive stables can be reunited for new storylines
- **Requirements**: Stable must be inactive (not retired, not currently active)
- **Process**: Creates new activity period for the stable
- **Result**: Stable becomes active again with existing member structure
- **Status Transition**: Inactive → Active (bypasses debut process)
- **Historical Data**: Previous activity periods preserved

### Complex Operation Constraints
- **Employment Status**: Only employed members can be transferred between stables
- **Availability Checking**: Members must be available for transfer (not suspended, injured, etc.)
- **Date Validation**: All operations must respect chronological order
- **Business Rule Compliance**: Operations must satisfy minimum member requirements
- **Transaction Integrity**: All database operations are wrapped in transactions

## Exception Handling

### Business Logic Exceptions
- `CannotBeEmployedException`, `CannotBeRetiredException`, `CannotBeSuspendedException`
- `CannotBeDebutedException`, `CannotBeActivatedException`
- `CannotBeMergedException`, `CannotBeSplitException`, `CannotBeReunitedException`
- `NotEnoughMembersException`, `MemberNotAvailableException`

### Exception Usage Pattern
```php
// Business rule violations
throw CannotBeEmployedException::alreadyEmployed($wrestler);
throw CannotBeRetiredException::hasActiveChampionship($wrestler, $title);
```

## Validation Method Placement

### Model vs Action Validation
**Rule**: Keep validation methods in appropriate classes

#### Model Methods (Boolean State Checking)
```php
// Models should only contain state checking methods
public function canBeEmployed(): bool
{
    return !$this->isRetired();
}
```

#### Action Methods (Business Rule Enforcement)
```php
// Actions should contain validation methods that throw exceptions
public function ensureCanBeEmployed(Wrestler $wrestler): void
{
    if (!$wrestler->canBeEmployed()) {
        throw CannotBeEmployedException::alreadyEmployed($wrestler);
    }
}
```

## Trait Status Methods

### Business Logic Over Enum Checks
**Rule**: Trait status methods should use business logic rather than direct enum checks

```php
// ✅ GOOD - Use business logic
public function isInactive(): bool
{
    return !$this->isCurrentlyActive();
}

public function isUnactivated(): bool
{
    return !$this->hasActivityPeriods();
}

// ❌ BAD - Direct enum check
public function isInactive(): bool
{
    return $this->status === ActivityStatus::Inactive;
}
```

## Related Documentation
- [Domain Structure](domain-structure.md)
- [Repository Pattern](repository-pattern.md)
- [Actions Pattern](actions-pattern.md)
- [Exception Handling](../security/exceptions.md)