# Unit Testing Standards

Guidelines for writing unit tests that focus on individual classes and methods in isolation.

## Overview

Unit tests form the foundation of Ringside's testing pyramid (70% of test suite). They test individual classes, methods, and business logic in complete isolation from external dependencies.

## Unit Test Scope

### Purpose
- **Isolation**: Test single class or method without external dependencies
- **Logic Testing**: Focus on business logic, calculations, and algorithms
- **Fast Execution**: Tests should run quickly without database or network calls
- **Reliability**: Consistent results regardless of external state

### Location
- **Directory**: `tests/Unit/`
- **Structure**: Must exactly mirror source directory structure
- **Naming**: `{ClassName}Test.php` for unit tests
- **Factory Tests**: `tests/Unit/Database/Factories/{Directory}/{ClassName}FactoryTest.php`
- **Excluded**: Database seeder tests (moved to Integration tests)

## Model Testing Standards

### Required Test Structure
Every model test MUST contain exactly these 5 describe blocks:

```php
describe('ModelName Model Unit Tests', function () {
    describe('model attributes and configuration', function () {
        // Test fillable, casts, table name, defaults, builder
    });

    describe('trait integration', function () {
        // Test all traits used by model
    });

    describe('interface implementation', function () {
        // Test all interfaces implemented
    });

    describe('model constants', function () {
        // Test any model-specific constants
    });

    describe('business logic methods', function () {
        // Test existence of key business methods
    });
});
```

### What TO Test in Model Tests
- **Fillable Properties**: `getFillable()` returns correct array
- **Casts**: `getCasts()` returns correct casting configuration
- **Custom Builder**: `query()` returns correct builder instance
- **Trait Usage**: Model uses expected traits via `usesTrait()`
- **Default Values**: Default attribute values are set correctly
- **Basic Attributes**: Value objects and casting work correctly

### What NOT to Test in Model Tests
- Business logic methods (`ensureCanBeXXX()`, `canBeXXX()`) - test in Actions
- Validation rules enforcement - test in Form/Action tests
- Complex business workflows - test in Integration tests
- Trait functionality itself - test traits independently
- Database operations - test in Repository tests
- Factory-related code - test in separate Factory tests
- **Database seeders** - test in Integration tests at `tests/Integration/Database/Seeders/`

### Example Model Test
```php
<?php

declare(strict_types=1);

use App\Models\Wrestlers\Wrestler;
use App\Builders\WrestlerBuilder;

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

        test('has correct casts configuration', function () {
            // Arrange
            $wrestler = new Wrestler();
            
            // Act
            $casts = $wrestler->getCasts();
            
            // Assert
            expect($casts['height'])->toBe(HeightValueObject::class);
            expect($casts['weight'])->toBe('integer');
        });

        test('has custom eloquent builder', function () {
            // Arrange
            $wrestler = new Wrestler();
            
            // Act
            $builder = $wrestler->query();
            
            // Assert
            expect($builder)->toBeInstanceOf(WrestlerBuilder::class);
        });
    });

    describe('trait integration', function () {
        test('uses all required traits', function () {
            expect(Wrestler::class)->usesTrait(IsEmployable::class);
            expect(Wrestler::class)->usesTrait(IsRetirable::class);
            expect(Wrestler::class)->usesTrait(HasFactory::class);
        });
    });
});
```

## Repository Testing Standards

### Repository Test Structure
```php
describe('EntityRepository Unit Tests', function () {
    beforeEach(function () {
        $this->repository = app(EntityRepository::class);
    });

    describe('repository configuration', function () {
        // Test DI container resolution, interface implementation
    });

    describe('core CRUD operations', function () {
        // Test create, update, restore operations
    });

    describe('employment management', function () {
        // Test employment-related operations (if applicable)
    });

    describe('relationship management', function () {
        // Test entity-specific relationship methods
    });
});
```

