# Actions Component Testing

## Overview

This guide covers comprehensive testing strategies for Livewire action components in Ringside. Actions components handle business operations, user interactions, and workflow management across domain entities.

## Actions Component Architecture

### Actions Testing
Action components handle business operations for specific entities:

```php
/**
 * @extends BaseComponent
 */
class Actions extends Component
{
    public Model $model;
    public bool $showConfirmation = false;
    
    public function mount(Model $model): void { /* ... */ }
    public function edit(): void { /* ... */ }
    public function delete(): void { /* ... */ }
    public function confirmDelete(): void { /* ... */ }
}
```

## Testing Structure

### Basic Test Setup
```php
use App\Livewire\Events\Components\Actions;
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
Organize actions tests using clear `describe()` blocks:

```php
describe('Actions Configuration', function () {
    // Test component initialization and setup
});

describe('Actions Business Logic', function () {
    // Test business operations and workflows
});

describe('Actions Authorization', function () {
    // Test permissions and access control
});

describe('Actions Event Handling', function () {
    // Test event dispatching and communication
});

describe('Actions State Management', function () {
    // Test component state and confirmation flows
});
```

## Configuration Testing

### Component Initialization
Test that action components initialize correctly:

```php
describe('Actions Configuration', function () {
    test('initializes with correct model', function () {
        $event = Event::factory()->create();
        
        $component = Livewire::test(Actions::class, ['model' => $event]);
        
        expect($component->instance()->model)->toBe($event);
    });
    
    test('initializes with default state', function () {
        $event = Event::factory()->create();
        
        $component = Livewire::test(Actions::class, ['model' => $event]);
        
        expect($component->instance()->showConfirmation)->toBeFalse();
    });
    
    test('renders with model data', function () {
        $event = Event::factory()->create(['name' => 'Test Event']);
        
        $component = Livewire::test(Actions::class, ['model' => $event]);
        
        $component->assertOk();
        $component->assertSee('Test Event');
    });
});
```

### Available Actions Display
Test that available actions are displayed correctly:

```php
test('displays available actions', function () {
    $event = Event::factory()->create();
    
    $component = Livewire::test(Actions::class, ['model' => $event]);
    
    $component->assertSee('Edit');
    $component->assertSee('Delete');
    $component->assertSee('Duplicate');
});

test('conditionally displays actions based on model state', function () {
    $completedEvent = Event::factory()->create(['status' => 'completed']);
    $scheduledEvent = Event::factory()->create(['status' => 'scheduled']);
    
    $completedComponent = Livewire::test(Actions::class, ['model' => $completedEvent]);
    $scheduledComponent = Livewire::test(Actions::class, ['model' => $scheduledEvent]);
    
    $completedComponent->assertDontSee('Cancel');
    $scheduledComponent->assertSee('Cancel');
});
```

## Business Logic Testing

### Basic Actions
Test fundamental business operations:

```php
describe('Actions Business Logic', function () {
    test('edit action dispatches correct event', function () {
        $event = Event::factory()->create();
        
        $component = Livewire::test(Actions::class, ['model' => $event])
            ->call('edit');
        
        $component->assertDispatched('open-edit-modal', $event->id);
    });
    
    test('duplicate action creates new event', function () {
        $event = Event::factory()->create(['name' => 'Original Event']);
        
        $component = Livewire::test(Actions::class, ['model' => $event])
            ->call('duplicate');
        
        expect(Event::where('name', 'Copy of Original Event')->exists())->toBeTrue();
        $component->assertDispatched('event-duplicated');
    });
    
    test('delete action removes event', function () {
        $event = Event::factory()->create();
        
        $component = Livewire::test(Actions::class, ['model' => $event])
            ->call('delete');
        
        expect(Event::find($event->id))->toBeNull();
        $component->assertDispatched('event-deleted');
    });
});
```

### Complex Business Operations
Test complex business operations:

```php
test('cancel action updates event status', function () {
    $event = Event::factory()->create(['status' => 'scheduled']);
    
    $component = Livewire::test(Actions::class, ['model' => $event])
        ->call('cancel');
    
    expect($event->fresh()->status)->toBe('cancelled');
    $component->assertDispatched('event-cancelled');
});

test('reschedule action opens reschedule modal', function () {
    $event = Event::factory()->create(['status' => 'cancelled']);
    
    $component = Livewire::test(Actions::class, ['model' => $event])
        ->call('reschedule');
    
    $component->assertDispatched('open-reschedule-modal', $event->id);
});

