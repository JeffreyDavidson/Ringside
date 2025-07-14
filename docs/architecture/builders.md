# Builder Architecture

## Overview

Ringside uses a comprehensive builder pattern for constructing complex Eloquent queries with wrestling-specific business logic. All builders are organized by domain in `app/Builders/{Domain}/` and extend Laravel's base query builder functionality.

## Builder Organization

### Domain Structure
```
app/Builders/
├── Concerns/           # Shared builder traits and contracts
├── Contracts/          # Builder interfaces
├── Events/            # Event and venue builders
├── Roster/            # Wrestling roster member builders  
├── Titles/            # Title and championship builders
└── Users/             # User builders
```

### Builder Hierarchy

**Base Builders:**
- `SingleRosterMemberBuilder` - Base for individual roster members (wrestlers, managers, referees)
- `BaseRepository` - Foundation for repository pattern implementation

**Domain-Specific Builders:**
- `WrestlerBuilder` - Wrestler-specific query logic
- `ManagerBuilder` - Manager-specific query logic  
- `RefereeBuilder` - Referee-specific query logic
- `TagTeamBuilder` - Tag team query logic
- `StableBuilder` - Stable query logic
- `TitleBuilder` - Title query logic
- `EventBuilder` - Event query logic
- `VenueBuilder` - Venue query logic
- `UserBuilder` - User query logic

## Builder Capabilities

### Employment Status Scopes
```php
// Available in roster member builders
$wrestlers = Wrestler::query()
    ->employed()           // Currently employed
    ->available()          // Available for booking
    ->unemployed()         // Not currently employed
    ->get();
```

### Availability Scopes
```php
// Booking availability logic
$availableWrestlers = Wrestler::query()
    ->bookable()           // Can be booked for matches
    ->notInjured()         // Not currently injured
    ->notSuspended()       // Not currently suspended
    ->get();
```

### Activity Period Scopes
```php
// Historical data queries
$activeStables = Stable::query()
    ->currentlyActive()    // Active right now
    ->activeDuring($start, $end)  // Active during period
    ->debutedAfter($date)  // Debuted after date
    ->get();
```

### Retirement Scopes
```php
// Retirement status queries
$activeWrestlers = Wrestler::query()
    ->notRetired()         // Currently active
    ->retiredAfter($date)  // Retired after specific date
    ->get();
```

## Builder Traits and Concerns

### HasAvailabilityScopes
Provides booking availability logic for entities that can be scheduled for matches.

**Methods:**
- `available()` - Available for booking
- `unavailable()` - Not available for booking
- `bookable()` - Can be booked (employed + available + not injured/suspended)

### HasRetirementScopes  
Provides retirement status filtering for entities that can be retired.

**Methods:**
- `retired()` - Currently retired
- `notRetired()` - Not retired (active)
- `retiredBetween($start, $end)` - Retired within date range

## Builder Contracts

### HasAvailability
Defines availability-related query methods for bookable entities.

### HasEmployment
Defines employment status query methods for employable entities.

### HasRetirement
Defines retirement status query methods for retirable entities.

## Usage Examples

### Complex Wrestler Queries
```php
// Find wrestlers available for a championship match
$championshipContenders = Wrestler::query()
    ->employed()
    ->bookable()
    ->notCurrentChampion($titleId)
    ->hasMinimumExperience(6) // months
    ->orderByExperience('desc')
    ->limit(5)
    ->get();
```

### Stable Membership Queries
```php
// Find stables with available members for events
$availableStables = Stable::query()
    ->currentlyActive()
    ->hasAvailableMembers()
    ->whereHas('wrestlers', function ($query) {
        $query->bookable()->count('>=', 2);
    })
    ->get();
```

### Historical Analysis
```php
// Championship analysis
$titleReigns = Title::query()
    ->activeDuring($year)
    ->with(['championships' => function ($query) use ($year) {
        $query->activeDuring($year)
              ->with('champion')
              ->orderBy('started_at');
    }])
    ->get();
```

## Builder Best Practices

### 1. Domain Organization
- Keep builders in appropriate domain directories
- Use descriptive names that match the model's purpose
- Extend appropriate base builders for shared functionality

### 2. Scope Naming
- Use clear, business-focused method names
- Prefix with context when needed (`currently`, `not`, `has`)
- Group related scopes in traits for reusability

### 3. Query Optimization
- Use eager loading for related models
- Implement database indexes for commonly queried fields
- Consider query caching for expensive operations

### 4. Business Logic Integration
- Encode wrestling business rules in builder methods
- Use scopes to ensure data consistency
- Validate business constraints in builder logic

## Testing Builders

### Unit Testing
```php
test('wrestler builder filters by employment status', function () {
    $employedWrestler = Wrestler::factory()->employed()->create();
    $unemployedWrestler = Wrestler::factory()->unemployed()->create();
    
    $employed = Wrestler::query()->employed()->get();
    
    expect($employed)->toContain($employedWrestler)
                     ->not->toContain($unemployedWrestler);
});
```

### Integration Testing
```php
test('complex availability queries work correctly', function () {
    $availableWrestler = Wrestler::factory()
        ->employed()
        ->notInjured()
        ->notSuspended()
        ->create();
        
    $bookableWrestlers = Wrestler::query()->bookable()->get();
    
    expect($bookableWrestlers)->toContain($availableWrestler);
});
```

## Architecture Benefits

1. **Separation of Concerns**: Business logic stays in builders, not controllers
2. **Reusability**: Common query patterns shared across the application  
3. **Testability**: Complex queries can be unit tested in isolation
4. **Maintainability**: Wrestling business rules centralized in builders
5. **Performance**: Optimized queries with proper eager loading and indexes