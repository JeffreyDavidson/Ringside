# Table Component Testing

## Overview

This guide covers comprehensive testing strategies for Livewire table components in Ringside. Table components handle data display, filtering, sorting, and pagination across all domain entities, providing consistent data presentation interfaces.

## Table Component Architecture

### BaseTable Testing
Most table components extend `BaseTable` and follow consistent patterns:

```php
/**
 * @extends BaseTable<Event>
 */
class EventsTable extends BaseTable
{
    protected function getModelClass(): string { return Event::class; }
    protected function getColumns(): array { /* ... */ }
    protected function getFilters(): array { /* ... */ }
}
```

## Testing Structure

### Basic Test Setup
```php
use App\Livewire\Events\Tables\EventsTable;
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
Organize table tests using clear `describe()` blocks:

```php
describe('EventsTable Configuration', function () {
    // Test table configuration and setup
});

describe('EventsTable Data Display', function () {
    // Test data rendering and formatting
});

describe('EventsTable Filtering', function () {
    // Test filtering functionality
});

describe('EventsTable Sorting', function () {
    // Test sorting functionality
});

describe('EventsTable Pagination', function () {
    // Test pagination functionality
});
```

## Configuration Testing

### Table Configuration
Test that table components are configured correctly:

```php
describe('EventsTable Configuration', function () {
    test('returns correct model class', function () {
        $table = new EventsTable();
        
        expect($table->getModelClass())->toBe(Event::class);
    });
    
    test('has correct default per page setting', function () {
        $component = Livewire::test(EventsTable::class);
        
        expect($component->instance()->perPage)->toBe(15);
    });
    
    test('has correct default sort settings', function () {
        $component = Livewire::test(EventsTable::class);
        
        expect($component->instance()->sortBy)->toBe('id');
        expect($component->instance()->sortDirection)->toBe('asc');
    });
});
```

### Column Configuration
Test table column configuration:

```php
test('displays correct table headers', function () {
    $component = Livewire::test(EventsTable::class);
    
    $component->assertSee('Name');
    $component->assertSee('Date');
    $component->assertSee('Venue');
    $component->assertSee('Actions');
});

test('has correct column configuration', function () {
    $table = new EventsTable();
    $columns = $table->getColumns();
    
    expect($columns)->toHaveKey('name');
    expect($columns)->toHaveKey('date');
    expect($columns)->toHaveKey('venue');
    expect($columns)->toHaveKey('actions');
});
```

## Data Display Testing

### Basic Data Display
Test basic data rendering:

```php
describe('EventsTable Data Display', function () {
    test('displays events correctly', function () {
        $events = Event::factory()->count(3)->create();
        
        $component = Livewire::test(EventsTable::class);
        
        foreach ($events as $event) {
            $component->assertSee($event->name);
            $component->assertSee($event->date->format('M j, Y'));
        }
    });
    
    test('displays event venue names', function () {
        $venue = Venue::factory()->create(['name' => 'Test Arena']);
        $event = Event::factory()->create(['venue_id' => $venue->id]);
        
        $component = Livewire::test(EventsTable::class);
        
        $component->assertSee('Test Arena');
    });
    
    test('displays empty state when no events', function () {
        $component = Livewire::test(EventsTable::class);
        
        $component->assertSee('No events found');
    });
});
```

### Data Formatting
Test data formatting and display:

```php
test('formats dates correctly', function () {
    $event = Event::factory()->create([
        'date' => '2024-01-15 14:30:00',
    ]);
    
    $component = Livewire::test(EventsTable::class);
    
    $component->assertSee('Jan 15, 2024');
    $component->assertSee('2:30 PM');
});

test('handles null values gracefully', function () {
    $event = Event::factory()->create([
        'date' => null,
        'venue_id' => null,
    ]);
    
    $component = Livewire::test(EventsTable::class);
    
    $component->assertSee($event->name);
    $component->assertSee('TBD'); // Or whatever placeholder is used
});

test('displays computed properties correctly', function () {
    $event = Event::factory()->create();
    
    $component = Livewire::test(EventsTable::class);
    
    // Test computed properties like status, calculated fields, etc.
    $component->assertSee($event->status);
});
```

### Relationship Data Display
Test relationship data display:

```php
test('displays relationship data correctly', function () {
    $venue = Venue::factory()->create(['name' => 'Madison Square Garden']);
    $event = Event::factory()->create(['venue_id' => $venue->id]);
    
    $component = Livewire::test(EventsTable::class);
    
    $component->assertSee('Madison Square Garden');
});

