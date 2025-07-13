# Project Conventions

Naming conventions, structural patterns, and project-specific guidelines for Ringside.

## Overview

This document establishes consistent conventions for naming, structure, and patterns across the Ringside codebase.

## Naming Conventions

### Class Naming

#### Models
- **Singular Form**: Use singular names for models
- **PascalCase**: Use PascalCase for all model names
- **Descriptive Names**: Names should clearly indicate the entity

```php
// ✅ CORRECT
class Wrestler extends Model {}
class TagTeam extends Model {}
class WrestlerEmployment extends Model {}
class TitleChampionship extends Model {}

// ❌ INCORRECT
class wrestlers extends Model {}           // Not singular
class tag_team extends Model {}           // Not PascalCase
class Emp extends Model {}                // Not descriptive
```

#### Actions
- **Verb + Noun**: Use action verb followed by subject noun
- **Suffix**: Always end with "Action"
- **Descriptive**: Name should clearly indicate the operation

```php
// ✅ CORRECT
class EmployWrestlerAction {}
class RetireWrestlerAction {}
class CreateStableAction {}
class MergeStablesAction {}

// ❌ INCORRECT
class WrestlerEmploy {}                   // Wrong order
class Employ {}                           // Missing noun
class WrestlerEmploymentCreator {}        // Wrong suffix
```

#### Repositories
- **Noun + Repository**: Entity name followed by "Repository"
- **Singular**: Use singular entity name
- **Interface**: Repository interfaces follow same pattern with "Interface" suffix

```php
// ✅ CORRECT
class WrestlerRepository {}
class TagTeamRepository {}
interface WrestlerRepositoryInterface {}

// ❌ INCORRECT
class WrestlersRepository {}              // Not singular
class WrestlerRepo {}                     // Abbreviated
class WrestlerService {}                  // Wrong suffix
```

#### Validation Rules
- **Descriptive Names**: Clearly indicate validation purpose
- **Domain Context**: Include domain context when needed
- **Verb Form**: Use verb forms for action-based validation

```php
// ✅ CORRECT
class IsActive {}
class IsBookable {}
class CanChangeEmploymentDate {}
class HasMinimumMembers {}

// ❌ INCORRECT
class Active {}                           // Not descriptive
class Validator {}                        // Too generic
class WrestlerBookable {}                 // Redundant domain
```

### File Naming

#### Directory Structure
- **Domain Organization**: Group files by domain/entity
- **Consistent Nesting**: Use consistent directory nesting
- **Pluralization**: Use plural for directories, singular for files

```
app/
├── Models/
│   ├── Wrestlers/
│   │   ├── Wrestler.php
│   │   ├── WrestlerEmployment.php
│   │   └── WrestlerRetirement.php
│   └── TagTeams/
│       ├── TagTeam.php
│       └── TagTeamWrestler.php
├── Actions/
│   ├── Wrestlers/
│   │   ├── EmployAction.php
│   │   └── RetireAction.php
│   └── TagTeams/
│       └── CreateAction.php
```

#### Test File Naming
- **Mirror App Structure**: Test files mirror app directory structure
- **Descriptive Suffixes**: Use descriptive suffixes for test types
- **Consistent Naming**: Follow established naming patterns

```
tests/
├── Unit/
│   ├── Models/
│   │   ├── Wrestlers/
│   │   │   └── WrestlerTest.php
│   │   └── TagTeams/
│   │       └── TagTeamTest.php
│   └── Rules/
│       └── Wrestlers/
│           └── IsBookableUnitTest.php
├── Integration/
│   ├── Actions/
│   │   └── Wrestlers/
│   │       └── EmployActionTest.php
│   └── Rules/
│       └── Wrestlers/
│           └── IsBookableIntegrationTest.php
```

### Database Naming

#### Table Names
- **Plural**: Use plural form for table names
- **Snake Case**: Use snake_case for all table names
- **Descriptive**: Include full entity names

```sql
-- ✅ CORRECT
CREATE TABLE wrestlers (...);
CREATE TABLE tag_teams (...);
CREATE TABLE wrestler_employments (...);
CREATE TABLE stable_members (...);

-- ❌ INCORRECT
CREATE TABLE Wrestlers (...);            -- Not snake_case
CREATE TABLE tagteams (...);             -- Missing underscore
CREATE TABLE emp (...);                  -- Abbreviated
```

