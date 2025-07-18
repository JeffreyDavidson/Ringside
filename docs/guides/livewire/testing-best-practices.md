# Testing Best Practices

## Overview

This guide provides best practices, common pitfalls, and guidelines for effective testing of Livewire components in Ringside. Following these practices ensures maintainable, reliable, and efficient tests.

## Testing Philosophy

### Test Behavior, Not Implementation

**✅ CORRECT - Testing behavior:**
```php
test('creates event with valid data', function () {
    $venue = Venue::factory()->create();
    
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $form->venue_id = $venue->id;
    
    $result = $form->store();
    
    expect($result)->toBeTrue();
    expect(Event::where('name', 'Test Event')->exists())->toBeTrue();
});
```

**❌ WRONG - Testing implementation:**
```php
test('calls repository create method', function () {
    $repository = Mockery::mock(EventRepository::class);
    $repository->shouldReceive('create')->once();
    
    $form = new CreateEditForm();
    $form->setRepository($repository);
    $form->store();
    
    // This tests how it works, not what it does
});
```

### Write Descriptive Test Names

**✅ CORRECT - Clear, descriptive names:**
```php
test('validates required name field when creating event', function () {
    // Test implementation
});

test('allows same name when editing existing event', function () {
    // Test implementation
});

test('dispatches event created event after successful submission', function () {
    // Test implementation
});
```

**❌ WRONG - Vague, uninformative names:**
```php
test('validation works', function () {
    // What validation? Under what conditions?
});

test('form submits', function () {
    // What happens when it submits? What's being tested?
});

test('test_event_creation', function () {
    // What aspect of event creation?
});
```

### Follow AAA Pattern

Structure tests using **Arrange, Act, Assert**:

```php
test('creates event with venue relationship', function () {
    // Arrange
    $venue = Venue::factory()->create(['name' => 'Test Arena']);
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $form->venue_id = $venue->id;
    
    // Act
    $result = $form->store();
    
    // Assert
    expect($result)->toBeTrue();
    $event = Event::where('name', 'Test Event')->first();
    expect($event->venue->name)->toBe('Test Arena');
});
```

## Test Organization

### Use Describe Blocks Effectively

Group related tests logically:

```php
describe('CreateEditForm Configuration', function () {
    test('returns correct model class', function () {
        // Configuration test
    });
    
    test('initializes with default values', function () {
        // Configuration test
    });
});

describe('CreateEditForm Validation', function () {
    test('validates required fields', function () {
        // Validation test
    });
    
    test('validates uniqueness rules', function () {
        // Validation test
    });
});

describe('CreateEditForm Submission', function () {
    test('creates new event with valid data', function () {
        // Submission test
    });
    
    test('updates existing event', function () {
        // Submission test
    });
});
```

### Keep Tests Focused

Each test should verify one behavior:

**✅ CORRECT - Single behavior:**
```php
test('validates required name field', function () {
    $form = new CreateEditForm();
    $form->name = '';
    
    $form->validate();
    
    expect($form->getErrorBag()->has('name'))->toBeTrue();
});

test('validates name uniqueness', function () {
    Event::factory()->create(['name' => 'Existing Event']);
    
    $form = new CreateEditForm();
    $form->name = 'Existing Event';
    
    $form->validate();
    
    expect($form->getErrorBag()->has('name'))->toBeTrue();
});
```

**❌ WRONG - Multiple behaviors:**
```php
test('validation works correctly', function () {
    $form = new CreateEditForm();
    $form->name = '';
    $form->date = 'invalid-date';
    
    $form->validate();
    
    expect($form->getErrorBag()->has('name'))->toBeTrue();
    expect($form->getErrorBag()->has('date'))->toBeTrue();
    // Testing multiple validations makes it hard to debug failures
});
```

## Data Management

### Use Factories Consistently

**✅ CORRECT - Factory usage:**
```php
test('creates event with venue', function () {
    $venue = Venue::factory()->create();
    $event = Event::factory()->create(['venue_id' => $venue->id]);
    
    expect($event->venue)->toBe($venue);
});

test('creates event with specific attributes', function () {
    $event = Event::factory()->create([
        'name' => 'Specific Event',
        'date' => '2024-01-01',
    ]);
    
    expect($event->name)->toBe('Specific Event');
    expect($event->date->format('Y-m-d'))->toBe('2024-01-01');
});
```

