# Testing Standards

Comprehensive testing guidelines for Ringside application development.

## Overview

Ringside maintains 100% test coverage using a multi-layered testing approach with clear separation of concerns.

This document provides an overview of testing standards. For detailed guidelines on specific testing levels, see the specialized documentation:

- **[Unit Testing Standards](standards/unit-tests.md)** - Individual class and method testing
- **[Integration Testing Standards](standards/integration-tests.md)** - Component interaction testing  
- **[Feature Testing Standards](standards/feature-tests.md)** - Complete application workflow testing
- **[Performance Testing Standards](standards/performance.md)** - Application performance and optimization testing

## Directory Structure Standard

**CRITICAL PRINCIPLE**: Test directory structure must EXACTLY mirror the source directory structure at ALL testing levels.

### App-to-Test Mapping
```
app/{Directory}/{ClassName}.php
↓
tests/Unit/{Directory}/{ClassName}Test.php
tests/Integration/{Directory}/{ClassName}IntegrationTest.php
```

### Database-to-Test Mapping
```
database/factories/{Directory}/{ClassName}Factory.php
↓
tests/Unit/Database/Factories/{Directory}/{ClassName}FactoryTest.php
```

### Examples
```
app/Rules/Events/DateCanBeChanged.php
→ tests/Unit/Rules/Events/DateCanBeChangedUnitTest.php
→ tests/Integration/Rules/Events/DateCanBeChangedIntegrationTest.php

app/Models/Wrestlers/Wrestler.php
→ tests/Unit/Models/Wrestlers/WrestlerTest.php

app/Actions/Wrestlers/EmployAction.php
→ tests/Integration/Actions/Wrestlers/EmployActionTest.php

database/factories/Wrestlers/WrestlerFactory.php
→ tests/Unit/Database/Factories/Wrestlers/WrestlerFactoryTest.php

database/factories/Events/EventFactory.php
→ tests/Unit/Database/Factories/Events/EventFactoryTest.php
```

## Testing Levels & Distribution (Testing Pyramid)

### Unit Tests (70% of test suite)
- **Location**: `tests/Unit/`
- **Purpose**: Test individual classes, methods, and business logic in isolation
- **Scope**: Single class or method testing with mocked dependencies
- **Examples**: Model methods, Rule logic, Repository methods, Builder scopes

### Integration Tests (20% of test suite)
- **Location**: `tests/Integration/`
- **Purpose**: Test interaction between multiple components within a domain
- **Scope**: Multiple classes working together, database interactions, workflows
- **Examples**: Repository + Model interactions, Action + Repository workflows

### Feature Tests (8% of test suite)
- **Location**: `tests/Feature/Http/Controllers/`
- **Purpose**: Test complete user workflows and HTTP endpoints
- **Scope**: Full request-response cycle, authentication, authorization
- **Examples**: Controller endpoints, form submissions, user flows

### Browser Tests (2% of test suite)
- **Location**: `tests/Browser/` (when needed)
- **Purpose**: End-to-end testing of JavaScript interactions
- **Scope**: Full browser automation testing critical user paths
- **Framework**: Laravel Dusk for browser automation

## Test Structure & Organization

### Arrange-Act-Assert (AAA) Pattern
All tests must follow clear AAA pattern with visual separation:

```php
test('wrestler can be employed successfully', function () {
    // Arrange
    $wrestler = Wrestler::factory()->create(['name' => 'John Doe']);
    $employmentDate = now()->subDays(30);
    
    // Act
    $action = EmployAction::make()
        ->handle($wrestler, $employmentDate);
    
    // Assert
    expect($wrestler->fresh()->isEmployed())->toBeTrue();
    expect($wrestler->currentEmployment->started_at)->toEqual($employmentDate);
});
```

### AAA Guidelines
- Use clear comment blocks: `// Arrange`, `// Act`, `// Assert`
- Separate sections with blank lines for readability
- Keep arrange section focused on data setup only
- Act section should contain the primary action being tested
- Assert section should verify expected outcomes

## Test Framework & Tools

### Framework Setup
- **Pest PHP**: Primary testing framework with Laravel plugin
- **Coverage Requirement**: 100% test coverage maintained
- **Parallel Execution**: Tests run in parallel for performance
- **Database**: SQLite in-memory database for fast test execution