#### Column Names
- **Snake Case**: Use snake_case for all column names
- **Descriptive**: Use full, descriptive names
- **Consistent Patterns**: Follow established patterns

```sql
-- ✅ CORRECT
CREATE TABLE wrestlers (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    hometown VARCHAR(255),
    height_feet INT,
    height_inches INT,
    weight INT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- ❌ INCORRECT
CREATE TABLE wrestlers (
    ID BIGINT PRIMARY KEY,                -- Not snake_case
    nm VARCHAR(255),                      -- Abbreviated
    heightFeet INT,                       -- camelCase
    wt INT                                -- Abbreviated
);
```

#### Foreign Key Naming
- **Singular + _id**: Use singular entity name with "_id" suffix
- **Consistent**: Follow same pattern across all tables
- **Descriptive**: Include full entity name

```sql
-- ✅ CORRECT
ALTER TABLE wrestler_employments 
    ADD CONSTRAINT fk_wrestler_employments_wrestler_id 
    FOREIGN KEY (wrestler_id) REFERENCES wrestlers(id);

-- ❌ INCORRECT
ALTER TABLE wrestler_employments 
    ADD CONSTRAINT fk_emp_wrestler 
    FOREIGN KEY (w_id) REFERENCES wrestlers(id);
```

## Structural Patterns

### Model Structure

#### Standard Model Layout
```php
<?php

declare(strict_types=1);

namespace App\Models\Wrestlers;

use App\Models\Concerns\IsEmployable;
use App\Models\Concerns\IsRetirable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Wrestler model for managing wrestler entities.
 */
class Wrestler extends Model
{
    use IsEmployable, IsRetirable, SoftDeletes;

    // Table configuration
    protected $table = 'wrestlers';
    
    // Mass assignment
    protected $fillable = [
        'name',
        'hometown',
        'height_feet',
        'height_inches',
        'weight',
    ];
    
    // Attribute casting
    protected $casts = [
        'height' => HeightValueObject::class,
        'weight' => 'integer',
    ];
    
    // Relationships
    public function currentEmployment(): HasOne
    {
        return $this->hasOne(WrestlerEmployment::class)
            ->whereNull('ended_at');
    }
    
    // Scopes
    public function scopeEmployed(Builder $query): Builder
    {
        return $query->whereHas('currentEmployment');
    }
    
    // Accessors & Mutators
    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }
    
    // Business methods
    public function isEmployed(): bool
    {
        return $this->currentEmployment !== null;
    }
}
```

#### Model Organization Order
1. **Use Statements**: Traits and imports
2. **Table Configuration**: Table name, primary key, etc.
3. **Mass Assignment**: Fillable/guarded properties
4. **Attribute Casting**: Casts array
5. **Relationships**: All relationship methods
6. **Scopes**: Query scopes
7. **Accessors/Mutators**: Attribute methods
8. **Business Methods**: Domain-specific methods

### Action Structure

#### Standard Action Layout
```php
<?php

declare(strict_types=1);

namespace App\Actions\Wrestlers;

use App\Models\Wrestlers\Wrestler;
use App\Repositories\WrestlerRepository;
use Illuminate\Support\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Action to employ a wrestler.
 */
class EmployAction
{
    use AsAction;
    
    public function __construct(
        private WrestlerRepository $repository
    ) {}
    
    public function handle(Wrestler $wrestler, ?Carbon $startDate = null): Wrestler
    {
        $startDate ??= now();
        
        $this->validateEmployment($wrestler, $startDate);
        
        return $this->repository->createEmployment($wrestler, $startDate);
    }
    
    private function validateEmployment(Wrestler $wrestler, Carbon $startDate): void
    {
        if ($wrestler->isEmployed()) {
            throw CannotBeEmployedException::alreadyEmployed($wrestler);
        }
        
        if ($wrestler->isRetired()) {
            throw CannotBeEmployedException::isRetired($wrestler);
        }
    }
}
```

#### Action Organization Order
1. **Use Statements**: Imports and dependencies
2. **Constructor**: Dependency injection
3. **Handle Method**: Main action logic
4. **Validation Methods**: Private validation logic
5. **Helper Methods**: Supporting functionality

### Repository Structure

