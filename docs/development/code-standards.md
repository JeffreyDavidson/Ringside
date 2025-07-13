# Code Standards & Conventions

This document outlines the coding standards and conventions used in the Ringside application.

## PHP Standards

### Strict Types
- All PHP files must declare strict types: `declare(strict_types=1);`
- No exceptions - this enforces type safety throughout the application

### Code Quality Requirements
- **Laravel Pint**: Use custom rules defined in `pint.json`
- **PHPStan**: Level 6 with 100% type coverage requirement
- **Comprehensive PHPDoc**: All classes and methods must have proper documentation

### Import Standards
- **Import Classes for DocBlocks**: Always import classes when adding docblocks or generic type hints
- **Never use FQCN in DocBlocks**: Import classes instead of using fully qualified class names
- **Test Files**: Always import classes instead of using FQCN in test files

```php
// ✅ CORRECT - Import the class
use App\Models\Wrestlers\Wrestler;
use App\Actions\Wrestlers\EmployAction;

test('can employ wrestler', function () {
    $wrestler = Wrestler::factory()->create();
    EmployAction::run($wrestler, now());
});

// ❌ INCORRECT - Using FQCN
test('can employ wrestler', function () {
    $wrestler = \App\Models\Wrestlers\Wrestler::factory()->create();
    \App\Actions\Wrestlers\EmployAction::run($wrestler, now());
});
```

## Model Attribute Naming Conventions

### Time-Based Tracking Models
**CRITICAL RULE**: All time-based tracking models use consistent field naming:

#### Start Fields
Always use `started_at` (never use context-specific names):
- ❌ `injured_at`, `suspended_at`, `retired_at`, `employed_at`
- ✅ `started_at` for all tracking models

#### End Fields  
Always use `ended_at` (never use context-specific names):
- ❌ `cleared_at`, `reinstated_at`, `unretired_at`, `released_at`
- ✅ `ended_at` for all tracking models

#### Examples
```php
// ✅ CORRECT - Consistent naming
class WrestlerInjury extends Model
{
    protected $fillable = [
        'wrestler_id',
        'started_at',    // When injury began
        'ended_at',      // When injury was cleared
    ];
}

class ManagerSuspension extends Model  
{
    protected $fillable = [
        'manager_id',
        'started_at',    // When suspension began
        'ended_at',      // When suspension ended
    ];
}

// ❌ INCORRECT - Inconsistent naming
class WrestlerInjury extends Model
{
    protected $fillable = [
        'wrestler_id',
        'injured_at',    // Inconsistent with other tracking models
        'cleared_at',    // Inconsistent with other tracking models
    ];
}
```

#### Rationale
- **Simplified Queries**: Common field names enable generic query building
- **Trait Compatibility**: Shared traits work across all tracking models
- **Reduced Complexity**: Business logic doesn't need model-specific field handling
- **Consistent API**: All time-based tracking models behave identically

### Individual People vs Entity Models

#### Individual People Models
Use separate name fields:
- **Models**: Managers, Referees
- **Fields**: `first_name`, `last_name`
- **Rationale**: People have structured names for formal documentation

#### Entity Models
Use single name field:
- **Models**: Wrestlers, TagTeams, Stables, Events, Venues, Titles
- **Fields**: `name`
- **Rationale**: Entities have single identifying names

#### Address Models
Use structured address fields:
- **Models**: Venues
- **Fields**: `street_address`, `city`, `state`, `zipcode`
- **Rationale**: Complete address information needed for event management

## Trait Design Standards

### Method Duplication Prevention
- **Never Duplicate Methods**: Same method should never exist in multiple traits
- **Use Public APIs**: Traits should call public methods from other traits
- **Centralize Common Functionality**: Place methods in the most appropriate trait
- **Eliminate Conflicts**: Remove duplication rather than using `insteadof`

### Trait Status Methods
Use business logic rather than direct enum checks:

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

## Interface-Based Architecture

### Replace method_exists() with Type-Safe Interfaces
**CRITICAL RULE**: Never use `method_exists()` - use interface contracts instead

