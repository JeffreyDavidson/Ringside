# Form Component Testing

## Overview

This guide covers comprehensive testing strategies for Livewire form components in Ringside. Form components are the core of our data input system, handling validation, model binding, and persistence across all domain entities.

## Form Component Architecture

### BaseForm Testing
All form components extend `BaseForm` and follow the `CreateEditForm` pattern:

```php
/**
 * @extends BaseForm<Event>
 */
class CreateEditForm extends BaseForm
{
    // Form properties
    public string $name = '';
    public Carbon|string|null $date = '';
    public int $venue_id = 0;
    public ?string $preview = '';
    
    // Required methods
    protected function getModelClass(): string { return Event::class; }
    protected function getModelData(): array { /* ... */ }
    protected function rules(): array { /* ... */ }
}
```

## Testing Structure

### Basic Test Setup
```php
use App\Livewire\Events\Forms\CreateEditForm;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Users\User;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->actingAs($this->admin);
});
```

### Test Organization
Organize form tests using clear `describe()` blocks:

```php
describe('CreateEditForm Configuration', function () {
    // Test abstract method implementations
});

describe('CreateEditForm Validation', function () {
    // Test validation rules and error handling
});

describe('CreateEditForm Model Binding', function () {
    // Test model loading and data binding
});

describe('CreateEditForm Submission', function () {
    // Test form submission and persistence
});
```

## Configuration Testing

### Abstract Method Testing
Test that form components implement required abstract methods correctly:

```php
describe('CreateEditForm Configuration', function () {
    test('returns correct model class', function () {
        $form = new CreateEditForm();
        
        expect($form->getModelClass())->toBe(Event::class);
    });
    
    test('initializes with default values', function () {
        $form = new CreateEditForm();
        
        expect($form->name)->toBe('');
        expect($form->date)->toBe('');
        expect($form->venue_id)->toBe(0);
        expect($form->preview)->toBe('');
    });
    
    test('has correct form properties', function () {
        $form = new CreateEditForm();
        
        expect($form)->toHaveProperty('name');
        expect($form)->toHaveProperty('date');
        expect($form)->toHaveProperty('venue_id');
        expect($form)->toHaveProperty('preview');
    });
});
```

## Validation Testing

### Required Field Validation
Test required field validation thoroughly:

```php
describe('CreateEditForm Validation', function () {
    test('validates required fields', function () {
        $form = new CreateEditForm();
        
        $form->name = '';
        $form->date = '';
        $form->venue_id = 0;
        
        $form->validate();
        
        expect($form->getErrorBag()->has('name'))->toBeTrue();
        expect($form->getErrorBag()->has('date'))->toBeFalse(); // nullable
        expect($form->getErrorBag()->has('venue_id'))->toBeFalse(); // required_with:date
    });
    
    test('validates required fields with dependencies', function () {
        $form = new CreateEditForm();
        
        $form->name = 'Test Event';
        $form->date = '2024-01-01';
        $form->venue_id = 0; // Required when date is provided
        
        $form->validate();
        
        expect($form->getErrorBag()->has('name'))->toBeFalse();
        expect($form->getErrorBag()->has('venue_id'))->toBeTrue();
    });
});
```

### Uniqueness Validation
Test uniqueness rules for create vs edit scenarios:

```php
test('validates name uniqueness for new events', function () {
    Event::factory()->create(['name' => 'Existing Event']);
    
    $form = new CreateEditForm();
    $form->name = 'Existing Event';
    
    $form->validate();
    
    expect($form->getErrorBag()->has('name'))->toBeTrue();
});

test('allows same name when editing existing event', function () {
    $event = Event::factory()->create(['name' => 'Test Event']);
    
    $form = new CreateEditForm();
    $form->setModel($event);
    $form->name = 'Test Event';
    
    $form->validate();
    
    expect($form->getErrorBag()->has('name'))->toBeFalse();
});

test('validates name uniqueness when editing with different name', function () {
    Event::factory()->create(['name' => 'Existing Event']);
    $event = Event::factory()->create(['name' => 'Test Event']);
    
    $form = new CreateEditForm();
    $form->setModel($event);
    $form->name = 'Existing Event';
    
    $form->validate();
    
    expect($form->getErrorBag()->has('name'))->toBeTrue();
});
```

