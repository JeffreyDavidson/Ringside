# Validation Rule Testing

## Unit Test Approach (Pure Logic)
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

## Integration Test Approach (Framework)
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