```php
// ✅ CORRECT - Type-safe interface checking
if ($entity instanceof ProvidesCurrentWrestlers) {
    $wrestlers = $entity->currentWrestlers()->get();
}

if ($entity instanceof Validatable) {
    $canRetire = $entity->canBeRetired();
}

// ❌ INCORRECT - Runtime method checking
if (method_exists($entity, 'currentWrestlers')) {
    $wrestlers = $entity->currentWrestlers()->get();
}

if (method_exists($entity, 'canBeRetired')) {
    $canRetire = $entity->canBeRetired();
}
```

### Interface Naming Conventions
- `Has*` - For entities that possess or manage something
- `Can*` - For entities that have capabilities  
- `Is*` - For entities with status/state
- `Provides*` - For marker interfaces that expose specific methods

## Validation Method Placement

### Model vs Action Validation
**CRITICAL RULE**: Keep validation methods in appropriate classes

#### Model Methods (Boolean State Checking)
Models should only contain state checking methods that return boolean values:

```php
// ✅ CORRECT - Model state checking method
public function canBeEmployed(): bool
{
    return !$this->isRetired();
}
```

#### Action Methods (Business Rule Enforcement)  
Actions should contain validation methods that throw exceptions:

```php
// ✅ CORRECT - Action validation with exception
public function ensureCanBeEmployed(Wrestler $wrestler): void
{
    if (!$wrestler->canBeEmployed()) {
        throw CannotBeEmployedException::alreadyEmployed($wrestler);
    }
}
```

**Rule**: Methods following the `ensureCanBeXXXXX()` naming convention should NOT be placed on model classes - they belong in Action classes.

## Modern Laravel Features

### Attributes Style
Use modern Laravel attribute patterns instead of legacy methods:

```php
// ✅ MODERN - Use Attribute class
use Illuminate\Database\Eloquent\Casts\Attribute;

protected function formattedPhoneNumber(): Attribute
{
    return Attribute::make(
        get: fn (mixed $value, array $attributes) => 
            '(' . substr($attributes['phone'], 0, 3) . ') ' . 
            substr($attributes['phone'], 3, 3) . '-' . 
            substr($attributes['phone'], 6)
    );
}

// ❌ LEGACY - Avoid get/set methods
public function getFormattedPhoneNumberAttribute(): string
{
    return '(' . substr($this->phone, 0, 3) . ') ' . 
           substr($this->phone, 3, 3) . '-' . 
           substr($this->phone, 6);
}
```

## Security Standards

### Secret Management
- **Never expose secrets**: No secrets or keys in code or logs
- **Never commit secrets**: Secrets must not be committed to repository
- **Use environment variables**: All sensitive data in `.env` files
- **Validate input**: Always validate and sanitize user input

## Code Quality Enforcement

### Pre-commit Requirements
1. `composer test` - All tests must pass
2. `composer lint` - Code formatting must be correct
3. `composer rector` - Code modernization applied
4. `composer test:type-coverage` - 100% type coverage maintained

### Documentation Requirements
- **PHPDoc blocks**: All public methods must have proper documentation
- **Type hints**: All parameters and return types must be declared
- **Comments**: Complex business logic must be commented
- **Avoid over-commenting**: Don't add comments unless requested

## Testing Standards

### Test File Standards
- **Import classes**: Always import classes instead of using FQCN
- **AAA Pattern**: Arrange-Act-Assert with clear comment blocks
- **Realistic data**: Use business-appropriate factory data
- **Named routes**: Use Laravel's named routes instead of hardcoded URLs

### Factory Test Field References
Always use correct field names in factory tests:

```php
// ✅ CORRECT - Use standardized field names
expect($injury->started_at)->toBeInstanceOf(Carbon::class);
expect($injury->ended_at)->toBeNull();

// ❌ INCORRECT - Don't use context-specific names
expect($injury->injured_at)->toBeInstanceOf(Carbon::class);
expect($injury->cleared_at)->toBeNull();
```

## Related Documentation
- [Business Rules](../architecture/business-rules.md)
- [Development Commands](commands.md)
- [Factory Testing Guidelines](../testing/factory-testing.md)
- [Testing Overview](../testing/overview.md)