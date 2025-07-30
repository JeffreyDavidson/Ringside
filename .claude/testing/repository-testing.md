# Repository Testing Standards

## Repository Test Structure
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

## Repository Testing Categories
- **Employment Management**: `createEmployment()`, `createRelease()`
- **Retirement Management**: `createRetirement()`, `endRetirement()`
- **Suspension Management**: `createSuspension()`, `endSuspension()`
- **Injury Management**: `createInjury()`, `endInjury()`
- **Relationship Management**: Entity-specific relationship operations
