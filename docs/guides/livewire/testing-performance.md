# Performance Testing

## Overview

This guide covers performance testing strategies for Livewire components in Ringside, including query optimization, memory usage, response times, and load testing. Performance testing ensures components remain responsive and efficient under various conditions.

## Performance Testing Philosophy

### Why Performance Testing Matters

Performance testing is crucial for Livewire applications because:
- Components handle user interactions in real-time
- Database queries can compound quickly
- Memory usage affects user experience
- Network requests impact responsiveness
- Component rendering affects perceived performance

### Testing Approach

Focus on testing these performance aspects:
- **Query Efficiency**: Number and complexity of database queries
- **Memory Usage**: Memory consumption during component operations
- **Response Time**: Time taken for component actions
- **Rendering Performance**: Time to render components
- **Resource Utilization**: CPU and memory usage patterns

## Database Query Performance

### Query Count Testing

Test that components use an efficient number of queries:

```php
use Illuminate\Support\Facades\DB;

describe('EventsTable Query Performance', function () {
    test('loads events with minimal queries', function () {
        Event::factory()->count(50)->create();
        
        DB::flushQueryLog();
        DB::enableQueryLog();
        
        $component = Livewire::test(EventsTable::class);
        
        $queries = DB::getQueryLog();
        
        expect(count($queries))->toBeLessThan(5);
    });
    
    test('pagination does not increase query count', function () {
        Event::factory()->count(100)->create();
        
        DB::flushQueryLog();
        DB::enableQueryLog();
        
        $component = Livewire::test(EventsTable::class)
            ->call('nextPage');
        
        $queries = DB::getQueryLog();
        
        expect(count($queries))->toBeLessThan(5);
    });
});
```

### N+1 Query Prevention

Test that components avoid N+1 query problems:

```php
test('table avoids N+1 queries when loading relationships', function () {
    $venues = Venue::factory()->count(10)->create();
    
    foreach ($venues as $venue) {
        Event::factory()->create(['venue_id' => $venue->id]);
    }
    
    DB::flushQueryLog();
    DB::enableQueryLog();
    
    $component = Livewire::test(EventsTable::class);
    
    $queries = DB::getQueryLog();
    
    // Should not have separate queries for each venue
    expect(count($queries))->toBeLessThan(5);
});

test('form avoids N+1 queries when loading options', function () {
    Venue::factory()->count(50)->create();
    
    DB::flushQueryLog();
    DB::enableQueryLog();
    
    $component = Livewire::test(FormModal::class)
        ->call('openModal');
    
    $queries = DB::getQueryLog();
    
    // Should load all venues in one query
    expect(count($queries))->toBeLessThan(3);
});
```

### Query Complexity Testing

Test query complexity and optimization:

```php
test('filtering uses efficient queries', function () {
    Event::factory()->count(100)->create();
    
    DB::flushQueryLog();
    DB::enableQueryLog();
    
    $component = Livewire::test(EventsTable::class)
        ->set('search', 'Test Event')
        ->set('filters.status', 'active');
    
    $queries = DB::getQueryLog();
    
    // Verify efficient query structure
    expect(count($queries))->toBeLessThan(3);
    
    // Check for proper WHERE clause usage
    $searchQuery = collect($queries)->first(function ($query) {
        return str_contains($query['query'], 'WHERE');
    });
    
    expect($searchQuery)->not->toBeNull();
});

test('sorting uses database-level ordering', function () {
    Event::factory()->count(100)->create();
    
    DB::flushQueryLog();
    DB::enableQueryLog();
    
    $component = Livewire::test(EventsTable::class)
        ->set('sortBy', 'name')
        ->set('sortDirection', 'desc');
    
    $queries = DB::getQueryLog();
    
    // Verify ORDER BY clause is used
    $sortQuery = collect($queries)->first(function ($query) {
        return str_contains($query['query'], 'ORDER BY');
    });
    
    expect($sortQuery)->not->toBeNull();
});
```

## Memory Usage Testing

### Memory Consumption Monitoring

Test memory usage during component operations:

```php
describe('Component Memory Usage', function () {
    test('table component manages memory efficiently', function () {
        Event::factory()->count(1000)->create();
        
        $initialMemory = memory_get_usage();
        
        $component = Livewire::test(EventsTable::class);
        
        $afterLoadMemory = memory_get_usage();
        $loadMemoryUsage = $afterLoadMemory - $initialMemory;
        
        expect($loadMemoryUsage)->toBeLessThan(10 * 1024 * 1024); // 10MB
        
        // Test pagination doesn't leak memory
        $component->call('nextPage');
        $afterPageMemory = memory_get_usage();
        $pageMemoryUsage = $afterPageMemory - $afterLoadMemory;
        
        expect($pageMemoryUsage)->toBeLessThan(1 * 1024 * 1024); // 1MB
    });
    
    test('form component releases memory after submission', function () {
        $initialMemory = memory_get_usage();
        
        $component = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Test Event')
            ->call('save');
        
        $finalMemory = memory_get_usage();
        $memoryUsed = $finalMemory - $initialMemory;
        
        expect($memoryUsed)->toBeLessThan(5 * 1024 * 1024); // 5MB
    });
});
```

### Memory Leak Detection

Test for memory leaks in component operations:

```php
test('repeated component operations do not leak memory', function () {
    $initialMemory = memory_get_usage();
    
    // Perform operation multiple times
    for ($i = 0; $i < 100; $i++) {
        $component = Livewire::test(EventsTable::class);
        $component->call('refresh');
        unset($component);
    }
    
    // Force garbage collection
    gc_collect_cycles();
    
    $finalMemory = memory_get_usage();
    $memoryGrowth = $finalMemory - $initialMemory;
    
    // Memory growth should be minimal
    expect($memoryGrowth)->toBeLessThan(50 * 1024 * 1024); // 50MB
});

test('modal open/close cycles do not leak memory', function () {
    $initialMemory = memory_get_usage();
    
    $component = Livewire::test(FormModal::class);
    
    // Perform multiple open/close cycles
    for ($i = 0; $i < 50; $i++) {
        $component->call('openModal');
        $component->call('closeModal');
    }
    
    $finalMemory = memory_get_usage();
    $memoryGrowth = $finalMemory - $initialMemory;
    
    expect($memoryGrowth)->toBeLessThan(10 * 1024 * 1024); // 10MB
});
```

## Response Time Testing

### Action Response Time

Test response times for component actions:

```php
describe('Component Response Times', function () {
    test('table loading completes within acceptable time', function () {
        Event::factory()->count(500)->create();
        
        $startTime = microtime(true);
        
        $component = Livewire::test(EventsTable::class);
        
        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;
        
        expect($responseTime)->toBeLessThan(2.0); // 2 seconds
    });
    
    test('form submission completes within acceptable time', function () {
        $venue = Venue::factory()->create();
        
        $startTime = microtime(true);
        
        $component = Livewire::test(FormModal::class)
            ->call('openModal')
            ->set('form.name', 'Test Event')
            ->set('form.venue_id', $venue->id)
            ->call('save');
        
        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;
        
        expect($responseTime)->toBeLessThan(1.0); // 1 second
    });
});
```

### Filtering and Sorting Performance

Test performance of filtering and sorting operations:

```php
test('search filtering performs efficiently', function () {
    Event::factory()->count(1000)->create();
    
    $startTime = microtime(true);
    
    $component = Livewire::test(EventsTable::class)
        ->set('search', 'Test Event');
    
    $endTime = microtime(true);
    $responseTime = $endTime - $startTime;
    
    expect($responseTime)->toBeLessThan(0.5); // 500ms
});

test('complex filtering performs efficiently', function () {
    $venues = Venue::factory()->count(10)->create();
    
    foreach ($venues as $venue) {
        Event::factory()->count(100)->create(['venue_id' => $venue->id]);
    }
    
    $startTime = microtime(true);
    
    $component = Livewire::test(EventsTable::class)
        ->set('search', 'Event')
        ->set('filters.venue_id', $venues->first()->id)
        ->set('filters.status', 'active');
    
    $endTime = microtime(true);
    $responseTime = $endTime - $startTime;
    
    expect($responseTime)->toBeLessThan(1.0); // 1 second
});
```

## Rendering Performance

### Component Rendering Time

Test component rendering performance:

```php
describe('Component Rendering Performance', function () {
    test('table renders efficiently with large datasets', function () {
        Event::factory()->count(100)->create();
        
        $startTime = microtime(true);
        
        $component = Livewire::test(EventsTable::class);
        $html = $component->render();
        
        $endTime = microtime(true);
        $renderTime = $endTime - $startTime;
        
        expect($renderTime)->toBeLessThan(0.5); // 500ms
        expect(strlen($html))->toBeGreaterThan(1000); // Actual content
    });
    
    test('modal renders efficiently', function () {
        $startTime = microtime(true);
        
        $component = Livewire::test(FormModal::class)
            ->call('openModal');
        
        $html = $component->render();
        
        $endTime = microtime(true);
        $renderTime = $endTime - $startTime;
        
        expect($renderTime)->toBeLessThan(0.2); // 200ms
        expect(strlen($html))->toBeGreaterThan(500); // Actual content
    });
});
```

### Template Compilation Performance

Test template compilation and caching:

```php
test('template compilation does not affect performance', function () {
    // Clear view cache
    Artisan::call('view:clear');
    
    $startTime = microtime(true);
    
    $component = Livewire::test(EventsTable::class);
    $html = $component->render();
    
    $endTime = microtime(true);
    $firstRenderTime = $endTime - $startTime;
    
    // Second render should be faster (cached)
    $startTime = microtime(true);
    
    $component = Livewire::test(EventsTable::class);
    $html = $component->render();
    
    $endTime = microtime(true);
    $secondRenderTime = $endTime - $startTime;
    
    expect($secondRenderTime)->toBeLessThan($firstRenderTime);
    expect($secondRenderTime)->toBeLessThan(0.1); // 100ms
});
```

## Load Testing

### Concurrent Component Usage

Test component behavior under concurrent load:

```php
describe('Component Load Testing', function () {
    test('handles multiple concurrent table requests', function () {
        Event::factory()->count(100)->create();
        
        $startTime = microtime(true);
        
        $components = [];
        
        // Simulate multiple concurrent requests
        for ($i = 0; $i < 10; $i++) {
            $components[] = Livewire::test(EventsTable::class);
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        expect($totalTime)->toBeLessThan(5.0); // 5 seconds for 10 concurrent
        expect(count($components))->toBe(10);
    });
    
    test('handles multiple concurrent form submissions', function () {
        $venues = Venue::factory()->count(10)->create();
        
        $startTime = microtime(true);
        
        $components = [];
        
        // Simulate multiple concurrent form submissions
        for ($i = 0; $i < 5; $i++) {
            $component = Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('form.name', "Event {$i}")
                ->set('form.venue_id', $venues->random()->id)
                ->call('save');
            
            $components[] = $component;
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        expect($totalTime)->toBeLessThan(3.0); // 3 seconds for 5 concurrent
        expect(Event::count())->toBe(5);
    });
});
```

### Resource Utilization Testing

Test resource utilization under load:

```php
test('maintains reasonable resource utilization under load', function () {
    Event::factory()->count(1000)->create();
    
    $initialMemory = memory_get_usage();
    
    // Simulate heavy usage
    for ($i = 0; $i < 20; $i++) {
        $component = Livewire::test(EventsTable::class)
            ->set('search', 'Event ' . $i % 10)
            ->call('nextPage');
    }
    
    $finalMemory = memory_get_usage();
    $memoryUsed = $finalMemory - $initialMemory;
    
    expect($memoryUsed)->toBeLessThan(100 * 1024 * 1024); // 100MB
});
```

## Performance Monitoring

### Query Performance Monitoring

Monitor and assert query performance:

```php
class QueryPerformanceMonitor
{
    private array $queries = [];
    
    public function startMonitoring(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
    }
    
    public function stopMonitoring(): array
    {
        $this->queries = DB::getQueryLog();
        DB::disableQueryLog();
        
        return $this->queries;
    }
    
    public function assertQueryCountLessThan(int $maxCount): void
    {
        expect(count($this->queries))->toBeLessThan($maxCount);
    }
    
    public function assertNoSlowQueries(float $maxTime = 0.1): void
    {
        foreach ($this->queries as $query) {
            expect($query['time'])->toBeLessThan($maxTime * 1000); // Convert to milliseconds
        }
    }
    
    public function assertNoNPlusOneQueries(): void
    {
        $duplicateQueries = collect($this->queries)
            ->groupBy('query')
            ->filter(function ($group) {
                return count($group) > 1;
            });
        
        expect($duplicateQueries->count())->toBe(0);
    }
}
```

