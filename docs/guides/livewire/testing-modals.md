# Modal Component Testing

## Overview

This guide covers comprehensive testing strategies for Livewire modal components in Ringside. Modal components provide overlay interfaces for forms and content, integrating with our form system to create seamless user interactions.

## Modal Component Architecture

### BaseFormModal Testing
Most modal components extend `BaseFormModal` and integrate with form components:

```php
/**
 * @extends BaseFormModal<CreateEditForm, Event>
 */
class FormModal extends BaseFormModal
{
    protected function getFormClass(): string { return CreateEditForm::class; }
    protected function getModelClass(): string { return Event::class; }
    protected function getModalPath(): string { return 'livewire.events.modals.form-modal'; }
}
```

## Testing Structure

### Basic Test Setup
```php
use App\Livewire\Events\Modals\FormModal;
use App\Livewire\Events\Forms\CreateEditForm;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Users\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->administrator()->create();
    $this->actingAs($this->admin);
});
```

### Test Organization
Organize modal tests using clear `describe()` blocks:

```php
describe('FormModal Configuration', function () {
    // Test abstract method implementations
});

describe('FormModal State Management', function () {
    // Test modal open/close state
});

describe('FormModal Form Integration', function () {
    // Test form instantiation and binding
});

describe('FormModal Submission', function () {
    // Test form submission through modal
});

describe('FormModal Event Handling', function () {
    // Test event dispatching and handling
});
```

## Configuration Testing

### Abstract Method Testing
Test that modal components implement required abstract methods correctly:

```php
describe('FormModal Configuration', function () {
    test('returns correct form class', function () {
        $modal = new FormModal();
        $reflection = new ReflectionClass($modal);
        $method = $reflection->getMethod('getFormClass');
        $method->setAccessible(true);
        
        expect($method->invoke($modal))->toBe(CreateEditForm::class);
    });
    
    test('returns correct model class', function () {
        $modal = new FormModal();
        $reflection = new ReflectionClass($modal);
        $method = $reflection->getMethod('getModelClass');
        $method->setAccessible(true);
        
        expect($method->invoke($modal))->toBe(Event::class);
    });
    
    test('returns correct modal path', function () {
        $modal = new FormModal();
        $reflection = new ReflectionClass($modal);
        $method = $reflection->getMethod('getModalPath');
        $method->setAccessible(true);
        
        expect($method->invoke($modal))->toBe('livewire.events.modals.form-modal');
    });
});
```

### Form Integration Testing
Test that modals properly integrate with forms:

```php
test('initializes form correctly', function () {
    $component = Livewire::test(FormModal::class);
    
    expect($component->instance()->form)->toBeInstanceOf(CreateEditForm::class);
});

test('form and modal references are properly linked', function () {
    $component = Livewire::test(FormModal::class);
    
    expect($component->instance()->form)->toBe($component->instance()->modelForm);
});
```

## State Management Testing

### Modal Open/Close State
Test basic modal state management:

```php
describe('FormModal State Management', function () {
    test('modal starts closed', function () {
        $component = Livewire::test(FormModal::class);
        
        expect($component->instance()->isModalOpen)->toBeFalse();
    });
    
    test('opens modal correctly', function () {
        $component = Livewire::test(FormModal::class);
        
        $component->call('openModal');
        
        expect($component->instance()->isModalOpen)->toBeTrue();
    });
    
    test('closes modal correctly', function () {
        $component = Livewire::test(FormModal::class)
            ->call('openModal');
        
        expect($component->instance()->isModalOpen)->toBeTrue();
        
        $component->call('closeModal');
        
        expect($component->instance()->isModalOpen)->toBeFalse();
    });
});
```

### Modal State Transitions
Test modal state transitions and cleanup:

```php
test('resets form when switching between create and edit modes', function () {
    $event = Event::factory()->create(['name' => 'Test Event']);
    
    $component = Livewire::test(FormModal::class)
        ->call('openModal', $event->id);
    
    expect($component->instance()->form->name)->toBe('Test Event');
    
    $component->call('openModal'); // Open in create mode
    
    expect($component->instance()->form->name)->toBe('');
});

test('maintains modal state during form validation errors', function () {
    $component = Livewire::test(FormModal::class)
        ->call('openModal')
        ->set('form.name', '') // Invalid data
        ->call('save');
    
    expect($component->instance()->isModalOpen)->toBeTrue();
    expect($component->instance()->form->getErrorBag()->has('name'))->toBeTrue();
});
```

### Model Context Management
Test modal behavior with model context:

```php
test('loads model context correctly when editing', function () {
    $venue = Venue::factory()->create();
    $event = Event::factory()->create([
        'name' => 'Test Event',
        'date' => '2024-01-01 14:00:00',
        'venue_id' => $venue->id,
    ]);
    
    $component = Livewire::test(FormModal::class)
        ->call('openModal', $event->id);
    
    expect($component->instance()->model)->toBe($event);
    expect($component->instance()->form->name)->toBe('Test Event');
    expect($component->instance()->form->date)->toBe('2024-01-01 14:00:00');
    expect($component->instance()->form->venue_id)->toBe($venue->id);
});

test('clears model context when switching to create mode', function () {
    $event = Event::factory()->create(['name' => 'Test Event']);
    
    $component = Livewire::test(FormModal::class)
        ->call('openModal', $event->id);
    
    expect($component->instance()->model)->toBe($event);
    
    $component->call('openModal'); // Switch to create mode
    
    expect($component->instance()->model)->toBeNull();
    expect($component->instance()->form->name)->toBe('');
});
```

## Form Integration Testing

### Form Instantiation
Test form component instantiation and binding:

```php
describe('FormModal Form Integration', function () {
    test('creates form instance on mount', function () {
        $component = Livewire::test(FormModal::class);
        
        expect($component->instance()->form)->toBeInstanceOf(CreateEditForm::class);
    });
    
    test('form has correct parent reference', function () {
        $component = Livewire::test(FormModal::class);
        
        expect($component->instance()->form->getComponent())->toBe($component->instance());
    });
    
    test('form has correct property name', function () {
        $component = Livewire::test(FormModal::class);
        
        expect($component->instance()->form->getName())->toBe('form');
    });
});
```

### Form-Modal Communication
Test communication between modal and form components:

```php
test('modal passes model to form correctly', function () {
    $event = Event::factory()->create(['name' => 'Test Event']);
    
    $component = Livewire::test(FormModal::class)
        ->call('openModal', $event->id);
    
    expect($component->instance()->form->getModel())->toBe($event);
});

test('form validation errors are accessible through modal', function () {
    $component = Livewire::test(FormModal::class)
        ->call('openModal')
        ->set('form.name', '')
        ->call('save');
    
    expect($component->instance()->form->getErrorBag()->has('name'))->toBeTrue();
});
```

## Modal Submission Testing

### Successful Submission
Test successful form submission through modal:

```php
describe('FormModal Submission', function () {
    test('creates new event through modal', function () {
        $venue = Venue::factory()->create();
        
        $component = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'New Event')
            ->set('form.date', '2024-01-01 14:00:00')
            ->set('form.venue_id', $venue->id)
            ->call('save');
        
        expect($component->instance()->isModalOpen)->toBeFalse();
        expect(Event::where('name', 'New Event')->exists())->toBeTrue();
    });
    
    test('updates existing event through modal', function () {
        $venue = Venue::factory()->create();
        $event = Event::factory()->create([
            'name' => 'Old Name',
            'venue_id' => $venue->id,
        ]);
        
        $component = Livewire::test(FormModal::class)
            ->call('openModal', $event->id)
            ->set('form.name', 'New Name')
            ->call('save');
        
        expect($component->instance()->isModalOpen)->toBeFalse();
        expect($event->fresh()->name)->toBe('New Name');
    });
});
```

