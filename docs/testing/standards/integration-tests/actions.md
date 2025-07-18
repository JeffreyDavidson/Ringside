# Action & Repository Integration

## Action Integration Testing

### Action Test Organization
```php
describe('ActionName Integration Tests', function () {
    beforeEach(function () {
        $this->repository = app(EntityRepository::class);
        $this->action = app(ActionName::class);
    });

    describe('successful action execution', function () {
        test('performs action with database persistence', function () {
            // Arrange
            $entity = Entity::factory()->create();

            // Act
            $result = $this->action->handle($entity, now());

            // Assert
            expect($result)->toBeInstanceOf(Entity::class);
            $this->assertDatabaseHas('entities', [
                'id' => $entity->id,
                'status' => 'updated',
            ]);
        });
    });

    describe('error handling', function () {
        test('handles business rule violations', function () {
            // Arrange
            $entity = Entity::factory()->invalidState()->create();

            // Act & Assert
            expect(fn() => $this->action->handle($entity))
                ->toThrow(BusinessRuleException::class);
        });
    });
});
```

### Repository Integration Testing
```php
describe('EntityRepository Integration Tests', function () {
    beforeEach(function () {
        $this->repository = app(EntityRepository::class);
    });

    describe('employment management', function () {
        test('creates employment with proper database persistence', function () {
            // Arrange
            $entity = Entity::factory()->create();
            $employmentDate = now();

            // Act
            $result = $this->repository->createEmployment($entity, $employmentDate);

            // Assert
            expect($result->fresh()->isEmployed())->toBeTrue();
            $this->assertDatabaseHas('entity_employments', [
                'entity_id' => $entity->id,
                'started_at' => $employmentDate,
            ]);
        });
    });

    describe('relationship management', function () {
        test('manages entity relationships correctly', function () {
            // Arrange
            $entity = Entity::factory()->create();
            $related = RelatedEntity::factory()->create();

            // Act
            $this->repository->addRelationship($entity, $related);

            // Assert
            expect($entity->fresh()->relatedEntities)->toContain($related);
        });
    });
});
```
