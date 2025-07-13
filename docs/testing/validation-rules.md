# Validation Rule Testing Guidelines

This document provides comprehensive guidelines for testing custom validation rules in the Ringside application, establishing clear patterns for both unit and integration testing approaches.

## Overview

Validation rules are tested at two distinct levels:
- **Unit Tests**: Focus on rule logic in complete isolation
- **Integration Tests**: Test validation within Laravel's framework with real data

## Directory Structure Standard

**CRITICAL PRINCIPLE**: Test directory structure must EXACTLY mirror the app directory structure at ALL testing levels.

### App-to-Test Mapping
```
app/Rules/{Domain}/{RuleName}.php
↓
tests/Unit/Rules/{Domain}/{RuleName}UnitTest.php
tests/Integration/Rules/{Domain}/{RuleName}IntegrationTest.php
```

### Examples
```
app/Rules/Events/DateCanBeChanged.php
→ tests/Unit/Rules/Events/DateCanBeChangedUnitTest.php
→ tests/Integration/Rules/Events/DateCanBeChangedIntegrationTest.php

app/Rules/Matches/CorrectNumberOfSides.php  
→ tests/Unit/Rules/Matches/CorrectNumberOfSidesUnitTest.php
→ tests/Integration/Rules/Matches/CorrectNumberOfSidesIntegrationTest.php
```

### Benefits of App Structure Mirroring
- **Easy Navigation**: Developers can instantly find tests for any rule class
- **Consistent Convention**: Follows Laravel community standards
- **Maintainable Structure**: Adding new rules automatically suggests correct test location
- **IDE Support**: Better autocomplete and navigation between app and test files

## Rule Categories and Testing Approaches

### Category A: Pure Logic Rules (High Unit Test Value)
**Characteristics**: Mathematical calculations, array processing, algorithm-based logic
**Testing Approach**: Unit tests only - focus on logic verification
**Examples**: `CompetitorsNotDuplicated`, `HasMinimumMembers`, `CorrectNumberOfSides`

### Category B: Simple Model Interaction Rules (Medium Unit Test Value)  
**Characteristics**: Simple model method calls, conditional logic with `method_exists()` checks
**Testing Approach**: Unit tests with Mockery for model dependencies
**Examples**: `DateCanBeChanged`, `CanChangeEmploymentDate`, `CanChangeDebutDate`

### Category C: Database-Heavy Rules (Integration Tests Only)
**Characteristics**: Complex database queries, Eloquent relationships, Laravel builder usage
**Testing Approach**: Integration tests only - requires real database interaction
**Examples**: `IsActive`, `IsBookable`, `CanRefereeMatch`, `ChampionInMatch`, `CanJoinTagTeam`

## Unit Test Standards

### File Location and Naming
```
tests/Unit/Rules/{Domain}/{RuleName}UnitTest.php
```
**Note**: Test directory structure must exactly mirror the app directory structure (`app/Rules/` → `tests/Unit/Rules/`)

### Required Structure
```php
<?php

declare(strict_types=1);

use App\Rules\Domain\RuleName;
// Import other required classes (never use FQCN)

/**
 * Unit tests for RuleName validation rule.
 *
 * UNIT TEST SCOPE:
 * - [Specific logic being tested]
 * - [Rule functionality in isolation]
 * - [Edge cases and error handling]
 * - [Interface compliance verification]
 *
 * These tests verify the RuleName rule logic independently
 * of models, database, or Laravel's validation framework.
 *
 * @see \App\Rules\Domain\RuleName
 */
describe('RuleName Validation Rule Unit Tests', function () {
    describe('core validation logic', function () {
        // Primary business logic tests
    });

    describe('interface compliance', function () {
        // ValidationRule interface verification
    });

    describe('error message consistency', function () {
        // Message format and consistency testing
    });

    describe('edge cases and data handling', function () {
        // Null values, type safety, boundary conditions
    });

    afterEach(function () {
        \Mockery::close();
    });
});
```

### Key Testing Patterns

#### Direct Rule Method Testing
```php
// ✅ CORRECT - Direct rule validation testing
$rule = new RuleName($dependencies);
$failCalled = false;
$failCallback = function (string $message) use (&$failCalled) {
    $failCalled = true;
};

$rule->validate('attribute', $value, $failCallback);
expect($failCalled)->toBeFalse();

// ❌ AVOID - Laravel validation framework
$validator = Validator::make(['field' => $value], ['field' => [new RuleName()]]);
```

#### Mockery for Dependencies
```php
// ✅ CORRECT - Mock model dependencies
$model = \Mockery::mock(Model::class);
$model->shouldReceive('someMethod')->andReturn(true);

$rule = new RuleName($model);
```

#### Interface Compliance Testing
```php
test('rule implements ValidationRule interface', function () {
    $rule = new RuleName(null);
    expect($rule)->toBeInstanceOf(\Illuminate\Contracts\Validation\ValidationRule::class);
});
```

## Integration Test Standards

### File Location and Naming
```
tests/Integration/Rules/{Domain}/{RuleName}IntegrationTest.php
```
**Note**: Test directory structure must exactly mirror the app directory structure (`app/Rules/` → `tests/Integration/Rules/`)