### Submission Failure Handling
Test modal behavior when submission fails:

```php
test('keeps modal open when validation fails', function () {
    $component = Livewire::test(FormModal::class)
        ->call('openModal')
        ->set('form.name', '') // Required field
        ->call('save');
    
    expect($component->instance()->isModalOpen)->toBeTrue();
    expect($component->instance()->form->getErrorBag()->has('name'))->toBeTrue();
});

test('handles submission errors gracefully', function () {
    DB::shouldReceive('beginTransaction')->andThrow(new \Exception('Database error'));
    
    $component = Livewire::test(FormModal::class)
        ->call('openModal')
        ->set('form.name', 'Test Event')
        ->call('save');
    
    expect($component->instance()->isModalOpen)->toBeTrue();
    expect($component->instance()->form->getErrorBag()->has('general'))->toBeTrue();
});
```

### Alternative Submission Methods
Test different submission methods:

```php
test('submitForm method works correctly', function () {
    $venue = Venue::factory()->create();
    
    $component = Livewire::test(FormModal::class)
        ->call('openModal')
        ->set('form.name', 'Test Event')
        ->set('form.venue_id', $venue->id)
        ->call('submitForm');
    
    expect($component->instance()->isModalOpen)->toBeFalse();
    expect(Event::where('name', 'Test Event')->exists())->toBeTrue();
});

test('save method delegates to submitForm', function () {
    $venue = Venue::factory()->create();
    
    $component = Livewire::test(FormModal::class)
        ->call('openModal')
        ->set('form.name', 'Test Event')
        ->set('form.venue_id', $venue->id)
        ->call('save');
    
    expect($component->instance()->isModalOpen)->toBeFalse();
    expect(Event::where('name', 'Test Event')->exists())->toBeTrue();
});
```

## Event Handling Testing

### Event Dispatching
Test that modals dispatch correct events:

```php
describe('FormModal Event Handling', function () {
    test('dispatches form-submitted event on successful creation', function () {
        $venue = Venue::factory()->create();
        
        $component = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Test Event')
            ->set('form.date', '2024-01-01')
            ->set('form.venue_id', $venue->id)
            ->call('save');
        
        $component->assertDispatched('form-submitted');
    });
    
    test('dispatches closeModal event on successful submission', function () {
        $venue = Venue::factory()->create();
        
        $component = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Test Event')
            ->set('form.venue_id', $venue->id)
            ->call('save');
        
        $component->assertDispatched('closeModal');
    });
    
    test('dispatches model-specific events', function () {
        $event = Event::factory()->create();
        
        $component = Livewire::test(FormModal::class)
            ->call('openModal', $event->id)
            ->set('form.name', 'Updated Event')
            ->call('save');
        
        $component->assertDispatched('eventUpdated');
    });
});
```

### Event Parameters
Test event parameters and data:

```php
test('dispatches events with correct parameters', function () {
    $venue = Venue::factory()->create();
    
    $component = Livewire::test(FormModal::class)
        ->call('openModal')
        ->set('form.name', 'Test Event')
        ->set('form.venue_id', $venue->id)
        ->call('save');
    
    $component->assertDispatched('form-submitted');
    
    $event = Event::where('name', 'Test Event')->first();
    expect($event)->not->toBeNull();
});

test('does not dispatch events on validation failure', function () {
    $component = Livewire::test(FormModal::class)
        ->call('openModal')
        ->set('form.name', '')
        ->call('save');
    
    $component->assertNotDispatched('form-submitted');
    $component->assertNotDispatched('closeModal');
});
```

## Modal Rendering Testing

### Rendering States
Test modal rendering in different states:

```php
describe('FormModal Rendering', function () {
    test('can render in create mode', function () {
        $component = Livewire::test(FormModal::class)
            ->call('openModal');
        
        $component->assertOk();
        $component->assertSee('Create Event');
    });
    
    test('can render in edit mode', function () {
        $event = Event::factory()->create(['name' => 'Test Event']);
        
        $component = Livewire::test(FormModal::class)
            ->call('openModal', $event->id);
        
        $component->assertOk();
        $component->assertSee('Edit Event');
    });
    
    test('displays correct title based on mode', function () {
        $component = Livewire::test(FormModal::class)
            ->call('openModal');
        
        $component->assertSee('Create Event');
        
        $event = Event::factory()->create(['name' => 'Test Event']);
        $component->call('openModal', $event->id);
        
        $component->assertSee('Edit Event');
    });
});
```

### Content Rendering
Test modal content rendering:

```php
test('renders form fields correctly', function () {
    $component = Livewire::test(FormModal::class)
        ->call('openModal');
    
    $component->assertSee('Name');
    $component->assertSee('Date');
    $component->assertSee('Venue');
    $component->assertSee('Preview');
});

test('renders form data in edit mode', function () {
    $venue = Venue::factory()->create(['name' => 'Test Arena']);
    $event = Event::factory()->create([
        'name' => 'Test Event',
        'venue_id' => $venue->id,
    ]);
    
    $component = Livewire::test(FormModal::class)
        ->call('openModal', $event->id);
    
    $component->assertSee('Test Event');
    $component->assertSee('Test Arena');
});
```

## Advanced Modal Testing

### Dummy Data Integration
Test dummy data generation functionality:

```php
describe('FormModal Dummy Data', function () {
    test('generates dummy data for development', function () {
        $component = Livewire::test(FormModal::class)
            ->call('openModal')
            ->call('fillDummyFields');
        
        expect($component->instance()->form->name)->not->toBe('');
        expect($component->instance()->form->venue_id)->not->toBe(0);
    });
    
    test('dummy data respects field constraints', function () {
        $component = Livewire::test(FormModal::class)
            ->call('openModal')
            ->call('fillDummyFields');
        
        expect(strlen($component->instance()->form->name))->toBeLessThanOrEqual(255);
        expect(Venue::find($component->instance()->form->venue_id))->not->toBeNull();
    });
});
```

### Complex Modal Scenarios
Test complex modal workflows:

```php
test('handles rapid open/close cycles', function () {
    $component = Livewire::test(FormModal::class);
    
    // Rapid open/close
    $component->call('openModal');
    expect($component->instance()->isModalOpen)->toBeTrue();
    
    $component->call('closeModal');
    expect($component->instance()->isModalOpen)->toBeFalse();
    
    $component->call('openModal');
    expect($component->instance()->isModalOpen)->toBeTrue();
});

test('handles switching between different models', function () {
    $event1 = Event::factory()->create(['name' => 'Event 1']);
    $event2 = Event::factory()->create(['name' => 'Event 2']);
    
    $component = Livewire::test(FormModal::class)
        ->call('openModal', $event1->id);
    
    expect($component->instance()->form->name)->toBe('Event 1');
    
    $component->call('openModal', $event2->id);
    
    expect($component->instance()->form->name)->toBe('Event 2');
});
```

### Error Recovery Testing
Test modal error recovery:

```php
test('recovers from form validation errors', function () {
    $venue = Venue::factory()->create();
    
    $component = Livewire::test(FormModal::class)
        ->call('openModal')
        ->set('form.name', '') // Invalid
        ->call('save');
    
    expect($component->instance()->isModalOpen)->toBeTrue();
    expect($component->instance()->form->getErrorBag()->has('name'))->toBeTrue();
    
    // Fix the error
    $component->set('form.name', 'Valid Event')
        ->set('form.venue_id', $venue->id)
        ->call('save');
    
    expect($component->instance()->isModalOpen)->toBeFalse();
    expect(Event::where('name', 'Valid Event')->exists())->toBeTrue();
});
```

## Performance Testing

### Modal Performance
Test modal performance characteristics:

```php
test('opens modal without excessive queries', function () {
    $queryCount = 0;
    DB::listen(function ($query) use (&$queryCount) {
        $queryCount++;
    });
    
    $component = Livewire::test(FormModal::class)
        ->call('openModal');
    
    expect($queryCount)->toBeLessThan(3);
});

test('handles large model datasets efficiently', function () {
    $events = Event::factory()->count(100)->create();
    
    $component = Livewire::test(FormModal::class)
        ->call('openModal', $events->random()->id);
    
    expect($component->instance()->isModalOpen)->toBeTrue();
});
```

### Memory Usage
Test modal memory efficiency:

```php
test('manages memory efficiently during modal operations', function () {
    $initialMemory = memory_get_usage();
    
    $component = Livewire::test(FormModal::class);
    
    // Perform multiple operations
    for ($i = 0; $i < 10; $i++) {
        $component->call('openModal');
        $component->call('closeModal');
    }
    
    $finalMemory = memory_get_usage();
    $memoryUsed = $finalMemory - $initialMemory;
    
    expect($memoryUsed)->toBeLessThan(5 * 1024 * 1024); // 5MB limit
});
```

## Integration Testing

### Modal-Parent Communication
Test communication between modal and parent components:

```php
test('modal integrates with parent component', function () {
    // This would typically be tested in a parent component test
    // but we can test the modal's event dispatching
    
    $component = Livewire::test(FormModal::class)
        ->call('openModal')
        ->set('form.name', 'Test Event')
        ->call('save');
    
    $component->assertDispatched('form-submitted');
});

test('modal handles parent-dispatched events', function () {
    $component = Livewire::test(FormModal::class);
    
    // Simulate parent dispatching open event
    $component->call('openModal');
    
    expect($component->instance()->isModalOpen)->toBeTrue();
});
```

### Cross-Component Testing
Test modal interaction with other components:

```php
test('modal works with table components', function () {
    $events = Event::factory()->count(3)->create();
    
    $component = Livewire::test(FormModal::class)
        ->call('openModal', $events->first()->id);
    
    expect($component->instance()->model)->toBe($events->first());
});
```

## Test Helpers and Utilities

### Modal Test Helpers
Create reusable helpers for modal testing:

```php
function openModalWithValidData(): TestCase
{
    $venue = Venue::factory()->create();
    
    return Livewire::test(FormModal::class)
        ->call('openModal')
        ->set('form.name', 'Test Event')
        ->set('form.date', '2024-01-01 14:00:00')
        ->set('form.venue_id', $venue->id)
        ->set('form.preview', 'Test preview');
}

function assertModalClosed($component): void
{
    expect($component->instance()->isModalOpen)->toBeFalse();
}

function assertModalOpen($component): void
{
    expect($component->instance()->isModalOpen)->toBeTrue();
}
```

### Mock Helpers
Create mocks for complex scenarios:

```php
function mockFormSubmissionError(): void
{
    DB::shouldReceive('beginTransaction')
        ->andThrow(new \Exception('Database error'));
}

function mockFormValidationError(): void
{
    // Mock validation failure scenarios
    Event::factory()->create(['name' => 'Existing Event']);
}
```

## Best Practices

### Test Organization
- Group related tests using `describe()` blocks
- Test both successful and failure scenarios
- Cover modal state transitions thoroughly
- Test form integration comprehensively

### Assertion Strategies
- Verify modal state changes
- Check form data binding
- Validate event dispatching
- Test error handling

### Performance Considerations
- Mock external dependencies
- Use minimal test data
- Test query efficiency
- Monitor memory usage

### Common Patterns
- Test modal open/close cycles
- Verify form validation integration
- Check event dispatching
- Test error recovery

## Related Documentation

- [Testing Guide](testing-guide.md) - Main testing overview
- [Form Testing](testing-forms.md) - Form component testing
- [Modal Patterns](../../architecture/livewire/modal-patterns.md) - Modal implementation patterns
- [Component Architecture](../../architecture/livewire/component-architecture.md) - Overall architecture