### Custom Validation Rules
Test custom validation rules:

```php
test('validates date can be changed for existing events', function () {
    $event = Event::factory()->past()->create();
    
    $form = new CreateEditForm();
    $form->setModel($event);
    $form->date = '2025-01-01';
    
    $form->validate();
    
    // Should use DateCanBeChanged rule
    expect($form->getErrorBag()->has('date'))->toBeFalse();
});

test('validates venue exists when date is provided', function () {
    $form = new CreateEditForm();
    $form->date = '2024-01-01';
    $form->venue_id = 999; // Non-existent venue
    
    $form->validate();
    
    expect($form->getErrorBag()->has('venue_id'))->toBeTrue();
});
```

### Field Format Validation
Test field format and type validation:

```php
test('validates date format', function () {
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $form->date = 'invalid-date';
    
    $form->validate();
    
    expect($form->getErrorBag()->has('date'))->toBeTrue();
});

test('validates string length limits', function () {
    $form = new CreateEditForm();
    $form->name = str_repeat('a', 300); // Too long
    
    $form->validate();
    
    expect($form->getErrorBag()->has('name'))->toBeTrue();
    expect($form->getErrorBag()->first('name'))->toContain('255 characters');
});
```

## Model Binding Testing

### Data Loading
Test model data loading and binding:

```php
describe('CreateEditForm Model Binding', function () {
    test('loads model data correctly', function () {
        $venue = Venue::factory()->create();
        $event = Event::factory()->create([
            'name' => 'Test Event',
            'date' => '2024-01-01 14:00:00',
            'venue_id' => $venue->id,
            'preview' => 'Test preview',
        ]);
        
        $form = new CreateEditForm();
        $form->setModel($event);
        
        expect($form->name)->toBe('Test Event');
        expect($form->date)->toBe('2024-01-01 14:00:00');
        expect($form->venue_id)->toBe($venue->id);
        expect($form->preview)->toBe('Test preview');
    });
    
    test('loads model with null values correctly', function () {
        $event = Event::factory()->create([
            'name' => 'Test Event',
            'date' => null,
            'venue_id' => null,
            'preview' => null,
        ]);
        
        $form = new CreateEditForm();
        $form->setModel($event);
        
        expect($form->name)->toBe('Test Event');
        expect($form->date)->toBeNull();
        expect($form->venue_id)->toBeNull();
        expect($form->preview)->toBeNull();
    });
});
```

### Extra Data Loading
Test the `loadExtraData()` method:

```php
test('calls loadExtraData when model is set', function () {
    $venue = Venue::factory()->create();
    $event = Event::factory()->create(['venue_id' => $venue->id]);
    
    $form = new CreateEditForm();
    $form->setModel($event);
    
    // Verify extra data loading occurred
    expect($form->venue_id)->toBe($event->venue_id);
});

test('handles complex relationships in loadExtraData', function () {
    $venue = Venue::factory()->create();
    $event = Event::factory()->create(['venue_id' => $venue->id]);
    
    $form = new CreateEditForm();
    $form->setModel($event);
    
    // Test that venue relationship is properly loaded
    expect($form->venue_id)->toBe($venue->id);
});
```

## Form Submission Testing

### Successful Creation
Test successful form submission for new models:

```php
describe('CreateEditForm Submission', function () {
    test('creates new event with valid data', function () {
        $venue = Venue::factory()->create();
        
        $form = new CreateEditForm();
        $form->name = 'New Event';
        $form->date = '2024-01-01 14:00:00';
        $form->venue_id = $venue->id;
        $form->preview = 'Event preview';
        
        $result = $form->store();
        
        expect($result)->toBeTrue();
        expect(Event::where('name', 'New Event')->exists())->toBeTrue();
        
        $event = Event::where('name', 'New Event')->first();
        expect($event->date->format('Y-m-d H:i:s'))->toBe('2024-01-01 14:00:00');
        expect($event->venue_id)->toBe($venue->id);
        expect($event->preview)->toBe('Event preview');
    });
    
    test('creates event with minimal required data', function () {
        $form = new CreateEditForm();
        $form->name = 'Minimal Event';
        
        $result = $form->store();
        
        expect($result)->toBeTrue();
        expect(Event::where('name', 'Minimal Event')->exists())->toBeTrue();
    });
});
```

### Successful Updates
Test successful form submission for existing models:

```php
test('updates existing event with valid data', function () {
    $venue = Venue::factory()->create();
    $event = Event::factory()->create(['name' => 'Old Name']);
    
    $form = new CreateEditForm();
    $form->setModel($event);
    $form->name = 'New Name';
    $form->date = '2024-01-01 14:00:00';
    $form->venue_id = $venue->id;
    
    $result = $form->store();
    
    expect($result)->toBeTrue();
    expect($event->fresh()->name)->toBe('New Name');
    expect($event->fresh()->date->format('Y-m-d H:i:s'))->toBe('2024-01-01 14:00:00');
    expect($event->fresh()->venue_id)->toBe($venue->id);
});

test('updates partial event data', function () {
    $event = Event::factory()->create([
        'name' => 'Original Name',
        'preview' => 'Original Preview',
    ]);
    
    $form = new CreateEditForm();
    $form->setModel($event);
    $form->name = 'Updated Name';
    // Don't change other fields
    
    $result = $form->store();
    
    expect($result)->toBeTrue();
    expect($event->fresh()->name)->toBe('Updated Name');
    expect($event->fresh()->preview)->toBe('Original Preview'); // Unchanged
});
```

### Validation Failure Handling
Test form submission with validation failures:

```php
test('returns false when validation fails', function () {
    $form = new CreateEditForm();
    $form->name = ''; // Required field
    
    $result = $form->store();
    
    expect($result)->toBeFalse();
    expect(Event::count())->toBe(0);
});

test('maintains form state when validation fails', function () {
    $venue = Venue::factory()->create();
    
    $form = new CreateEditForm();
    $form->name = ''; // Invalid
    $form->venue_id = $venue->id; // Valid
    
    $result = $form->store();
    
    expect($result)->toBeFalse();
    expect($form->venue_id)->toBe($venue->id); // State preserved
});
```

## Advanced Form Testing

### Relationship Testing
Test forms with complex relationships:

```php
test('creates event with venue relationship', function () {
    $venue = Venue::factory()->create();
    
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $form->venue_id = $venue->id;
    
    $result = $form->store();
    
    expect($result)->toBeTrue();
    
    $event = Event::where('name', 'Test Event')->first();
    expect($event->venue)->toBe($venue);
});

test('handles missing relationship gracefully', function () {
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $form->venue_id = 999; // Non-existent venue
    
    $result = $form->store();
    
    expect($result)->toBeFalse();
    expect($form->getErrorBag()->has('venue_id'))->toBeTrue();
});
```

### Data Transformation Testing
Test data transformation in `getModelData()`:

```php
test('transforms form data correctly for model storage', function () {
    $venue = Venue::factory()->create();
    
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $form->date = '2024-01-01 14:00:00';
    $form->venue_id = $venue->id;
    $form->preview = 'Test preview';
    
    $reflection = new ReflectionClass($form);
    $method = $reflection->getMethod('getModelData');
    $method->setAccessible(true);
    
    $modelData = $method->invoke($form);
    
    expect($modelData)->toBe([
        'name' => 'Test Event',
        'date' => '2024-01-01 14:00:00',
        'venue_id' => $venue->id,
        'preview' => 'Test preview',
    ]);
});
```

### Error Handling
Test error handling in form submission:

```php
test('handles database exceptions gracefully', function () {
    DB::shouldReceive('beginTransaction')->andThrow(new \Exception('Connection failed'));
    
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    
    $result = $form->store();
    
    expect($result)->toBeFalse();
    expect($form->getErrorBag()->has('general'))->toBeTrue();
});
```

## Testing with Traits

### Employment Management
Test forms that use employment-related traits:

```php
test('validates employment date with ManagesEmployment trait', function () {
    $form = new CreateEditForm(); // Assumes form uses ManagesEmployment
    $form->employment_date = '2024-01-01';
    
    $form->validate();
    
    expect($form->getErrorBag()->has('employment_date'))->toBeFalse();
});

test('handles employment date rules correctly', function () {
    $form = new CreateEditForm();
    $form->employment_date = 'invalid-date';
    
    $form->validate();
    
    expect($form->getErrorBag()->has('employment_date'))->toBeTrue();
});
```

### Data Presentation
Test forms that use data presentation traits:

```php
test('loads venues list with PresentsVenuesList trait', function () {
    $venues = Venue::factory()->count(3)->create();
    
    $form = new CreateEditForm(); // Assumes form uses PresentsVenuesList
    
    expect($form->getVenues())->toHaveCount(3);
    expect($form->getVenues()->first())->toBeInstanceOf(Venue::class);
});
```

## Performance Testing

### Query Efficiency
Test that forms don't execute excessive queries:

```php
test('uses efficient queries during form operations', function () {
    $queryCount = 0;
    DB::listen(function ($query) use (&$queryCount) {
        $queryCount++;
    });
    
    $venue = Venue::factory()->create();
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $form->venue_id = $venue->id;
    
    $form->store();
    
    expect($queryCount)->toBeLessThan(5); // Reasonable query limit
});
```

### Memory Usage
Test memory usage for large forms:

```php
test('handles large form data efficiently', function () {
    $initialMemory = memory_get_usage();
    
    $form = new CreateEditForm();
    $form->name = str_repeat('a', 1000);
    $form->preview = str_repeat('b', 5000);
    
    $form->store();
    
    $finalMemory = memory_get_usage();
    $memoryUsed = $finalMemory - $initialMemory;
    
    expect($memoryUsed)->toBeLessThan(1024 * 1024); // 1MB limit
});
```

## Common Testing Patterns

### Test Helpers
Create reusable test helpers for common form operations:

```php
function createFormWithValidData(): CreateEditForm
{
    $venue = Venue::factory()->create();
    
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $form->date = '2024-01-01 14:00:00';
    $form->venue_id = $venue->id;
    $form->preview = 'Test preview';
    
    return $form;
}

function assertEventExists(string $name, ?string $date = null): void
{
    $query = Event::where('name', $name);
    
    if ($date) {
        $query->where('date', $date);
    }
    
    expect($query->exists())->toBeTrue();
}
```

### Factory Integration
Use factories effectively in form tests:

```php
test('works with factory-created relationships', function () {
    $event = Event::factory()
        ->for(Venue::factory()->state(['name' => 'Test Arena']))
        ->create();
    
    $form = new CreateEditForm();
    $form->setModel($event);
    
    expect($form->venue_id)->toBe($event->venue_id);
});
```

## Best Practices

### Test Organization
- Group related tests using `describe()` blocks
- Use descriptive test names that explain the behavior
- Keep tests focused on single behaviors
- Test both positive and negative scenarios

### Data Management
- Use factories for consistent test data
- Clean up test data after each test
- Use transactions for database tests
- Mock external dependencies

### Assertion Strategies
- Test behavior, not implementation
- Use meaningful assertions
- Check both success and failure cases
- Verify side effects and state changes

### Performance Considerations
- Keep test data minimal
- Use efficient queries
- Mock expensive operations
- Profile test execution time

## Related Documentation

- [Testing Guide](testing-guide.md) - Main testing overview
- [Modal Testing](testing-modals.md) - Modal component testing
- [Form Patterns](../../architecture/livewire/form-patterns.md) - Form implementation patterns
- [Component Architecture](../../architecture/livewire/component-architecture.md) - Overall architecture