### Repository Testing Categories
- **Employment Management**: `createEmployment()`, `createRelease()`
- **Retirement Management**: `createRetirement()`, `endRetirement()`
- **Suspension Management**: `createSuspension()`, `endSuspension()`
- **Injury Management**: `createInjury()`, `endInjury()`
- **Relationship Management**: Entity-specific relationship operations

## Action Testing Standards

### Action Test Organization
```php
describe('ActionName Unit Tests', function () {
    beforeEach(function () {
        Event::fake();
        testTime()->freeze();
        $this->entityRepository = $this->mock(EntityRepository::class);
    });

    describe('action workflow for state A', function () {
        beforeEach(function () {
            $this->entityInStateA = Entity::factory()->stateA()->create();
        });

        test('performs action at current datetime by default', function () {
            // Arrange
            $datetime = now();
            $this->setupMocksForStateA($this->entityInStateA, $datetime);

            // Act
            resolve(ActionName::class)->handle($this->entityInStateA);

            // Assert - Mock expectations automatically verified
        });
    });
});
```

### Action Testing Patterns
- **Mock Setup**: Use helper methods for complex mock configurations
- **State-Based Testing**: Group tests by entity state
- **Exception Testing**: Test business rule violations
- **Date Handling**: Test both current and specific datetime scenarios

## Validation Rule Testing

### Unit Test Approach (Pure Logic)
```php
describe('RuleName Validation Rule Unit Tests', function () {
    test('validation passes when condition is met', function () {
        // Arrange
        $rule = new RuleName($dependencies);
        $failCalled = false;
        $failCallback = function () use (&$failCalled) {
            $failCalled = true;
        };

        // Act
        $rule->validate('attribute', $value, $failCallback);

        // Assert
        expect($failCalled)->toBeFalse();
    });
});
```

### Validation Rule Categories
- **Category A**: Pure Logic Rules (CompetitorsNotDuplicated, HasMinimumMembers)
- **Category B**: Simple Model Interaction (DateCanBeChanged, CanChangeEmploymentDate)
- **Category C**: Database-Heavy Rules (moved to Integration tests)

## Policy Testing Standards

### Policy Test Structure
```php
describe('EntityPolicy Unit Tests', function () {
    beforeEach(function () {
        $this->policy = new EntityPolicy();
        $this->admin = administrator();
        $this->basicUser = basicUser();
    });

    describe('before hook behavior', function () {
        test('administrators bypass all authorization checks', function () {
            // Test admin bypass
        });

        test('basic users continue to individual method checks', function () {
            // Test basic user flow
        });
    });
});
```

### Policy Testing Requirements
- **Before Hook**: Test administrator bypass functionality
- **Individual Methods**: Test all policy methods return false for basic users
- **Laravel Gate**: Test Gate facade integration
- **Consistency**: Test all methods follow same pattern

## Factory Testing Standards

### Factory Test Structure
Factory tests must mirror the `database/factories/` directory structure exactly:

```
database/factories/Wrestlers/WrestlerFactory.php
→ tests/Unit/Database/Factories/Wrestlers/WrestlerFactoryTest.php

database/factories/Events/EventFactory.php
→ tests/Unit/Database/Factories/Events/EventFactoryTest.php
```

### Standard Factory Test Template
```php
describe('ModelFactory Unit Tests', function () {
    describe('default attribute generation', function () {
        test('creates model with correct default attributes', function () {
            // Arrange & Act
            $model = ModelFactory::new()->make();
            
            // Assert
            expect($model->attribute)->toBeBetween(100, 500);
            expect($model->status)->toBe(DefaultStatus::Active);
        });

        test('generates realistic data', function () {
            // Test realistic data generation
        });
    });
    
    describe('factory state methods', function () {
        test('state method works correctly', function () {
            // Arrange & Act
            $employed = ModelFactory::new()->employed()->make();
            
            // Assert
            expect($employed->status)->toBe(EmploymentStatus::Employed);
        });
    });

    describe('factory customization', function () {
        test('accepts custom attribute overrides', function () {
            // Test custom attribute handling
        });
    });

    describe('data consistency', function () {
        test('database creation works correctly', function () {
            // Test database persistence
        });
    });
});
```