test('handles missing relationships gracefully', function () {
    $event = Event::factory()->create(['venue_id' => null]);
    
    $component = Livewire::test(EventsTable::class);
    
    $component->assertSee($event->name);
    $component->assertSee('No venue assigned');
});
```

## Filtering Testing

### Basic Filtering
Test search and filter functionality:

```php
describe('EventsTable Filtering', function () {
    test('filters events by name', function () {
        Event::factory()->create(['name' => 'WrestleMania']);
        Event::factory()->create(['name' => 'SummerSlam']);
        
        $component = Livewire::test(EventsTable::class)
            ->set('search', 'WrestleMania');
        
        $component->assertSee('WrestleMania');
        $component->assertDontSee('SummerSlam');
    });
    
    test('search is case insensitive', function () {
        Event::factory()->create(['name' => 'WrestleMania']);
        Event::factory()->create(['name' => 'SummerSlam']);
        
        $component = Livewire::test(EventsTable::class)
            ->set('search', 'wrestlemania');
        
        $component->assertSee('WrestleMania');
        $component->assertDontSee('SummerSlam');
    });
    
    test('clears search results when search is empty', function () {
        Event::factory()->create(['name' => 'WrestleMania']);
        Event::factory()->create(['name' => 'SummerSlam']);
        
        $component = Livewire::test(EventsTable::class)
            ->set('search', 'WrestleMania');
        
        $component->assertSee('WrestleMania');
        $component->assertDontSee('SummerSlam');
        
        $component->set('search', '');
        
        $component->assertSee('WrestleMania');
        $component->assertSee('SummerSlam');
    });
});
```

### Advanced Filtering
Test advanced filtering options:

```php
test('filters events by date range', function () {
    Event::factory()->create(['date' => '2024-01-01']);
    Event::factory()->create(['date' => '2024-06-01']);
    
    $component = Livewire::test(EventsTable::class)
        ->set('filters.date_from', '2024-01-01')
        ->set('filters.date_to', '2024-03-31');
    
    $component->assertSee('2024-01-01');
    $component->assertDontSee('2024-06-01');
});

test('filters events by venue', function () {
    $venue1 = Venue::factory()->create(['name' => 'Arena 1']);
    $venue2 = Venue::factory()->create(['name' => 'Arena 2']);
    
    Event::factory()->create(['venue_id' => $venue1->id]);
    Event::factory()->create(['venue_id' => $venue2->id]);
    
    $component = Livewire::test(EventsTable::class)
        ->set('filters.venue_id', $venue1->id);
    
    $component->assertSee('Arena 1');
    $component->assertDontSee('Arena 2');
});

test('filters events by status', function () {
    Event::factory()->create(['status' => 'scheduled']);
    Event::factory()->create(['status' => 'completed']);
    
    $component = Livewire::test(EventsTable::class)
        ->set('filters.status', 'scheduled');
    
    $component->assertSee('scheduled');
    $component->assertDontSee('completed');
});
```

### Filter Combinations
Test filter combinations:

```php
test('combines multiple filters correctly', function () {
    $venue = Venue::factory()->create(['name' => 'Test Arena']);
    
    Event::factory()->create([
        'name' => 'WrestleMania',
        'venue_id' => $venue->id,
        'status' => 'scheduled',
    ]);
    
    Event::factory()->create([
        'name' => 'SummerSlam',
        'venue_id' => $venue->id,
        'status' => 'completed',
    ]);
    
    $component = Livewire::test(EventsTable::class)
        ->set('search', 'WrestleMania')
        ->set('filters.venue_id', $venue->id)
        ->set('filters.status', 'scheduled');
    
    $component->assertSee('WrestleMania');
    $component->assertDontSee('SummerSlam');
});

