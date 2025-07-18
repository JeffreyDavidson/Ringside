# Model Testing Standards

## Required Test Structure
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

## What TO Test in Model Tests
- **Fillable Properties**: `getFillable()` returns correct array
- **Casts**: `getCasts()` returns correct casting configuration
- **Custom Builder**: `query()` returns correct builder instance
- **Trait Usage**: Model uses expected traits via `usesTrait()`
- **Default Values**: Default attribute values are set correctly
- **Basic Attributes**: Value objects and casting work correctly

## What NOT to Test in Model Tests
- Business logic methods (`ensureCanBeXXX()`, `canBeXXX()`)
- Validation rules enforcement
- Complex business workflows
- Trait functionality itself
- Database operations
- Factory-related code