### Required Structure
```php
<?php

declare(strict_types=1);

use App\Models\Domain\Model;
use App\Rules\Domain\RuleName;
use Illuminate\Support\Facades\Validator;

/**
 * Integration tests for RuleName validation rule.
 *
 * INTEGRATION TEST SCOPE:
 * - Laravel validation framework integration
 * - Database interaction through models and factories
 * - Complete validation workflow with error handling
 * - Realistic scenarios with actual model states
 * - Validator facade integration and error message testing
 *
 * These tests verify that the RuleName rule works correctly
 * within Laravel's validation system with real database data.
 *
 * @see \App\Rules\Domain\RuleName
 */
describe('RuleName Validation Rule Integration Tests', function () {
    describe('validation rule behavior', function () {
        // Core validation scenarios with real data
    });

    describe('edge cases and data types', function () {
        // Boundary conditions with database constraints
    });
});
```

### Key Testing Patterns

#### Laravel Validator Integration
```php
// ✅ CORRECT - Use Laravel's validation system
$validator = Validator::make(['field' => $value], [
    'field' => [new RuleName()],
]);

expect($validator->passes())->toBeTrue();
expect($validator->errors()->first('field'))->toBe('Expected message');
```

#### Factory Usage for Realistic Data
```php
// ✅ CORRECT - Use factories for real model states
$activeModel = Model::factory()->active()->create();
$inactiveModel = Model::factory()->inactive()->create();
```

## DataAwareRule Testing

### Unit Test Approach
```php
describe('DataAwareRule implementation', function () {
    test('setData method stores data correctly', function () {
        $rule = new RuleName();
        $testData = ['field' => 'value'];

        $result = $rule->setData($testData);
        expect($result)->toBe($rule); // Method chaining
    });
});
```

### Integration Test Approach
```php
$validator = Validator::make([
    'field1' => $value1,
    'field2' => $value2,
    'target_field' => $targetValue,
], [
    'target_field' => [new DataAwareRuleName()],
]);
```

## Error Message Testing

### Unit Test Pattern
```php
test('error message is consistent across calls', function () {
    $rule = new RuleName($failingDependency);
    $messages = [];
    $failCallback = function (string $message) use (&$messages) {
        $messages[] = $message;
    };

    $rule->validate('field1', $value, $failCallback);
    $rule->validate('field2', $value, $failCallback);

    expect($messages[0])->toBe($messages[1]);
    expect($messages[0])->toBe('Expected error message');
});
```

### Integration Test Pattern
```php
expect($validator->errors()->first('field'))
    ->toBe('Expected error message from rule');
```

## Common Anti-Patterns to Avoid

### In Unit Tests
❌ **Don't use database factories or models**
```php
// WRONG - This is integration testing
$model = Model::factory()->create();
$rule = new RuleName($model);
```

❌ **Don't use Laravel's validation framework**
```php
// WRONG - This tests Laravel, not the rule logic
$validator = Validator::make($data, ['field' => [new RuleName()]]);
```

❌ **Don't test business logic outcomes**
```php
// WRONG - Test rule logic, not business effects
expect($wrestler->fresh()->isEmployed())->toBeTrue();
```

### In Integration Tests
❌ **Don't mock model dependencies**
```php
// WRONG - Use real models in integration tests
$model = \Mockery::mock(Model::class);
```

❌ **Don't test rule logic in isolation**
```php
// WRONG - Use Validator::make() for integration testing
$rule = new RuleName();
$rule->validate('field', $value, $callback);
```

## Documentation Standards

### PHPDoc Requirements
- **Unit Tests**: Focus on "rule logic independently of models, database, or Laravel's validation framework"
- **Integration Tests**: Focus on "works correctly within Laravel's validation system with real database data"
- **Scope Documentation**: Clearly define what each test level covers
- **Bidirectional @see**: Link between rule class and test class

### Test Organization
- Use descriptive `describe()` blocks for test organization
- Group related functionality together
- Follow AAA pattern with clear separation
- Include `afterEach(\Mockery::close())` for unit tests

## Rule-Specific Guidelines

### Mathematical/Algorithm Rules
- Focus on calculation accuracy and edge cases
- Test with various input combinations
- Verify mathematical relationships (e.g., tag teams × 2 + wrestlers)

### Conditional Logic Rules
- Test all conditional branches
- Mock `method_exists()` scenarios with Mockery
- Verify proper fallback behavior

### Database Query Rules
- Test with realistic data scenarios
- Verify proper relationship handling
- Test query performance implications

### DataAware Rules
- Test data access and processing
- Verify proper form field integration
- Test with missing or invalid data fields

## Best Practices Summary

1. **Choose the Right Test Level**: Use unit tests for logic, integration tests for framework interaction
2. **Clear Separation**: Never mix unit and integration testing concerns
3. **Comprehensive Coverage**: Test all paths, edge cases, and error conditions
4. **Realistic Scenarios**: Use appropriate data for each test level
5. **Consistent Patterns**: Follow established templates and conventions
6. **Proper Documentation**: Clear scope definition and bidirectional references
7. **Maintainable Tests**: Write tests that are easy to understand and modify

## Future Considerations

- **Performance Testing**: Consider adding performance tests for complex database rules
- **Browser Testing**: May need visual validation testing for form integration
- **Security Testing**: Validate input sanitization and injection prevention
- **Accessibility Testing**: Ensure error messages work with screen readers

This testing approach ensures robust validation rule coverage while maintaining clear separation between unit and integration testing concerns.