test('filter reset clears all filters', function () {
    Event::factory()->create(['name' => 'WrestleMania']);
    Event::factory()->create(['name' => 'SummerSlam']);
    
    $component = Livewire::test(EventsTable::class)
        ->set('search', 'WrestleMania')
        ->set('filters.status', 'scheduled')
        ->call('resetFilters');
    
    $component->assertSee('WrestleMania');
    $component->assertSee('SummerSlam');
    expect($component->instance()->search)->toBe('');
    expect($component->instance()->filters)->toBe([]);
});
```

## Sorting Testing

### Basic Sorting
Test sorting functionality:

```php
describe('EventsTable Sorting', function () {
    test('sorts events by name ascending', function () {
        Event::factory()->create(['name' => 'Z Event']);
        Event::factory()->create(['name' => 'A Event']);
        
        $component = Livewire::test(EventsTable::class)
            ->set('sortBy', 'name')
            ->set('sortDirection', 'asc');
        
        $html = $component->render();
        $aPosition = strpos($html, 'A Event');
        $zPosition = strpos($html, 'Z Event');
        
        expect($aPosition)->toBeLessThan($zPosition);
    });
    
    test('sorts events by name descending', function () {
        Event::factory()->create(['name' => 'A Event']);
        Event::factory()->create(['name' => 'Z Event']);
        
        $component = Livewire::test(EventsTable::class)
            ->set('sortBy', 'name')
            ->set('sortDirection', 'desc');
        
        $html = $component->render();
        $aPosition = strpos($html, 'A Event');
        $zPosition = strpos($html, 'Z Event');
        
        expect($zPosition)->toBeLessThan($aPosition);
    });
});
```

### Date Sorting
Test date-based sorting:

```php
test('sorts events by date ascending', function () {
    Event::factory()->create(['date' => '2024-06-01']);
    Event::factory()->create(['date' => '2024-01-01']);
    
    $component = Livewire::test(EventsTable::class)
        ->set('sortBy', 'date')
        ->set('sortDirection', 'asc');
    
    $html = $component->render();
    $januaryPosition = strpos($html, '2024-01-01');
    $junePosition = strpos($html, '2024-06-01');
    
    expect($januaryPosition)->toBeLessThan($junePosition);
});

test('sorts events by date descending', function () {
    Event::factory()->create(['date' => '2024-01-01']);
    Event::factory()->create(['date' => '2024-06-01']);
    
    $component = Livewire::test(EventsTable::class)
        ->set('sortBy', 'date')
        ->set('sortDirection', 'desc');
    
    $html = $component->render();
    $januaryPosition = strpos($html, '2024-01-01');
    $junePosition = strpos($html, '2024-06-01');
    
    expect($junePosition)->toBeLessThan($januaryPosition);
});
```

### Relationship Sorting
Test sorting by relationship data:

```php
test('sorts events by venue name', function () {
    $venue1 = Venue::factory()->create(['name' => 'Z Arena']);
    $venue2 = Venue::factory()->create(['name' => 'A Arena']);
    
    Event::factory()->create(['venue_id' => $venue1->id]);
    Event::factory()->create(['venue_id' => $venue2->id]);
    
    $component = Livewire::test(EventsTable::class)
        ->set('sortBy', 'venue.name')
        ->set('sortDirection', 'asc');
    
    $html = $component->render();
    $aPosition = strpos($html, 'A Arena');
    $zPosition = strpos($html, 'Z Arena');
    
    expect($aPosition)->toBeLessThan($zPosition);
});
```

### Sort State Management
Test sorting state management:

```php
test('toggles sort direction when clicking same column', function () {
    $component = Livewire::test(EventsTable::class)
        ->set('sortBy', 'name')
        ->set('sortDirection', 'asc')
        ->call('sortBy', 'name');
    
    expect($component->instance()->sortDirection)->toBe('desc');
});

