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

```php
// ✅ CORRECT - Proper test structure
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
});
```

## Related Documentation
- [Code Style Guide](code-style.md)
- [PHP Standards](php.md)
- [Error Handling](error-handling.md)
