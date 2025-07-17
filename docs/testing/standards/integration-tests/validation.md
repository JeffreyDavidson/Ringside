# Validation Rule Integration

## Validation Rule Integration

### Database-Heavy Rule Testing
```php
describe('RuleName Integration Tests', function () {
    describe('validation with database queries', function () {
        test('passes validation when entity is active', function () {
            // Arrange
            $activeEntity = Entity::factory()->active()->create();
            $validator = Validator::make(['entity_id' => $activeEntity->id], [
                'entity_id' => [new RuleName()],
            ]);

            // Act & Assert
            expect($validator->passes())->toBeTrue();
        });

        test('fails validation when entity is inactive', function () {
            // Arrange
            $inactiveEntity = Entity::factory()->inactive()->create();
            $validator = Validator::make(['entity_id' => $inactiveEntity->id], [
                'entity_id' => [new RuleName()],
            ]);

            // Act & Assert
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->first('entity_id'))
                ->toBe('Expected error message.');
        });
    });
});
```

### DataAware Rule Testing
```php
describe('DataAwareRule Integration Tests', function () {
    test('validates with form data context', function () {
        // Arrange
        $validator = Validator::make([
            'field1' => 'value1',
            'field2' => 'value2',
            'target_field' => 'target_value',
        ], [
            'target_field' => [new DataAwareRuleName()],
        ]);

        // Act & Assert
        expect($validator->passes())->toBeTrue();
    });
});
```
