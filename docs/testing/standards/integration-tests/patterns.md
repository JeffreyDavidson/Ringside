# Patterns & Troubleshooting

## Integration Test Quality Standards

### Database Assertions
```php
// ✅ CORRECT - Assert both model state and database persistence
expect($entity->fresh()->isEmployed())->toBeTrue();
$this->assertDatabaseHas('entity_employments', [
    'entity_id' => $entity->id,
    'started_at' => $employmentDate,
]);

// ❌ AVOID - Only testing model state
expect($entity->fresh()->isEmployed())->toBeTrue();
```

### Factory Usage
```php
// ✅ CORRECT - Use factories for realistic test data
$wrestler = Wrestler::factory()->employed()->create();
$tagTeam = TagTeam::factory()->withWrestlers(2)->create();

// ❌ AVOID - Manual model creation
$wrestler = new Wrestler(['name' => 'Test']);
$wrestler->save();
```

### Relationship Testing
```php
// ✅ CORRECT - Test actual relationship loading
expect($wrestler->currentEmployment)->not->toBeNull();
expect($wrestler->relationLoaded('currentEmployment'))->toBeTrue();

// ❌ AVOID - Testing relationship existence only
expect($wrestler->currentEmployment())->toBeInstanceOf(HasOne::class);
```

## Performance Considerations

### Integration Test Performance
- **Selective Database Usage**: Only use database when testing interactions
- **Transaction Rollbacks**: Use database transactions for isolation
- **Minimal Data**: Create only necessary test data
- **Eager Loading**: Test eager loading to prevent N+1 queries

### Test Optimization
```php
// ✅ CORRECT - Use database transactions
use Illuminate\Foundation\Testing\DatabaseTransactions;

class IntegrationTest extends TestCase
{
    use DatabaseTransactions;

    // Test methods
}

// ✅ CORRECT - Test eager loading
test('eager loads relationships correctly', function () {
    $wrestlers = Wrestler::factory()->count(3)->create();

    $results = Wrestler::with('currentEmployment')->get();

    foreach ($results as $wrestler) {
        expect($wrestler->relationLoaded('currentEmployment'))->toBeTrue();
    }
});
```

## Common Integration Test Patterns

### Testing Component Interactions
```php
test('action and repository work together correctly', function () {
    // Arrange
    $wrestler = Wrestler::factory()->create();
    $action = app(EmployWrestlerAction::class);

    // Act
    $result = $action->handle($wrestler, now());

    // Assert
    expect($result->isEmployed())->toBeTrue();
    expect($result->currentEmployment)->not->toBeNull();
});
```

### Testing Complex Workflows
```php
test('complete wrestler employment workflow', function () {
    // Arrange
    $wrestler = Wrestler::factory()->unemployed()->create();

    // Act - Multi-step workflow
    $employment = EmployWrestlerAction::run($wrestler, now());
    $assignment = AssignToStableAction::run($wrestler, $stable);
    $booking = BookForMatchAction::run($wrestler, $match);

    // Assert - Complete workflow state
    expect($wrestler->fresh()->isEmployed())->toBeTrue();
    expect($wrestler->fresh()->currentStable)->not->toBeNull();
    expect($wrestler->fresh()->upcomingMatches)->toHaveCount(1);
});
```

### Testing Error Propagation
```php
test('errors propagate correctly through component stack', function () {
    // Arrange
    $wrestler = Wrestler::factory()->retired()->create();

    // Act & Assert
    expect(fn() => EmployWrestlerAction::run($wrestler, now()))
        ->toThrow(CannotBeEmployedException::class);
});
```

## Troubleshooting Integration Tests

### Common Issues
- **Database State**: Ensure proper database cleanup between tests
- **Factory Dependencies**: Verify factory relationships work correctly
- **Event Conflicts**: Use Event::fake() to avoid event side effects
- **Cache Issues**: Clear cache between tests when needed

### Debug Techniques
```php
// Debug database queries
DB::enableQueryLog();
// Run test code
dump(DB::getQueryLog());

// Debug model state
dump($model->fresh()->toArray());

// Debug relationships
dump($model->relationLoaded('relationship'));
```

This comprehensive integration testing guide ensures proper component interaction testing while maintaining performance and reliability standards.
