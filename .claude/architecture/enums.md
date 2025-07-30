# Enum Architecture

## Overview

Ringside uses a domain-organized enum structure to maintain type safety and consistency across the wrestling management system. All enums are organized by domain with shared enums for cross-domain usage.

## Enum Organization

### Domain Structure
```
app/Enums/
├── Shared/                 # Cross-domain enums
│   ├── ActivationStatus.php
│   ├── EmploymentStatus.php
│   └── RosterMemberType.php
├── Events/                 # Event-specific enums
├── Stables/               # Stable-specific enums
│   ├── StableMemberType.php
│   ├── StableMembershipAction.php
│   └── StableStatus.php
├── Titles/                # Title-specific enums
│   ├── TitleStatus.php
│   └── TitleType.php
└── Users/                 # User-specific enums
    ├── Role.php
    └── UserStatus.php
```

### Architecture Principles

1. **Domain Organization**: Enums are grouped by business domain
2. **Shared Resources**: Common enums live in `Shared/` directory
3. **No Root Enums**: All enums must be domain-organized (root-level enums removed)
4. **Type Safety**: Enums provide compile-time type checking
5. **Business Logic**: Enums contain presentation and validation logic

## Enum Categories

### Shared Enums

#### ActivationStatus
Used for entities that can be activated/deactivated (stables, titles).

```php
enum ActivationStatus: string
{
    case Unactivated = 'unactivated';      // Never been activated
    case FutureActivation = 'future_activation'; // Scheduled activation
    case Active = 'active';                 // Currently active
    case Inactive = 'inactive';             // Temporarily inactive
    case Retired = 'retired';               // Permanently retired
}
```

**Usage:**
- Stables: Activation lifecycle management
- General activation patterns across domains

#### EmploymentStatus
Used for entities with employment relationships (wrestlers, managers, referees, tag teams).

```php
enum EmploymentStatus: string
{
    case Employed = 'employed';             // Currently employed
    case FutureEmployment = 'future_employment'; // Scheduled employment
    case Released = 'released';             // Contract released
    case Retired = 'retired';               // Permanently retired
    case Unemployed = 'unemployed';         // Not currently employed
}
```

**Usage:**
- Pure employment contract states
- Separate from availability/booking status
- Used across roster member types

### Domain-Specific Enums

#### Title Status (Titles/TitleStatus.php)
Wrestling-specific status for championship titles.

```php
enum TitleStatus: string
{
    case Undebuted = 'undebuted';           // Title exists but never debuted
    case PendingDebut = 'pending_debut';    // Scheduled to debut
    case Active = 'active';                 // Currently active/defendable
    case Inactive = 'inactive';             // Temporarily out of circulation
}
```

**Key Differences from ActivationStatus:**
- Wrestling-specific terminology ("debuted" vs "activated")
- Title-specific business logic
- Championship-focused workflow states

#### Title Type (Titles/TitleType.php)
Defines championship title categories.

```php
enum TitleType: string
{
    case Singles = 'singles';               // Individual championship
    case TagTeam = 'tag-team';             // Tag team championship
}
```

#### User Role (Users/Role.php)
System access levels for users.

```php
enum Role: string
{
    case Administrator = 'administrator';    // Full system access
    case Basic = 'basic';                   // Limited access
}
```

#### User Status (Users/UserStatus.php)
User account states.

```php
enum UserStatus: string
{
    case Unverified = 'unverified';        // Email not verified
    case Active = 'active';                // Active account
    case Inactive = 'inactive';            // Suspended account
}
```

## Enum Usage Guidelines

### Import Patterns

**Correct Usage:**
```php
use App\Enums\Shared\EmploymentStatus;
use App\Enums\Titles\TitleStatus;
use App\Enums\Users\Role;
```

**Incorrect Usage:**
```php
use App\Enums\ActivationStatus;  // ❌ Root-level enum removed
use App\Enums\Role;              // ❌ Root-level enum removed
```

### Model Integration

#### Enum Casting
```php
class Wrestler extends Model
{
    protected $casts = [
        'status' => EmploymentStatus::class,
    ];
}
```

#### Factory Usage
```php
class WrestlerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'status' => EmploymentStatus::Unemployed,
        ];
    }
    
    public function employed(): static
    {
        return $this->state(['status' => EmploymentStatus::Employed]);
    }
}
```

#### Blade Templates
```php
<!-- Correct enum reference -->
<x-tables.meta-data enum="\App\Enums\Titles\TitleStatus" />
<x-tables.meta-data enum="\App\Enums\Shared\ActivationStatus" />
```

### Business Logic Methods

All enums provide standardized methods for presentation and logic:

```php
// Label for display
$status->label();  // "Currently Employed"

// Color for UI styling  
$status->color();  // "success"

// Custom business logic
$status->isActive();
$status->canTransitionTo($newStatus);
```

## Enum Best Practices

### 1. Domain Placement
- Place enums in appropriate domain directories
- Use `Shared/` for cross-domain enums
- Never create root-level enums

### 2. Naming Conventions
- Use descriptive enum names that reflect business concepts
- Case names should be PascalCase
- String values should be snake_case

### 3. Documentation
- Include docblocks explaining business context
- Document enum case meanings
- Provide usage examples

### 4. Backward Compatibility
- Never remove enum cases (mark as deprecated instead)
- Add new cases at the end
- Consider migration impact when changing values

## Testing Enums

### Unit Testing
```php
test('employment status provides correct labels', function () {
    expect(EmploymentStatus::Employed->label())->toBe('Employed');
    expect(EmploymentStatus::Released->label())->toBe('Released');
});

test('title status color coding works', function () {
    expect(TitleStatus::Active->color())->toBe('bg-green-600 text-white');
    expect(TitleStatus::Inactive->color())->toBe('bg-yellow-500 text-black');
});
```

### Integration Testing
```php
test('wrestler factory creates correct employment status', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    
    expect($wrestler->status)->toBe(EmploymentStatus::Employed);
    expect($wrestler->status->label())->toBe('Employed');
});
```

## Migration from Root Enums

During the enum cleanup process, all root-level enums were removed and replaced with domain-organized versions:

### Removed Enums
- ✅ `App\Enums\TitleType` → `App\Enums\Titles\TitleType`
- ✅ `App\Enums\ActivationStatus` → `App\Enums\Shared\ActivationStatus`
- ✅ `App\Enums\EmploymentStatus` → `App\Enums\Shared\EmploymentStatus`
- ✅ `App\Enums\Role` → `App\Enums\Users\Role`
- ✅ `App\Enums\UserStatus` → `App\Enums\Users\UserStatus`

### Updated Components
- ✅ Factory imports updated to use domain-organized enums
- ✅ Test files updated with correct enum references
- ✅ Blade templates updated with proper enum paths
- ✅ Title status architecture unified (TitleFactory → TitleStatus)

## Architecture Benefits

1. **Type Safety**: Compile-time enum validation prevents invalid states
2. **Business Logic Encapsulation**: Enum methods contain domain logic
3. **Consistency**: Standardized status patterns across domains
4. **Maintainability**: Domain organization makes enums easy to find
5. **Extensibility**: New enum cases can be added without breaking changes
6. **Testing**: Enum behavior can be thoroughly unit tested