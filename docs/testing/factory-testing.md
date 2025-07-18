# Factory Testing Standards

## Factory Test Structure
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

## Factory Testing Focus
- **Default Attributes**: Test factory generates appropriate defaults
- **State Methods**: Test all factory states work correctly
- **Relationships**: Test factory relationship creation
- **Realistic Data**: Verify business-appropriate data generation