test('changes sort column when clicking different column', function () {
    $component = Livewire::test(EventsTable::class)
        ->set('sortBy', 'name')
        ->set('sortDirection', 'desc')
        ->call('sortBy', 'date');
    
    expect($component->instance()->sortBy)->toBe('date');
    expect($component->instance()->sortDirection)->toBe('asc');
});
```

## Pagination Testing

### Basic Pagination
Test pagination functionality:

```php
describe('EventsTable Pagination', function () {
    test('paginates events correctly', function () {
        Event::factory()->count(25)->create();
        
        $component = Livewire::test(EventsTable::class);
        
        $component->assertSee('1-15 of 25');
        $component->assertSee('Next');
    });
    
    test('navigates to next page', function () {
        Event::factory()->count(25)->create();
        
        $component = Livewire::test(EventsTable::class)
            ->call('nextPage');
        
        $component->assertSee('16-25 of 25');
        $component->assertSee('Previous');
    });
    
    test('navigates to previous page', function () {
        Event::factory()->count(25)->create();
        
        $component = Livewire::test(EventsTable::class)
            ->call('nextPage')
            ->call('previousPage');
        
        $component->assertSee('1-15 of 25');
    });
});
```

### Pagination with Filters
Test pagination with filters applied:

```php
test('pagination works with filters', function () {
    Event::factory()->count(20)->create(['name' => 'Test Event']);
    Event::factory()->count(10)->create(['name' => 'Other Event']);
    
    $component = Livewire::test(EventsTable::class)
        ->set('search', 'Test Event');
    
    $component->assertSee('1-15 of 20');
    
    $component->call('nextPage');
    
    $component->assertSee('16-20 of 20');
});

test('pagination resets when filters change', function () {
    Event::factory()->count(30)->create(['name' => 'Test Event']);
    Event::factory()->count(10)->create(['name' => 'Other Event']);
    
    $component = Livewire::test(EventsTable::class)
        ->set('search', 'Test Event')
        ->call('nextPage');
    
    expect($component->instance()->page)->toBe(2);
    
    $component->set('search', 'Other Event');
    
    expect($component->instance()->page)->toBe(1);
});
```

### Per Page Options
Test per-page size options:

```php
test('changes per page size correctly', function () {
    Event::factory()->count(50)->create();
    
    $component = Livewire::test(EventsTable::class)
        ->set('perPage', 25);
    
    $component->assertSee('1-25 of 50');
    $component->assertSee('Next');
});

test('maintains filters when changing per page size', function () {
    Event::factory()->count(50)->create(['name' => 'Test Event']);
    
    $component = Livewire::test(EventsTable::class)
        ->set('search', 'Test Event')
        ->set('perPage', 25);
    
    $component->assertSee('1-25 of 50');
    expect($component->instance()->search)->toBe('Test Event');
});
```

## Action Testing

### Row Actions
Test row-level actions:

```php
describe('EventsTable Actions', function () {
    test('displays edit actions for each event', function () {
        $events = Event::factory()->count(3)->create();
        
        $component = Livewire::test(EventsTable::class);
        
        foreach ($events as $event) {
            $component->assertSee("Edit");
            $component->assertSee("Delete");
        }
    });
    
    test('edit action dispatches correct event', function () {
        $event = Event::factory()->create();
        
        $component = Livewire::test(EventsTable::class)
            ->call('edit', $event->id);
        
        $component->assertDispatched('open-edit-modal', $event->id);
    });
    
    test('delete action removes event', function () {
        $event = Event::factory()->create();
        
        $component = Livewire::test(EventsTable::class)
            ->call('delete', $event->id);
        
        expect(Event::find($event->id))->toBeNull();
        $component->assertDispatched('event-deleted');
    });
});
```

### Bulk Actions
Test bulk actions if supported:

```php
test('selects multiple events for bulk actions', function () {
    $events = Event::factory()->count(3)->create();
    
    $component = Livewire::test(EventsTable::class)
        ->set('selectedEvents', [$events[0]->id, $events[1]->id]);
    
    expect($component->instance()->selectedEvents)->toHaveCount(2);
});