**❌ WRONG - Manual model creation:**
```php
test('creates event with venue', function () {
    $venue = new Venue();
    $venue->name = 'Test Venue';
    $venue->city = 'Test City';
    $venue->state = 'Test State';
    $venue->save();
    
    $event = new Event();
    $event->name = 'Test Event';
    $event->venue_id = $venue->id;
    $event->save();
    
    // Verbose and brittle
});
```

### Use Meaningful Test Data

**✅ CORRECT - Descriptive test data:**
```php
test('displays event information correctly', function () {
    $event = Event::factory()->create([
        'name' => 'WrestleMania 40',
        'date' => '2024-04-07',
    ]);
    
    $component = Livewire::test(EventsTable::class);
    
    $component->assertSee('WrestleMania 40');
    $component->assertSee('Apr 7, 2024');
});
```

**❌ WRONG - Generic test data:**
```php
test('displays event information correctly', function () {
    $event = Event::factory()->create([
        'name' => 'Test Event',
        'date' => '2024-01-01',
    ]);
    
    $component = Livewire::test(EventsTable::class);
    
    $component->assertSee('Test Event');
    $component->assertSee('Jan 1, 2024');
});
```

### Clean Up Test Data

```php
beforeEach(function () {
    // Set up test data
    $this->admin = User::factory()->administrator()->create();
    $this->actingAs($this->admin);
});

afterEach(function () {
    // Clean up if needed (usually automatic with database transactions)
    Mockery::close();
});
```

## Assertion Strategies

### Use Specific Assertions

**✅ CORRECT - Specific assertions:**
```php
test('event has correct attributes', function () {
    $event = Event::factory()->create([
        'name' => 'Test Event',
        'published' => true,
    ]);
    
    expect($event->name)->toBe('Test Event');
    expect($event->published)->toBeTrue();
    expect($event->created_at)->toBeInstanceOf(Carbon::class);
});
```

**❌ WRONG - Generic assertions:**
```php
test('event is created correctly', function () {
    $event = Event::factory()->create([
        'name' => 'Test Event',
        'published' => true,
    ]);
    
    expect($event)->toBeTruthy();
    expect($event->name)->not->toBeNull();
    expect($event->published)->not->toBeFalse();
});
```

### Assert Both Positive and Negative Cases

```php
test('validates required name field', function () {
    $form = new CreateEditForm();
    $form->name = '';
    
    $form->validate();
    
    expect($form->getErrorBag()->has('name'))->toBeTrue();
});

test('accepts valid name field', function () {
    $form = new CreateEditForm();
    $form->name = 'Valid Event Name';
    
    $form->validate();
    
    expect($form->getErrorBag()->has('name'))->toBeFalse();
});
```

### Test Edge Cases

```php
test('handles empty database gracefully', function () {
    $component = Livewire::test(EventsTable::class);
    
    $component->assertSee('No events found');
    $component->assertDontSee('Next page');
});

test('handles maximum name length', function () {
    $form = new CreateEditForm();
    $form->name = str_repeat('a', 255); // Maximum length
    
    $form->validate();
    
    expect($form->getErrorBag()->has('name'))->toBeFalse();
});

test('rejects name exceeding maximum length', function () {
    $form = new CreateEditForm();
    $form->name = str_repeat('a', 256); // One over maximum
    
    $form->validate();
    
    expect($form->getErrorBag()->has('name'))->toBeTrue();
});
```

## Error Handling

### Test Error Conditions

```php
test('handles database connection errors', function () {
    DB::shouldReceive('beginTransaction')
        ->andThrow(new \Exception('Connection failed'));
    
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    
    $result = $form->store();
    
    expect($result)->toBeFalse();
    expect($form->getErrorBag()->has('general'))->toBeTrue();
});

test('handles validation errors gracefully', function () {
    $form = new CreateEditForm();
    $form->name = ''; // Invalid
    
    $result = $form->store();
    
    expect($result)->toBeFalse();
    expect($form->getErrorBag()->has('name'))->toBeTrue();
    expect(Event::count())->toBe(0);
});
```