#### Standard Repository Layout
```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Wrestlers\Wrestler;
use App\Repositories\Concerns\ManagesEmployment;
use App\Repositories\Concerns\ManagesRetirement;
use Illuminate\Support\Carbon;

/**
 * Repository for wrestler operations.
 */
class WrestlerRepository implements WrestlerRepositoryInterface
{
    use ManagesEmployment, ManagesRetirement;
    
    public function __construct(
        private Wrestler $wrestler
    ) {}
    
    public function create(array $data): Wrestler
    {
        return $this->wrestler->create($data);
    }
    
    public function update(Wrestler $wrestler, array $data): Wrestler
    {
        $wrestler->update($data);
        return $wrestler->fresh();
    }
    
    public function delete(Wrestler $wrestler): bool
    {
        return $wrestler->delete();
    }
    
    public function restore(Wrestler $wrestler): bool
    {
        return $wrestler->restore();
    }
}
```

## Business Rule Conventions

### Status Management

#### Employment Status
- **Employed**: Currently has active employment
- **Unemployed**: No current employment, available for hiring
- **Released**: Recently terminated employment
- **Future Employment**: Scheduled future employment

#### Activity Status
- **Active**: Currently participating in activities
- **Inactive**: Not currently participating
- **Suspended**: Temporarily barred from activities
- **Retired**: Permanently ended career

#### Injury Status
- **Healthy**: No current injuries
- **Injured**: Currently injured and unavailable
- **Recovering**: In recovery process
- **Cleared**: Recently cleared from injury

### Capability Matrix

#### Entity Capabilities
Different entities have different capabilities based on business rules:

| Entity | Employment | Injury | Suspension | Retirement | Booking | Debut |
|--------|------------|--------|------------|------------|---------|-------|
| Wrestler | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| Manager | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| Referee | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| TagTeam | ✅ | ❌ | ✅ | ✅ | ✅ | ❌ |
| Stable | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ |
| Title | ❌ | ❌ | ❌ | ✅ | ❌ | ✅ |

### Relationship Conventions

#### Membership Relationships
- **Stable Members**: Only Wrestlers and TagTeams can be direct members
- **Tag Team Members**: Only Wrestlers can be tag team members
- **Manager Relationships**: Managers can manage Wrestlers and TagTeams
- **Championship Relationships**: Wrestlers and TagTeams can hold titles

#### Time-Based Relationships
- **Start/End Dates**: All relationships have start and optional end dates
- **Current vs Historical**: Distinguish current from historical relationships
- **Overlap Handling**: Handle overlapping relationship periods appropriately

## Code Organization Patterns

### Trait Usage

#### Status Management Traits
```php
// ✅ CORRECT - Focused trait with clear purpose
trait IsEmployable
{
    public function isEmployed(): bool
    {
        return $this->currentEmployment !== null;
    }
    
    public function employments(): HasMany
    {
        return $this->hasMany($this->resolveEmploymentModelClass());
    }
    
    public function currentEmployment(): HasOne
    {
        return $this->hasOne($this->resolveEmploymentModelClass())
            ->whereNull('ended_at');
    }
    
    private function resolveEmploymentModelClass(): string
    {
        $baseClass = class_basename($this);
        $namespace = 'App\\Models\\' . Str::plural($baseClass);
        
        return $namespace . '\\' . $baseClass . 'Employment';
    }
}
```

#### Trait Organization
- **Single Responsibility**: Each trait has one clear purpose
- **Model Resolution**: Traits resolve related model classes dynamically
- **Consistent Naming**: Follow established naming patterns
- **Interface Compliance**: Traits implement appropriate interfaces

### Exception Handling

#### Custom Exception Structure
```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\Model;

/**
 * Exception thrown when entity cannot be employed.
 */
class CannotBeEmployedException extends Exception
{
    public static function alreadyEmployed(Model $entity): self
    {
        $name = $entity->getDisplayName();
        return new self("Cannot employ {$name} - already employed.");
    }
    
    public static function isRetired(Model $entity): self
    {
        $name = $entity->getDisplayName();
        return new self("Cannot employ {$name} - currently retired.");
    }
    
    public static function isInjured(Model $entity): self
    {
        $name = $entity->getDisplayName();
        return new self("Cannot employ {$name} - currently injured.");
    }
}
```

