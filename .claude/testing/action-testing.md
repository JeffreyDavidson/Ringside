# Action Testing Standards

## Action Test Organization
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

## Action Testing Patterns
- **Mock Setup**: Use helper methods for complex mock configurations
- **State-Based Testing**: Group tests by entity state
- **Exception Testing**: Test business rule violations
- **Date Handling**: Test both current and specific datetime scenarios
