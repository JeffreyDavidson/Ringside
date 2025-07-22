# Testing Standards

Test code style and testing conventions for Ringside development.

## Overview

Comprehensive testing standards ensure reliable, maintainable test suites.

## Test Code Style

### Code Standards
- **Import Classes**: Always import test classes, never use FQCN
- **Clear Structure**: Use AAA pattern with proper separation
- **Descriptive Names**: Test names should explain expected behavior
- **Consistent Formatting**: Follow same formatting rules as application code
- **Group Assignment**: **MANDATORY** - Every test MUST include appropriate Pest groups
- **Assertions Required**: Every test MUST include proper assertions to avoid "risky" test warnings

```php
// ✅ CORRECT - Proper test structure with required groups
use App\Models\Wrestlers\Wrestler;
use App\Actions\Wrestlers\EmployAction;

test('can employ wrestler with valid data', function () {
    // Arrange
    $wrestler = Wrestler::factory()->create();
    $employmentDate = now()->subDays(30);

    // Act
    $result = EmployAction::run($wrestler, $employmentDate);

    // Assert
    expect($result)->toBeInstanceOf(WrestlerEmployment::class);
    expect($wrestler->fresh()->isEmployed())->toBeTrue();
})->group('wrestlers', 'unit', 'actions', 'employment');

// ✅ CORRECT - Integration test with proper groups
test('wrestler table displays employment status correctly', function () {
    $employedWrestler = Wrestler::factory()->employed()->create(['name' => 'Active Wrestler']);
    
    $component = Livewire::test(WrestlersTable::class);
    
    $component->assertSee('Active Wrestler');
})->group('wrestlers', 'integration', 'livewire', 'tables', 'status', 'employment');
```

## Pest Group Requirements

### Mandatory Groups
Every test MUST include these groups:
1. **Domain Group** - Which domain does it test? (managers, wrestlers, matches, etc.)
2. **Test Type Group** - What type of test? (unit, integration, feature)

### Recommended Groups
Tests SHOULD include relevant groups from:
- **Component Groups** - livewire, tables, modals, forms
- **Functionality Groups** - rendering, status, search, filters, employment, etc.

### Group Assignment Rules
```php
// ❌ WRONG - No groups
test('some test', function () {
    // test code
});

// ❌ WRONG - Insufficient groups  
test('some test', function () {
    // test code
})->group('managers');

// ✅ CORRECT - Proper group assignment
test('some test', function () {
    // test code
})->group('managers', 'integration', 'livewire', 'tables', 'rendering');
```

**See [Pest Group System](pest-groups.md) for complete group reference and usage examples.**

## Test Quality Standards

### Avoiding Risky Tests

**Risky Test Warning**: Tests that execute code but don't verify results with assertions.

**Common Causes:**
- Tests that only setup data without assertions
- Component tests that mount but don't verify behavior
- Tests with conditional assertions that may not execute

```php
// ❌ RISKY - No assertions, just setup
test('creates manager relationship', function () {
    $wrestler = Wrestler::factory()->create();
    $manager = Manager::factory()->create();
    
    WrestlerManager::create([
        'wrestler_id' => $wrestler->id,
        'manager_id' => $manager->id,
    ]);
    
    // Missing: Assertions to verify the relationship was created correctly
});

// ✅ CORRECT - Proper verification
test('creates manager relationship', function () {
    $wrestler = Wrestler::factory()->create();
    $manager = Manager::factory()->create();
    
    $relationship = WrestlerManager::create([
        'wrestler_id' => $wrestler->id,
        'manager_id' => $manager->id,
    ]);
    
    expect($relationship)->toBeInstanceOf(WrestlerManager::class);
    expect($wrestler->fresh()->managers)->toContain($manager);
    expect($manager->fresh()->wrestlers)->toContain($wrestler);
})->group('wrestlers', 'managers', 'integration', 'relationships');
```

### Database Compatibility

**SQLite vs MySQL**: Ensure date calculations and SQL functions work across both databases.

**Date Calculations:**
```php
// ❌ MySQL-only syntax
'DATEDIFF(NOW(), created_at) as days_ago'

// ✅ Database-agnostic approach
$driverName = DB::connection()->getDriverName();
$sql = match ($driverName) {
    'mysql' => 'DATEDIFF(NOW(), created_at) as days_ago',
    'sqlite' => 'CAST((julianday("now") - julianday(created_at)) AS INTEGER) as days_ago',
    default => 'DATEDIFF(NOW(), created_at) as days_ago'
};
```

**Testing Date Calculations:**
- Always test both current and historical date scenarios
- Use `Carbon::setTestNow()` for consistent test dates
- Verify calculations produce expected numeric results

## Related Documentation
- [Pest Group System](pest-groups.md) - Complete group reference and guidelines
- [Code Style Guide](code-style.md)
- [PHP Standards](php.md)
- [Error Handling](error-handling.md)