### Factory Testing Focus
- **Default Attributes**: Test factory generates appropriate defaults
- **State Methods**: Test all factory states work correctly
- **Relationships**: Test factory relationship creation
- **Realistic Data**: Verify business-appropriate data generation
- **Directory Structure**: Must match `database/factories/` structure exactly

## Builder Testing Standards

### Builder Test Structure
```php
describe('EntityBuilder Unit Tests', function () {
    beforeEach(function () {
        $this->builder = new EntityBuilder(new Entity());
    });

    describe('scope methods', function () {
        test('employed scope adds correct where clause', function () {
            // Arrange & Act
            $query = $this->builder->employed();
            
            // Assert
            expect($query->toSql())->toContain('whereHas');
        });
    });
});
```

### Builder Testing Focus
- **Scope Methods**: Test all query scopes work correctly
- **SQL Generation**: Verify correct SQL is generated
- **Method Chaining**: Test builder methods return builder instance
- **Complex Queries**: Test complex query building logic

## Unit Test Quality Standards

### Code Standards
- **Import Classes**: Always import instead of using FQCN
- **AAA Pattern**: Clear Arrange-Act-Assert separation
- **Descriptive Names**: Test names explain expected behavior
- **Mock Cleanup**: Use `afterEach(\Mockery::close())` for mocked tests

### Documentation Standards
- **PHPDoc Headers**: Comprehensive scope documentation
- **Bidirectional @see**: Links between classes and tests
- **Test Organization**: Use describe blocks for logical grouping
- **Consistent Naming**: Follow established naming conventions

## Common Unit Test Patterns

### Mocking Dependencies
```php
// ✅ CORRECT - Mock external dependencies
beforeEach(function () {
    $this->repository = $this->mock(EntityRepository::class);
    $this->validator = $this->mock(ValidationService::class);
});

// ❌ AVOID - Using real dependencies
beforeEach(function () {
    $this->repository = new EntityRepository();
    $this->validator = new ValidationService();
});
```

### Testing Exception Scenarios
```php
test('throws exception for invalid state', function () {
    // Arrange
    $entity = Entity::factory()->invalidState()->create();

    // Act & Assert
    expect(fn() => $action->handle($entity))
        ->toThrow(CannotPerformActionException::class);
});
```

### Testing Interface Compliance
```php
test('implements required interface', function () {
    // Arrange
    $instance = new ClassName();

    // Assert
    expect($instance)->toBeInstanceOf(RequiredInterface::class);
});
```

## Performance Considerations

### Unit Test Performance
- **Fast Execution**: Unit tests should run in milliseconds
- **No Database**: Use mocks instead of database operations
- **No External Calls**: Mock all external dependencies
- **Parallel Safe**: Tests should be parallel-safe

### Memory Management
- **Clean Mocks**: Always clean up mocks after tests
- **Avoid Large Objects**: Use minimal test data
- **Factory Usage**: Use make() instead of create() when possible

## Troubleshooting Unit Tests

### Common Issues
- **Mock Expectations**: Ensure mock expectations are set correctly
- **Test Isolation**: Tests should not depend on each other
- **State Management**: Clean up state between tests
- **Type Errors**: Use proper type hints and assertions

### Debug Techniques
```php
// Debug mock calls
$mock->shouldReceive('method')
     ->once()
     ->andReturnUsing(function ($args) {
         dump($args); // Debug received arguments
         return $result;
     });

// Test with debugging
test('debug test', function () {
    dump($variable); // Debug variable state
    expect($variable)->toBe($expected);
});
```

This comprehensive unit testing guide ensures focused, fast, and reliable unit tests that form the foundation of Ringside's testing strategy.