test('bulk delete removes selected events', function () {
    $events = Event::factory()->count(3)->create();
    
    $component = Livewire::test(EventsTable::class)
        ->set('selectedEvents', [$events[0]->id, $events[1]->id])
        ->call('bulkDelete');
    
    expect(Event::find($events[0]->id))->toBeNull();
    expect(Event::find($events[1]->id))->toBeNull();
    expect(Event::find($events[2]->id))->not->toBeNull();
});
```

## Performance Testing

### Query Efficiency
Test query performance:

```php
describe('EventsTable Performance', function () {
    test('uses efficient queries for data loading', function () {
        Event::factory()->count(100)->create();
        
        $queryCount = 0;
        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });
        
        $component = Livewire::test(EventsTable::class);
        
        expect($queryCount)->toBeLessThan(5); // Reasonable query limit
    });
    
    test('eager loads relationships efficiently', function () {
        $venues = Venue::factory()->count(10)->create();
        
        foreach ($venues as $venue) {
            Event::factory()->create(['venue_id' => $venue->id]);
        }
        
        $queryCount = 0;
        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });
        
        $component = Livewire::test(EventsTable::class);
        
        // Should not have N+1 query problems
        expect($queryCount)->toBeLessThan(5);
    });
});
```

### Memory Usage
Test memory efficiency:

```php
test('handles large datasets efficiently', function () {
    Event::factory()->count(1000)->create();
    
    $initialMemory = memory_get_usage();
    
    $component = Livewire::test(EventsTable::class);
    
    $finalMemory = memory_get_usage();
    $memoryUsed = $finalMemory - $initialMemory;
    
    expect($memoryUsed)->toBeLessThan(50 * 1024 * 1024); // 50MB limit
});
```

## Advanced Testing

### Export Functionality
Test export features if supported:

```php
test('exports events to CSV', function () {
    Event::factory()->count(10)->create();
    
    $component = Livewire::test(EventsTable::class)
        ->call('export', 'csv');
    
    $component->assertDispatched('export-started');
});

test('exports filtered events only', function () {
    Event::factory()->count(10)->create(['name' => 'Test Event']);
    Event::factory()->count(5)->create(['name' => 'Other Event']);
    
    $component = Livewire::test(EventsTable::class)
        ->set('search', 'Test Event')
        ->call('export', 'csv');
    
    // Verify only filtered events are exported
    $component->assertDispatched('export-started');
});
```

### Real-time Updates
Test real-time updates if supported:

```php
test('updates table when new events are created', function () {
    $component = Livewire::test(EventsTable::class);
    
    expect($component->instance()->events)->toHaveCount(0);
    
    Event::factory()->create(['name' => 'New Event']);
    
    $component->call('refresh');
    
    expect($component->instance()->events)->toHaveCount(1);
});
```

### Custom Filters
Test custom filter implementations:

```php
test('custom date range filter works correctly', function () {
    Event::factory()->create(['date' => '2024-01-15']);
    Event::factory()->create(['date' => '2024-03-15']);
    Event::factory()->create(['date' => '2024-06-15']);
    
    $component = Livewire::test(EventsTable::class)
        ->call('applyDateRangeFilter', '2024-01-01', '2024-04-30');
    
    $component->assertSee('2024-01-15');
    $component->assertSee('2024-03-15');
    $component->assertDontSee('2024-06-15');
});
```

## Test Helpers and Utilities

### Table Test Helpers
Create reusable helpers for table testing:

```php
function createEventsWithVenues(int $count = 5): Collection
{
    return Event::factory()
        ->count($count)
        ->sequence(fn() => ['venue_id' => Venue::factory()->create()->id])
        ->create();
}

function assertTableShowsEvents($component, array $eventNames): void
{
    foreach ($eventNames as $name) {
        $component->assertSee($name);
    }
}

function assertTableHidesEvents($component, array $eventNames): void
{
    foreach ($eventNames as $name) {
        $component->assertDontSee($name);
    }
}
```

### Data Generators
Create test data generators:

```php
function createEventsForDateRange(string $start, string $end, int $count): Collection
{
    return Event::factory()
        ->count($count)
        ->sequence(fn() => [
            'date' => fake()->dateTimeBetween($start, $end),
        ])
        ->create();
}

function createEventsWithStatus(string $status, int $count): Collection
{
    return Event::factory()
        ->count($count)
        ->state(['status' => $status])
        ->create();
}
```

## Best Practices

### Test Organization
- Group related tests using `describe()` blocks
- Test each major feature area separately
- Include both positive and negative test cases
- Test edge cases and boundary conditions

### Data Management
- Use factories for consistent test data
- Clean up after each test
- Use meaningful test data that reflects real usage
- Test with various data sizes

### Performance Considerations
- Test query efficiency with larger datasets
- Monitor memory usage during tests
- Test pagination performance
- Verify relationship loading efficiency

### User Experience Testing
- Test filtering and sorting combinations
- Verify pagination works with filters
- Test empty states and error conditions
- Ensure responsive behavior

## Related Documentation

- [Testing Guide](testing-guide.md) - Main testing overview
- [Form Testing](testing-forms.md) - Form component testing
- [Modal Testing](testing-modals.md) - Modal component testing
- [Component Architecture](../../architecture/livewire/component-architecture.md) - Overall architecture