#### Exception Organization
- **Domain-Specific**: Organize exceptions by domain
- **Static Factories**: Use static factory methods for consistent creation
- **Clear Messages**: Provide user-friendly error messages
- **Contextual Information**: Include relevant entity information

## Testing Conventions

### Test Structure

#### Standard Test Layout
```php
<?php

declare(strict_types=1);

use App\Models\Wrestlers\Wrestler;
use App\Actions\Wrestlers\EmployAction;

/**
 * Unit tests for Wrestler model structure and configuration.
 */
describe('Wrestler Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        test('has correct fillable properties', function () {
            // Arrange
            $wrestler = new Wrestler();
            
            // Act
            $fillable = $wrestler->getFillable();
            
            // Assert
            expect($fillable)->toEqual([
                'name',
                'hometown',
                'height_feet',
                'height_inches',
                'weight',
            ]);
        });
    });
    
    describe('trait integration', function () {
        test('uses required traits', function () {
            expect(Wrestler::class)->usesTrait(IsEmployable::class);
            expect(Wrestler::class)->usesTrait(IsRetirable::class);
        });
    });
});
```

#### Test Organization
- **Describe Blocks**: Use describe blocks for logical grouping
- **AAA Pattern**: Arrange-Act-Assert with clear separation
- **Descriptive Names**: Test names explain expected behavior
- **Consistent Structure**: Follow established patterns

### Factory Conventions

#### Factory Structure
```php
<?php

declare(strict_types=1);

namespace Database\Factories\Wrestlers;

use App\Models\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for creating Wrestler instances.
 */
class WrestlerFactory extends Factory
{
    protected $model = Wrestler::class;
    
    public function definition(): array
    {
        return [
            'name' => $this->faker->firstName() . ' ' . $this->faker->lastName(),
            'hometown' => $this->faker->city() . ', ' . $this->faker->stateAbbr(),
            'height_feet' => $this->faker->numberBetween(5, 7),
            'height_inches' => $this->faker->numberBetween(0, 11),
            'weight' => $this->faker->numberBetween(150, 350),
        ];
    }
    
    public function employed(): static
    {
        return $this->afterCreating(function (Wrestler $wrestler) {
            $wrestler->employments()->create([
                'started_at' => now()->subMonth(),
                'ended_at' => null,
            ]);
        });
    }
    
    public function retired(): static
    {
        return $this->afterCreating(function (Wrestler $wrestler) {
            $wrestler->retirements()->create([
                'started_at' => now()->subWeek(),
                'ended_at' => null,
            ]);
        });
    }
}
```

#### Factory Organization
- **Realistic Data**: Use realistic data for business domain
- **State Methods**: Provide state methods for common scenarios
- **Relationship Handling**: Handle relationships appropriately
- **Consistent Patterns**: Follow established factory patterns

## Documentation Conventions

### Code Documentation

#### PHPDoc Standards
```php
/**
 * Action to employ a wrestler with validation and business rules.
 *
 * This action handles the complete employment process including:
 * - Validation of current employment status
 * - Retirement status checking
 * - Employment record creation
 * - Status transition management
 *
 * @see WrestlerRepository For employment data persistence
 * @see CannotBeEmployedException For employment validation errors
 */
class EmployWrestlerAction
{
    /**
     * Execute the employment action.
     *
     * @param Wrestler $wrestler The wrestler to employ
     * @param Carbon|null $startDate Employment start date (defaults to now)
     * @return Wrestler The wrestler with updated employment status
     * @throws CannotBeEmployedException If wrestler cannot be employed
     */
    public function handle(Wrestler $wrestler, ?Carbon $startDate = null): Wrestler
    {
        // Implementation
    }
}
```

#### Documentation Organization
- **Class Purpose**: Explain what the class does and why
- **Method Purpose**: Describe method behavior and return values
- **Parameter Documentation**: Document all parameters
- **Exception Documentation**: Document thrown exceptions
- **Cross-References**: Link to related classes and methods

### README Documentation

#### Project Documentation Structure
```markdown
# Component Name

Brief description of the component and its purpose.

## Usage

Basic usage examples with code samples.

## Configuration

Configuration options and setup instructions.

## Examples

Detailed examples showing common usage patterns.

## Testing

Information about testing the component.

## Contributing

Guidelines for contributing to the component.
```

This comprehensive conventions guide ensures consistent patterns and practices across the entire Ringside codebase.