### Memory Performance Monitoring

Monitor memory usage patterns:

```php
class MemoryPerformanceMonitor
{
    private int $initialMemory;
    private int $peakMemory;
    
    public function startMonitoring(): void
    {
        $this->initialMemory = memory_get_usage();
        $this->peakMemory = memory_get_peak_usage();
    }
    
    public function assertMemoryUsageLessThan(int $maxBytes): void
    {
        $currentMemory = memory_get_usage();
        $memoryUsed = $currentMemory - $this->initialMemory;
        
        expect($memoryUsed)->toBeLessThan($maxBytes);
    }
    
    public function assertNoPeakMemoryIncrease(): void
    {
        $currentPeak = memory_get_peak_usage();
        $peakIncrease = $currentPeak - $this->peakMemory;
        
        expect($peakIncrease)->toBeLessThan(10 * 1024 * 1024); // 10MB
    }
}
```

### Response Time Monitoring

Monitor response times:

```php
class ResponseTimeMonitor
{
    private float $startTime;
    
    public function startTiming(): void
    {
        $this->startTime = microtime(true);
    }
    
    public function assertResponseTimeLessThan(float $maxSeconds): void
    {
        $endTime = microtime(true);
        $responseTime = $endTime - $this->startTime;
        
        expect($responseTime)->toBeLessThan($maxSeconds);
    }
}
```

## Performance Testing Utilities

### Test Data Generators

Create efficient test data for performance testing:

```php
function createLargeDataset(int $eventCount = 1000): void
{
    DB::disableQueryLog();
    
    $venues = Venue::factory()->count(50)->create();
    
    $events = [];
    for ($i = 0; $i < $eventCount; $i++) {
        $events[] = [
            'name' => "Event {$i}",
            'venue_id' => $venues->random()->id,
            'date' => fake()->dateTimeBetween('now', '+1 year'),
            'status' => fake()->randomElement(['active', 'inactive', 'cancelled']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
    
    // Use bulk insert for performance
    DB::table('events')->insert($events);
    
    DB::enableQueryLog();
}

function createTestDataWithRelationships(int $count = 100): void
{
    DB::disableQueryLog();
    
    $venues = Venue::factory()->count(20)->create();
    $events = Event::factory()->count($count)->create([
        'venue_id' => fn() => $venues->random()->id,
    ]);
    
    // Create matches for events
    foreach ($events as $event) {
        Match::factory()->count(rand(1, 5))->create([
            'event_id' => $event->id,
        ]);
    }
    
    DB::enableQueryLog();
}
```

### Performance Assertion Helpers

Create reusable performance assertions:

```php
function assertPerformantQuery(callable $operation, array $constraints = []): void
{
    $queryMonitor = new QueryPerformanceMonitor();
    $memoryMonitor = new MemoryPerformanceMonitor();
    $timeMonitor = new ResponseTimeMonitor();
    
    $queryMonitor->startMonitoring();
    $memoryMonitor->startMonitoring();
    $timeMonitor->startTiming();
    
    $operation();
    
    $queryMonitor->stopMonitoring();
    
    // Apply constraints
    if (isset($constraints['max_queries'])) {
        $queryMonitor->assertQueryCountLessThan($constraints['max_queries']);
    }
    
    if (isset($constraints['max_memory'])) {
        $memoryMonitor->assertMemoryUsageLessThan($constraints['max_memory']);
    }
    
    if (isset($constraints['max_time'])) {
        $timeMonitor->assertResponseTimeLessThan($constraints['max_time']);
    }
    
    if (isset($constraints['no_slow_queries'])) {
        $queryMonitor->assertNoSlowQueries($constraints['no_slow_queries']);
    }
    
    if (isset($constraints['no_n_plus_one'])) {
        $queryMonitor->assertNoNPlusOneQueries();
    }
}
```

## Performance Benchmarking

### Baseline Performance Tests

Establish performance baselines:

```php
describe('Performance Baselines', function () {
    test('table component baseline performance', function () {
        Event::factory()->count(100)->create();
        
        assertPerformantQuery(function () {
            Livewire::test(EventsTable::class);
        }, [
            'max_queries' => 5,
            'max_memory' => 10 * 1024 * 1024, // 10MB
            'max_time' => 1.0, // 1 second
            'no_slow_queries' => 0.1, // 100ms
            'no_n_plus_one' => true,
        ]);
    });
    
    test('form submission baseline performance', function () {
        $venue = Venue::factory()->create();
        
        assertPerformantQuery(function () use ($venue) {
            Livewire::test(FormModal::class)
                ->call('openModal')
                ->set('form.name', 'Test Event')
                ->set('form.venue_id', $venue->id)
                ->call('save');
        }, [
            'max_queries' => 10,
            'max_memory' => 5 * 1024 * 1024, // 5MB
            'max_time' => 0.5, // 500ms
        ]);
    });
});
```

### Performance Regression Detection

Detect performance regressions:

```php
test('performance has not regressed', function () {
    // Store baseline metrics
    $baselineMetrics = [
        'table_load_time' => 0.5,
        'table_query_count' => 3,
        'form_submit_time' => 0.3,
        'form_query_count' => 5,
    ];
    
    // Test current performance
    Event::factory()->count(100)->create();
    
    // Test table performance
    $startTime = microtime(true);
    DB::flushQueryLog();
    DB::enableQueryLog();
    
    Livewire::test(EventsTable::class);
    
    $tableTime = microtime(true) - $startTime;
    $tableQueries = count(DB::getQueryLog());
    
    expect($tableTime)->toBeLessThan($baselineMetrics['table_load_time'] * 1.2); // 20% tolerance
    expect($tableQueries)->toBeLessThanOrEqual($baselineMetrics['table_query_count']);
    
    // Test form performance
    $venue = Venue::factory()->create();
    
    $startTime = microtime(true);
    DB::flushQueryLog();
    DB::enableQueryLog();
    
    Livewire::test(FormModal::class)
        ->call('openModal')
        ->set('form.name', 'Test Event')
        ->set('form.venue_id', $venue->id)
        ->call('save');
    
    $formTime = microtime(true) - $startTime;
    $formQueries = count(DB::getQueryLog());
    
    expect($formTime)->toBeLessThan($baselineMetrics['form_submit_time'] * 1.2); // 20% tolerance
    expect($formQueries)->toBeLessThanOrEqual($baselineMetrics['form_query_count']);
});
```

## Environment-Specific Performance Testing

### Development Environment

Performance tests for development environment:

```php
describe('Development Environment Performance', function () {
    test('components perform adequately with debug mode enabled', function () {
        config(['app.debug' => true]);
        
        Event::factory()->count(50)->create();
        
        $startTime = microtime(true);
        
        Livewire::test(EventsTable::class);
        
        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;
        
        // More lenient in debug mode
        expect($responseTime)->toBeLessThan(3.0);
    });
});
```

### Production Environment

Performance tests for production environment:

```php
describe('Production Environment Performance', function () {
    test('components meet production performance requirements', function () {
        config(['app.debug' => false]);
        
        Event::factory()->count(500)->create();
        
        assertPerformantQuery(function () {
            Livewire::test(EventsTable::class);
        }, [
            'max_queries' => 3,
            'max_memory' => 20 * 1024 * 1024, // 20MB
            'max_time' => 1.0, // 1 second
            'no_slow_queries' => 0.05, // 50ms
            'no_n_plus_one' => true,
        ]);
    });
});
```

## Best Practices

### Performance Testing Strategy
- Test with realistic data volumes
- Monitor multiple performance metrics
- Set reasonable performance thresholds
- Test under various load conditions
- Use baseline measurements for comparison

### Query Optimization
- Test for N+1 query problems
- Verify proper eager loading
- Monitor query execution times
- Test index usage effectiveness
- Validate pagination efficiency

### Memory Management
- Monitor memory usage patterns
- Test for memory leaks
- Validate garbage collection
- Test with large datasets
- Monitor peak memory usage

### Response Time Testing
- Set realistic time expectations
- Test under different loads
- Monitor network-dependent operations
- Test caching effectiveness
- Validate user experience metrics

## Related Documentation

- [Testing Guide](testing-guide.md) - Main testing overview
- [Best Practices](testing-best-practices.md) - Testing best practices
- [Advanced Testing](testing-advanced.md) - Advanced testing patterns
- [Component Architecture](../../architecture/livewire/component-architecture.md) - Overall architecture