test('publish action updates event visibility', function () {
    $event = Event::factory()->create(['published' => false]);
    
    $component = Livewire::test(Actions::class, ['model' => $event])
        ->call('publish');
    
    expect($event->fresh()->published)->toBeTrue();
    $component->assertDispatched('event-published');
});
```

### Validation and Business Rules
Test business rule validation:

```php
test('prevents deletion of past events', function () {
    $pastEvent = Event::factory()->past()->create();
    
    $component = Livewire::test(Actions::class, ['model' => $pastEvent])
        ->call('delete');
    
    expect(Event::find($pastEvent->id))->not->toBeNull();
    $component->assertDispatched('action-failed', 'Cannot delete past events');
});

test('prevents cancellation of completed events', function () {
    $completedEvent = Event::factory()->create(['status' => 'completed']);
    
    $component = Livewire::test(Actions::class, ['model' => $completedEvent])
        ->call('cancel');
    
    expect($completedEvent->fresh()->status)->toBe('completed');
    $component->assertDispatched('action-failed', 'Cannot cancel completed events');
});

test('validates event has venue before publishing', function () {
    $event = Event::factory()->create(['venue_id' => null]);
    
    $component = Livewire::test(Actions::class, ['model' => $event])
        ->call('publish');
    
    expect($event->fresh()->published)->toBeFalse();
    $component->assertDispatched('action-failed', 'Event must have a venue to be published');
});
```

## Authorization Testing

### Permission Checks
Test permission-based access control:

```php
describe('Actions Authorization', function () {
    test('requires authentication', function () {
        auth()->logout();
        
        $event = Event::factory()->create();
        
        $component = Livewire::test(Actions::class, ['model' => $event])
            ->call('edit');
        
        $component->assertUnauthorized();
    });
    
    test('requires administrator privileges for delete', function () {
        $user = User::factory()->create(); // Regular user
        $this->actingAs($user);
        
        $event = Event::factory()->create();
        
        $component = Livewire::test(Actions::class, ['model' => $event])
            ->call('delete');
        
        $component->assertUnauthorized();
    });
    
    test('allows editors to modify events', function () {
        $editor = User::factory()->editor()->create();
        $this->actingAs($editor);
        
        $event = Event::factory()->create();
        
        $component = Livewire::test(Actions::class, ['model' => $event])
            ->call('edit');
        
        $component->assertDispatched('open-edit-modal', $event->id);
    });
});
```

### Role-Based Actions
Test role-based action availability:

```php
test('displays different actions based on user role', function () {
    $event = Event::factory()->create();
    
    // Admin user
    $admin = User::factory()->administrator()->create();
    $this->actingAs($admin);
    
    $adminComponent = Livewire::test(Actions::class, ['model' => $event]);
    $adminComponent->assertSee('Delete');
    $adminComponent->assertSee('Edit');
    
    // Editor user
    $editor = User::factory()->editor()->create();
    $this->actingAs($editor);
    
    $editorComponent = Livewire::test(Actions::class, ['model' => $event]);
    $editorComponent->assertDontSee('Delete');
    $editorComponent->assertSee('Edit');
});

test('hides actions for insufficient permissions', function () {
    $viewer = User::factory()->viewer()->create();
    $this->actingAs($viewer);
    
    $event = Event::factory()->create();
    
    $component = Livewire::test(Actions::class, ['model' => $event]);
    
    $component->assertDontSee('Edit');
    $component->assertDontSee('Delete');
    $component->assertSee('View');
});
```

## Event Handling Testing

### Event Dispatching
Test event dispatching and communication:

```php
describe('Actions Event Handling', function () {
    test('dispatches events with correct parameters', function () {
        $event = Event::factory()->create();
        
        $component = Livewire::test(Actions::class, ['model' => $event])
            ->call('edit');
        
        $component->assertDispatched('open-edit-modal', $event->id);
    });
    
    test('dispatches multiple events for complex actions', function () {
        $event = Event::factory()->create();
        
        $component = Livewire::test(Actions::class, ['model' => $event])
            ->call('publish');
        
        $component->assertDispatched('event-published', $event->id);
        $component->assertDispatched('refresh-table');
        $component->assertDispatched('show-notification', 'Event published successfully');
    });
    
    test('dispatches error events on failure', function () {
        $event = Event::factory()->create(['venue_id' => null]);
        
        $component = Livewire::test(Actions::class, ['model' => $event])
            ->call('publish');
        
        $component->assertDispatched('action-failed');
        $component->assertDispatched('show-error', 'Event must have a venue to be published');
    });
});
```

### Event Handling
Test component response to external events:

```php
test('responds to external events', function () {
    $event = Event::factory()->create();
    
    $component = Livewire::test(Actions::class, ['model' => $event]);
    
    // Simulate external event
    $component->dispatch('model-updated', $event->id);
    
    // Component should refresh its model
    expect($component->instance()->model->fresh())->toBe($event->fresh());
});