### Test Commands
```bash
# Run all tests
composer test

# Run specific test types
composer test:unit
./vendor/bin/pest tests/Unit/
./vendor/bin/pest tests/Integration/
./vendor/bin/pest tests/Feature/

# Run with coverage
./vendor/bin/pest --coverage

# Run in parallel
./vendor/bin/pest --parallel
```

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
- Business logic methods (`ensureCanBeXXX()`, `canBeXXX()`)
- Validation rules enforcement
- Complex business workflows
- Trait functionality itself
- Database operations
- Factory-related code

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
            // AAA pattern with mock setup
        });
    });

    describe('validation and error cases', function () {
        // Test exception scenarios
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

### Integration Test Approach (Framework)
```php
describe('RuleName Validation Rule Integration Tests', function () {
    test('passes validation when condition is met', function () {
        // Arrange
        $validator = Validator::make(['field' => $value], [
            'field' => [new RuleName()],
        ]);

        // Act & Assert
        expect($validator->passes())->toBeTrue();
    });
});
```

## Livewire Component Testing

### Component Test Structure
```php
describe('ComponentName Configuration', function () {
    test('returns correct form class', function () {
        // Test abstract method implementations
    });
});

describe('ComponentName State Management', function () {
    test('manages component state correctly', function () {
        // Test component properties and interactions
    });
});
```

### Testing Patterns
- **Component Configuration**: Test abstract method implementations
- **State Management**: Test component properties and interactions
- **Form Integration**: Test form submission and validation
- **Event Handling**: Test dispatched events and component communication

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
```php
describe('ModelFactory Unit Tests', function () {
    test('creates model with correct default attributes', function () {
        $model = ModelFactory::new()->make();
        
        expect($model->attribute)->toBeBetween(100, 500);
        expect($model->status)->toBe(DefaultStatus::Active);
    });
    
    test('factory state methods work correctly', function () {
        $employed = ModelFactory::new()->employed()->make();
        
        expect($employed->status)->toBe(EmploymentStatus::Employed);
    });
});
```

### Factory Testing Focus
- **Default Attributes**: Test factory generates appropriate defaults
- **State Methods**: Test all factory states work correctly
- **Relationships**: Test factory relationship creation
- **Realistic Data**: Verify business-appropriate data generation

## Test Quality Standards

### Code Standards
- **Import Classes**: Always import instead of using FQCN
- **AAA Pattern**: Clear Arrange-Act-Assert separation
- **Descriptive Names**: Test names explain expected behavior
- **Dataset Usage**: Use Pest datasets for multiple similar test cases

### Documentation Standards
- **PHPDoc Headers**: Comprehensive scope documentation
- **Bidirectional @see**: Links between classes and tests
- **Test Organization**: Use describe blocks for logical grouping
- **Consistent Naming**: Follow established naming conventions

## Performance & Optimization

### Test Performance
- **Fast Execution**: Full test suite should run under 30 seconds
- **Parallel Testing**: Use `--parallel` flag for speed
- **Memory Management**: Monitor memory usage in long test runs
- **Database Optimization**: Use in-memory SQLite for speed

### Test Reliability
- **No Flaky Tests**: All tests must be deterministic
- **Proper Cleanup**: Clean up after each test
- **Isolated Tests**: Tests should not depend on each other
- **Consistent Results**: Same results every time

## Continuous Integration

### CI Requirements
```bash
# Required CI checks
composer test
composer test:types
composer test:type-coverage
composer test:lint
```

### Quality Gates
- **100% Test Coverage**: No code ships without tests
- **All Tests Pass**: No failing tests allowed
- **Static Analysis**: PHPStan level 6 compliance
- **Code Style**: Laravel Pint formatting compliance

## Common Testing Patterns

### Named Routes Usage
```php
// ✅ CORRECT - Use named routes
actingAs(administrator())
    ->get(route('wrestlers.show', $wrestler))
    ->assertOk();

// ❌ INCORRECT - Hardcoded URLs
actingAs(administrator())
    ->get("/wrestlers/{$wrestler->id}")
    ->assertOk();
```

### Livewire Property Passing
```php
// ✅ CORRECT - Pass properties in initial array
livewire(WrestlerTable::class, ['userId' => $user->id])
    ->assertSee($wrestler->name);

// ❌ INCORRECT - Using set() for required properties
livewire(WrestlerTable::class)
    ->set('userId', $user->id)
    ->assertSee($wrestler->name);
```

### Authorization Testing
```php
describe('authorization patterns', function () {
    test('administrators can access resource', function () {
        actingAs(administrator())
            ->get(route('wrestlers.index'))
            ->assertOk();
    });

    test('basic users cannot access resource', function () {
        actingAs(basicUser())
            ->get(route('wrestlers.index'))
            ->assertForbidden();
    });

    test('guests are redirected to login', function () {
        get(route('wrestlers.index'))
            ->assertRedirect(route('login'));
    });
});
```

## Test Boundaries

### Unit Test Boundaries
- ✅ Component structure and method signatures
- ✅ Business logic and data processing
- ✅ Validation rules and data transformation
- ✅ Method return types and parameter handling
- ❌ Database operations
- ❌ HTTP requests and responses
- ❌ Multi-component interactions

### Integration Test Boundaries
- ✅ Component interaction and communication
- ✅ Database operations and business logic
- ✅ Complex multi-step workflows
- ✅ Repository and model interactions
- ❌ Visual elements and UI rendering
- ❌ Complete user authentication flows

### Feature Test Boundaries
- ✅ Route accessibility and HTTP responses
- ✅ Authentication and authorization workflows
- ✅ Complete user journeys
- ✅ Form data processing and validation
- ❌ Visual assertions on UI elements
- ❌ Button text content verification
- ❌ JavaScript interactions

## Troubleshooting

### Common Test Issues
- **Memory Issues**: Use `--memory-limit=512M` for large test suites
- **Parallel Issues**: Some tests may not be parallel-safe
- **Database Issues**: Ensure proper database cleanup
- **Timing Issues**: Use `testTime()->freeze()` for time-sensitive tests

### Debug Techniques
```bash
# Run single test with debug info
./vendor/bin/pest tests/Unit/Models/WrestlerTest.php --stop-on-failure

# Show test coverage
./vendor/bin/pest --coverage --min=100

# Profile test performance
./vendor/bin/pest --profile
```

This comprehensive testing standard ensures reliable, maintainable, and high-quality tests across the entire application.