### Test Error Recovery

```php
test('recovers from validation errors', function () {
    $venue = Venue::factory()->create();
    
    $component = Livewire::test(FormModal::class)
        ->call('openModal')
        ->set('form.name', '') // Invalid
        ->call('save');
    
    // Modal should remain open with errors
    expect($component->instance()->isModalOpen)->toBeTrue();
    expect($component->instance()->form->getErrorBag()->has('name'))->toBeTrue();
    
    // Fix the error and retry
    $component->set('form.name', 'Valid Event')
        ->set('form.venue_id', $venue->id)
        ->call('save');
    
    // Should succeed now
    expect($component->instance()->isModalOpen)->toBeFalse();
    expect(Event::where('name', 'Valid Event')->exists())->toBeTrue();
});
```

## Performance Considerations

### Use Minimal Test Data

```php
test('displays events correctly', function () {
    // Use minimal data needed for test
    $events = Event::factory()->count(3)->create();
    
    $component = Livewire::test(EventsTable::class);
    
    foreach ($events as $event) {
        $component->assertSee($event->name);
    }
});
```

### Mock External Dependencies

```php
test('continues when external service fails', function () {
    // Mock external service to avoid real API calls
    $emailService = Mockery::mock(EmailService::class);
    $emailService->shouldReceive('sendNotification')
        ->once()
        ->andReturn(false);
    
    $this->app->instance(EmailService::class, $emailService);
    
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    
    $result = $form->store();
    
    expect($result)->toBeTrue();
    expect(Event::where('name', 'Test Event')->exists())->toBeTrue();
});
```

### Use Database Transactions

```php
beforeEach(function () {
    // Tests automatically wrapped in transactions
    $this->admin = User::factory()->administrator()->create();
    $this->actingAs($this->admin);
});

// Database is automatically rolled back after each test
```

## Common Pitfalls to Avoid

### Don't Test Framework Code

**❌ WRONG - Testing Laravel/Livewire internals:**
```php
test('livewire component renders', function () {
    $component = Livewire::test(EventsTable::class);
    
    expect($component->instance())->toBeInstanceOf(EventsTable::class);
    // This tests Livewire framework, not your code
});
```

**✅ CORRECT - Testing your logic:**
```php
test('displays events in table', function () {
    $events = Event::factory()->count(3)->create();
    
    $component = Livewire::test(EventsTable::class);
    
    foreach ($events as $event) {
        $component->assertSee($event->name);
    }
});
```

### Don't Over-Mock

**❌ WRONG - Excessive mocking:**
```php
test('form stores data', function () {
    $form = Mockery::mock(CreateEditForm::class);
    $form->shouldReceive('validate')->once()->andReturn(true);
    $form->shouldReceive('getModelData')->once()->andReturn(['name' => 'Test']);
    $form->shouldReceive('store')->once()->andReturn(true);
    
    $result = $form->store();
    
    expect($result)->toBeTrue();
    // You're testing the mock, not the real code
});
```

**✅ CORRECT - Test real behavior:**
```php
test('form stores data', function () {
    $venue = Venue::factory()->create();
    
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $form->venue_id = $venue->id;
    
    $result = $form->store();
    
    expect($result)->toBeTrue();
    expect(Event::where('name', 'Test Event')->exists())->toBeTrue();
});
```

### Don't Test Multiple Concerns

**❌ WRONG - Testing multiple things:**
```php
test('form works correctly', function () {
    $form = new CreateEditForm();
    $form->name = '';
    
    $form->validate();
    expect($form->getErrorBag()->has('name'))->toBeTrue();
    
    $form->name = 'Valid Event';
    $result = $form->store();
    expect($result)->toBeTrue();
    
    // Testing validation AND storage in same test
});
```