test('handles event listener correctly', function () {
    $event = Event::factory()->create();
    
    $component = Livewire::test(Actions::class, ['model' => $event]);
    
    // Simulate parent component dispatching refresh
    $component->dispatch('refresh-actions');
    
    $component->assertOk();
});
```

## State Management Testing

### Confirmation Flows
Test confirmation state management:

```php
describe('Actions State Management', function () {
    test('shows confirmation for dangerous actions', function () {
        $event = Event::factory()->create();
        
        $component = Livewire::test(Actions::class, ['model' => $event])
            ->call('confirmDelete');
        
        expect($component->instance()->showConfirmation)->toBeTrue();
        $component->assertSee('Are you sure you want to delete this event?');
    });
    
    test('hides confirmation after cancellation', function () {
        $event = Event::factory()->create();
        
        $component = Livewire::test(Actions::class, ['model' => $event])
            ->call('confirmDelete')
            ->call('cancelDelete');
        
        expect($component->instance()->showConfirmation)->toBeFalse();
        $component->assertDontSee('Are you sure you want to delete this event?');
    });
    
    test('executes action after confirmation', function () {
        $event = Event::factory()->create();
        
        $component = Livewire::test(Actions::class, ['model' => $event])
            ->call('confirmDelete')
            ->call('confirmDeleteAction');
        
        expect(Event::find($event->id))->toBeNull();
        expect($component->instance()->showConfirmation)->toBeFalse();
    });
});
```

### State Persistence
Test state persistence across interactions:

```php
test('maintains state during confirmation flow', function () {
    $event = Event::factory()->create();
    
    $component = Livewire::test(Actions::class, ['model' => $event])
        ->call('confirmDelete');
    
    expect($component->instance()->model)->toBe($event);
    expect($component->instance()->showConfirmation)->toBeTrue();
    
    $component->call('cancelDelete');
    
    expect($component->instance()->model)->toBe($event);
    expect($component->instance()->showConfirmation)->toBeFalse();
});

test('resets state after action completion', function () {
    $event = Event::factory()->create();
    
    $component = Livewire::test(Actions::class, ['model' => $event])
        ->call('confirmDelete')
        ->call('confirmDeleteAction');
    
    expect($component->instance()->showConfirmation)->toBeFalse();
});
```

## Integration Testing

### Component Integration
Test integration with other components:

```php
describe('Actions Integration', function () {
    test('integrates with table component', function () {
        $event = Event::factory()->create();
        
        $component = Livewire::test(Actions::class, ['model' => $event])
            ->call('delete');
        
        $component->assertDispatched('event-deleted');
        $component->assertDispatched('refresh-table');
    });
    
    test('integrates with modal components', function () {
        $event = Event::factory()->create();
        
        $component = Livewire::test(Actions::class, ['model' => $event])
            ->call('edit');
        
        $component->assertDispatched('open-edit-modal', $event->id);
    });
    
    test('integrates with notification system', function () {
        $event = Event::factory()->create();
        
        $component = Livewire::test(Actions::class, ['model' => $event])
            ->call('publish');
        
        $component->assertDispatched('show-notification', 'Event published successfully');
    });
});
```

### Cross-Component Communication
Test communication between components:

```php
test('communicates with parent component', function () {
    $event = Event::factory()->create();
    
    $component = Livewire::test(Actions::class, ['model' => $event])
        ->call('duplicate');
    
    $component->assertDispatched('event-duplicated');
    $component->assertDispatched('refresh-parent');
});

