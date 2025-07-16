# Integration Test Best Practices

This document outlines best practices for writing integration tests in the Ringside application, including the new helper functions and improved test structure.

## Directory Structure

Integration tests should mirror the application directory structure for predictability and maintainability:

```
app/Models/Validation/Strategies/
└── IndividualRetirementValidation.php

tests/Integration/Models/Validation/Strategies/
└── IndividualRetirementValidationTest.php
```

### Benefits:
- **Consistency**: Follows existing patterns in the codebase
- **Predictability**: Easy to find tests for any class
- **IDE Support**: Better navigation between class and test
- **Scalability**: Structure grows naturally with the app

## Test Naming Conventions

### File Names
- Remove redundant words: `IndividualRetirementValidationIntegrationTest.php` → `IndividualRetirementValidationTest.php`
- Mirror class names exactly: `IndividualRetirementValidation.php` → `IndividualRetirementValidationTest.php`
- Location indicates test type: `tests/Integration/` vs `tests/Unit/`

### Test Methods
- Use descriptive but concise names
- Focus on behavior, not implementation
- Use parameterized tests for similar scenarios

## Helper Functions

### Status Test Expectations

Use the custom expectation functions from `tests/Helpers/StatusTestExpectations.php`:

```php
// Instead of multiple manual assertions
expect($entity->status)->toBe(EmploymentStatus::Employed);
expect($entity->isEmployed())->toBeTrue();
expect($entity->currentEmployment)->not->toBeNull();

// Use comprehensive helpers
expectValidEntityState($entity);
expectToBeBookable($entity);
expectValidEmploymentLifecycle($entity);
```

### Complex Scenario Builders

Use scenario builders from `tests/Helpers/IntegrationTestHelpers.php`:

```php
// Instead of manual setup
$wrestler = Wrestler::factory()->create();
$title = Title::factory()->create();
$championship = TitleChampionship::factory()->create([...]);

// Use scenario builders
$scenario = createChampionshipScenario('wrestler');
$lineup = createEmploymentLifecycleScenario('wrestler');
```

## Parameterized Tests

### Before (Verbose and Repetitive):
```php
test('allows retirement for employed wrestler', function () {
    $wrestler = Wrestler::factory()->employed()->create();
    expect(fn() => $this->strategy->validate($wrestler))->not->toThrow();
});

test('allows retirement for suspended wrestler', function () {
    $wrestler = Wrestler::factory()->suspended()->create();
    expect(fn() => $this->strategy->validate($wrestler))->not->toThrow();
});

test('throws exception for unemployed wrestler', function () {
    $wrestler = Wrestler::factory()->unemployed()->create();
    expect(fn() => $this->strategy->validate($wrestler))
        ->toThrow(CannotBeRetiredException::class);
});
```

### After (Concise and Maintainable):
```php
test('validates retirement rules correctly', function ($factoryState, $shouldPass) {
    $wrestler = Wrestler::factory()->{$factoryState}()->create();
    
    if ($shouldPass) {
        expect(fn() => $this->strategy->validate($wrestler))->not->toThrow();
        expectValidEntityState($wrestler);
    } else {
        expect(fn() => $this->strategy->validate($wrestler))
            ->toThrow(CannotBeRetiredException::class);
    }
})->with([
    ['employed', true],
    ['suspended', true], 
    ['injured', true],
    ['released', true],
    ['unemployed', false],
    ['withFutureEmployment', false],
    ['retired', false],
]);
```

## Integration Test Focus

Integration tests should focus on:

### ✅ What TO Test:
- **Real Database Interactions**: Use `create()` not `make()`
- **Cross-Model Relationships**: Employment, championship, stable membership
- **Complete Workflows**: End-to-end action sequences
- **Business Rule Integration**: How rules work with real data
- **Transaction Integrity**: Ensure no orphaned records

### ❌ What NOT to Test:
- **Pure Business Logic**: Use unit tests instead
- **Authorization Rules**: Use feature tests instead  
- **Implementation Details**: Focus on behavior
- **Error Messages**: Focus on exception types

## Test Documentation

### Class-Level Documentation:
```php
/**
 * Integration tests for IndividualRetirementValidation strategy.
 *
 * Tests retirement validation rules with real database models and relationships.
 * Verifies that the strategy correctly identifies when individual entities can/cannot retire.
 *
 * @see IndividualRetirementValidation
 */
```

### Test Method Documentation:
Keep it brief and focus on business value:

```php
test('validates wrestler dependency rules', function () {
    // Tag teams with active wrestlers might have additional constraints
    $tagTeam = TagTeam::factory()->employed()->create();
    // ... test implementation
});
```

## Performance Considerations

### Database Cleanup:
```php
// Use cleanup helpers for complex scenarios
afterEach(function() {
    cleanupTestState();
});
```

### Factory Usage:
```php
// Create realistic test data efficiently
$roster = setupRealisticTestState();
```

### Transaction Testing:
```php
// Verify actions maintain data integrity
expectTransactionIntegrity(
    fn() => EmployAction::run($wrestler, now()),
    $wrestler
);
```

## Examples

### Good Integration Test:
```php
describe('WrestlerEmploymentLifecycle', function () {
    test('complete employment workflow maintains state consistency', function () {
        $scenario = createEmploymentLifecycleScenario('wrestler');
        
        EmployAction::run($scenario['entity'], $scenario['employment_date']);
        expectValidEmploymentLifecycle($scenario['entity']);
        expectToBeBookable($scenario['entity']);
        
        InjureAction::run($scenario['entity'], $scenario['injury_date']);
        expectValidInjuryState($scenario['entity']);
        expectToBeUnavailable($scenario['entity']);
    });
});
```

### Poor Integration Test:
```php
describe('WrestlerEmploymentLifecycle', function () {
    test('wrestler becomes employed when EmployAction is called', function () {
        $wrestler = Wrestler::factory()->unemployed()->make(); // ❌ Using make()
        
        // ❌ Testing implementation details
        expect($wrestler->status)->toBe(EmploymentStatus::Unemployed);
        expect($wrestler->employments)->toHaveCount(0);
        
        EmployAction::run($wrestler, now());
        
        // ❌ Manual assertions instead of helpers
        expect($wrestler->fresh()->status)->toBe(EmploymentStatus::Employed);
        expect($wrestler->fresh()->isEmployed())->toBeTrue();
        expect($wrestler->fresh()->currentEmployment)->not->toBeNull();
    });
});
```

## Migration Guide

### Existing Tests:
1. **Move files** to mirror app structure
2. **Rename files** to remove redundant words
3. **Parameterize** repetitive test scenarios
4. **Use helpers** instead of manual assertions
5. **Focus on integration** concerns only

### New Tests:
1. **Start with scenario builders** for complex setups
2. **Use expectation helpers** for state validation
3. **Parameterize from the beginning** when testing similar scenarios
4. **Document business value** not implementation details