**✅ CORRECT - Separate concerns:**
```php
test('validates required name field', function () {
    $form = new CreateEditForm();
    $form->name = '';
    
    $form->validate();
    
    expect($form->getErrorBag()->has('name'))->toBeTrue();
});

test('stores event with valid data', function () {
    $form = new CreateEditForm();
    $form->name = 'Valid Event';
    
    $result = $form->store();
    
    expect($result)->toBeTrue();
    expect(Event::where('name', 'Valid Event')->exists())->toBeTrue();
});
```

### Don't Ignore Test Isolation

**❌ WRONG - Tests depend on each other:**
```php
test('creates event', function () {
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $form->store();
    
    expect(Event::count())->toBe(1);
});

test('event exists', function () {
    // This depends on the previous test
    expect(Event::where('name', 'Test Event')->exists())->toBeTrue();
});
```

**✅ CORRECT - Independent tests:**
```php
test('creates event', function () {
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $form->store();
    
    expect(Event::count())->toBe(1);
    expect(Event::where('name', 'Test Event')->exists())->toBeTrue();
});

test('finds existing event', function () {
    $event = Event::factory()->create(['name' => 'Test Event']);
    
    expect(Event::where('name', 'Test Event')->exists())->toBeTrue();
});
```

## Maintenance Best Practices

### Keep Tests Up to Date

```php
// Update tests when requirements change
test('validates name uniqueness excluding current event', function () {
    $event = Event::factory()->create(['name' => 'Test Event']);
    
    $form = new CreateEditForm();
    $form->setModel($event);
    $form->name = 'Test Event'; // Same name should be allowed
    
    $form->validate();
    
    expect($form->getErrorBag()->has('name'))->toBeFalse();
});
```

### Refactor Tests When Code Changes

```php
// Before: Testing implementation details
test('calls repository method', function () {
    $repository = Mockery::mock(EventRepository::class);
    $repository->shouldReceive('create')->once();
    
    // Test implementation
});

// After: Testing behavior
test('creates event in database', function () {
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    
    $result = $form->store();
    
    expect($result)->toBeTrue();
    expect(Event::where('name', 'Test Event')->exists())->toBeTrue();
});
```

### Remove Obsolete Tests

```php
// Remove tests for removed features
// test('handles legacy event format', function () {
//     // This feature was removed in v2.0
// });
```

## Test Documentation

### Document Complex Test Logic

```php
test('handles complex business rule validation', function () {
    // Business Rule: Events can only be published if they have:
    // 1. A name
    // 2. A future date
    // 3. An assigned venue
    // 4. At least one match scheduled
    
    $venue = Venue::factory()->create();
    $event = Event::factory()->create([
        'name' => 'Test Event',
        'date' => now()->addDays(30),
        'venue_id' => $venue->id,
        'published' => false,
    ]);
    
    // Should fail without matches
    $result = $event->canBePublished();
    expect($result)->toBeFalse();
    
    // Should succeed with matches
    Match::factory()->create(['event_id' => $event->id]);
    $result = $event->canBePublished();
    expect($result)->toBeTrue();
});
```

### Use Comments for Context

```php
test('prevents deletion of past events', function () {
    // Create event that occurred yesterday
    $pastEvent = Event::factory()->create([
        'date' => now()->subDay(),
    ]);
    
    $component = Livewire::test(ActionsComponent::class, ['model' => $pastEvent])
        ->call('delete');
    
    // Event should still exist
    expect(Event::find($pastEvent->id))->not->toBeNull();
    
    // User should be notified of the restriction
    $component->assertDispatched('action-failed', 'Cannot delete past events');
});
```

## Integration with CI/CD

### Ensure Tests Run in CI

```yaml
# .github/workflows/tests.yml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: vendor/bin/pest
```

### Use Test Coverage Reports

```bash
# Generate coverage report
vendor/bin/pest --coverage --min=80

# Generate HTML coverage report
vendor/bin/pest --coverage-html=coverage
```

## Related Documentation

- [Testing Guide](testing-guide.md) - Main testing overview
- [Form Testing](testing-forms.md) - Form component testing
- [Modal Testing](testing-modals.md) - Modal component testing
- [Table Testing](testing-tables.md) - Table component testing
- [Actions Testing](testing-actions.md) - Actions component testing
- [Advanced Testing](testing-advanced.md) - Advanced testing patterns