test('responds to parent component events', function () {
    $event = Event::factory()->create();
    
    $component = Livewire::test(Actions::class, ['model' => $event]);
    
    // Simulate parent updating model
    $component->dispatch('model-updated', $event->id);
    
    expect($component->instance()->model->fresh())->toBe($event->fresh());
});
```

## Error Handling Testing

### Exception Handling
Test error handling and recovery:

```php
describe('Actions Error Handling', function () {
    test('handles database exceptions gracefully', function () {
        DB::shouldReceive('beginTransaction')->andThrow(new \Exception('Database error'));
        
        $event = Event::factory()->create();
        
        $component = Livewire::test(Actions::class, ['model' => $event])
            ->call('delete');
        
        expect(Event::find($event->id))->not->toBeNull();
        $component->assertDispatched('action-failed', 'An error occurred while deleting the event');
    });
    
    test('handles validation errors', function () {
        $event = Event::factory()->create(['venue_id' => null]);
        
        $component = Livewire::test(Actions::class, ['model' => $event])
            ->call('publish');
        
        $component->assertDispatched('action-failed');
        expect($event->fresh()->published)->toBeFalse();
    });
    
    test('handles permission errors', function () {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $event = Event::factory()->create();
        
        $component = Livewire::test(Actions::class, ['model' => $event])
            ->call('delete');
        
        $component->assertUnauthorized();
    });
});
```

### Error Recovery
Test error recovery scenarios:

```php
test('recovers from temporary errors', function () {
    $event = Event::factory()->create(['venue_id' => null]);
    
    $component = Livewire::test(Actions::class, ['model' => $event])
        ->call('publish');
    
    // Initial failure
    $component->assertDispatched('action-failed');
    
    // Fix the issue
    $event->update(['venue_id' => Venue::factory()->create()->id]);
    
    // Retry should succeed
    $component->call('publish');
    
    expect($event->fresh()->published)->toBeTrue();
});
```

## Performance Testing

### Action Performance
Test action performance characteristics:

```php
describe('Actions Performance', function () {
    test('executes actions efficiently', function () {
        $event = Event::factory()->create();
        
        $queryCount = 0;
        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });
        
        $component = Livewire::test(Actions::class, ['model' => $event])
            ->call('edit');
        
        expect($queryCount)->toBeLessThan(2);
    });
    
    test('handles bulk operations efficiently', function () {
        $events = Event::factory()->count(10)->create();
        
        $startTime = microtime(true);
        
        foreach ($events as $event) {
            $component = Livewire::test(Actions::class, ['model' => $event])
                ->call('delete');
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        expect($executionTime)->toBeLessThan(5); // 5 seconds limit
    });
});
```

### Memory Usage
Test memory efficiency:

```php
test('manages memory efficiently during actions', function () {
    $event = Event::factory()->create();
    
    $initialMemory = memory_get_usage();
    
    $component = Livewire::test(Actions::class, ['model' => $event]);
    
    // Perform multiple actions
    for ($i = 0; $i < 100; $i++) {
        $component->call('edit');
    }
    
    $finalMemory = memory_get_usage();
    $memoryUsed = $finalMemory - $initialMemory;
    
    expect($memoryUsed)->toBeLessThan(5 * 1024 * 1024); // 5MB limit
});
```

## Test Helpers and Utilities

### Action Test Helpers
Create reusable helpers for actions testing:

```php
function createEventWithVenue(array $eventData = [], array $venueData = []): Event
{
    $venue = Venue::factory()->create($venueData);
    
    return Event::factory()->create(array_merge([
        'venue_id' => $venue->id,
    ], $eventData));
}

function assertActionDispatchedEvents($component, array $events): void
{
    foreach ($events as $event => $parameters) {
        if (is_numeric($event)) {
            $component->assertDispatched($parameters);
        } else {
            $component->assertDispatched($event, $parameters);
        }
    }
}

function assertActionFailed($component, string $message = null): void
{
    $component->assertDispatched('action-failed');
    
    if ($message) {
        $component->assertDispatched('show-error', $message);
    }
}
```

### Mock Helpers
Create mocks for testing complex scenarios:

```php
function mockDatabaseError(): void
{
    DB::shouldReceive('beginTransaction')->andThrow(new \Exception('Database error'));
    DB::shouldReceive('rollBack')->once();
}

function mockPermissionError(): void
{
    Gate::shouldReceive('authorize')->andThrow(new AuthorizationException('Insufficient permissions'));
}

function mockValidationError(): void
{
    Validator::shouldReceive('make')->andReturn(
        Mockery::mock()->shouldReceive('fails')->andReturn(true)->getMock()
    );
}
```

## Best Practices

### Test Organization
- Group related tests using `describe()` blocks
- Test both successful and failure scenarios
- Cover authorization and permission checks
- Test event dispatching and handling

### Business Logic Testing
- Test complex business operations thoroughly
- Verify business rule enforcement
- Test edge cases and boundary conditions
- Ensure proper error handling

### Integration Testing
- Test component communication
- Verify event dispatching and handling
- Test cross-component interactions
- Ensure proper state management

### Performance Considerations
- Test action execution efficiency
- Monitor memory usage during operations
- Test bulk operation performance
- Verify query optimization

## Related Documentation

- [Testing Guide](testing-guide.md) - Main testing overview
- [Form Testing](testing-forms.md) - Form component testing
- [Modal Testing](testing-modals.md) - Modal component testing
- [Table Testing](testing-tables.md) - Table component testing
- [Component Architecture](../../architecture/livewire/component-architecture.md) - Overall architecture