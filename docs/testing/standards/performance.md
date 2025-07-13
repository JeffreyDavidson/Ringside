# Performance Testing Standards

Guidelines for testing application performance and optimization.

## Overview

Performance testing ensures Ringside maintains acceptable response times and resource usage under various conditions. This includes database query optimization, memory management, and execution speed testing.

## Performance Test Categories

### Unit Test Performance
- **Fast Execution**: Unit tests should run in milliseconds
- **No Database**: Use mocks instead of database operations
- **No External Calls**: Mock all external dependencies
- **Memory Efficient**: Use minimal test data

### Integration Test Performance
- **Database Usage**: Only when testing interactions
- **Transaction Rollbacks**: Use database transactions for isolation
- **Minimal Data**: Create only necessary test data
- **Eager Loading**: Test eager loading to prevent N+1 queries

### Feature Test Performance
- **Response Times**: Monitor HTTP response times
- **Query Counts**: Track database query counts
- **Memory Usage**: Monitor memory consumption
- **Concurrent Testing**: Test under load conditions

## Database Performance Testing

### Query Count Testing
```php
test('avoids N+1 queries in wrestler list', function () {
    // Arrange
    $wrestlers = Wrestler::factory()->count(10)->create();
    $admin = administrator();
    
    // Act & Assert
    $this->assertDatabaseQueryCount(3, function () use ($admin) {
        actingAs($admin)->get(route('wrestlers.index'));
    });
});

test('eager loads relationships correctly', function () {
    // Arrange
    $wrestlers = Wrestler::factory()->count(5)->create();
    
    // Act
    $results = Wrestler::with(['currentEmployment', 'currentChampionships'])->get();
    
    // Assert
    foreach ($results as $wrestler) {
        expect($wrestler->relationLoaded('currentEmployment'))->toBeTrue();
        expect($wrestler->relationLoaded('currentChampionships'))->toBeTrue();
    }
});
```

### Query Optimization Testing
```php
test('complex query performs efficiently', function () {
    // Arrange
    $wrestlers = Wrestler::factory()->count(50)->create();
    $employments = WrestlerEmployment::factory()->count(100)->create();
    
    // Act
    $startTime = microtime(true);
    $bookableWrestlers = Wrestler::query()
        ->with(['currentEmployment', 'currentChampionships'])
        ->employed()
        ->notRetired()
        ->bookable()
        ->get();
    $endTime = microtime(true);
    
    // Assert
    expect($bookableWrestlers)->toBeCollection();
    expect($endTime - $startTime)->toBeLessThan(0.1); // 100ms max
});
```

### Index Usage Testing
```php
test('database indexes are used correctly', function () {
    // Arrange
    $wrestlers = Wrestler::factory()->count(1000)->create();
    
    // Act
    DB::enableQueryLog();
    $result = Wrestler::where('name', 'like', 'John%')->get();
    $queryLog = DB::getQueryLog();
    
    // Assert
    expect($queryLog)->toHaveCount(1);
    expect($queryLog[0]['query'])->toContain('where `name` like ?');
    // Verify index usage through query analysis
});
```

## Memory Performance Testing

### Memory Usage Testing
```php
test('factory creation uses acceptable memory', function () {
    // Arrange
    $initialMemory = memory_get_usage();
    
    // Act
    $wrestlers = Wrestler::factory()->count(100)->create();
    $finalMemory = memory_get_usage();
    
    // Assert
    $memoryUsed = $finalMemory - $initialMemory;
    expect($memoryUsed)->toBeLessThan(50 * 1024 * 1024); // 50MB max
});

test('large collection processing is memory efficient', function () {
    // Arrange
    $wrestlers = Wrestler::factory()->count(1000)->create();
    $initialMemory = memory_get_usage();
    
    // Act
    $processedCount = 0;
    Wrestler::chunk(100, function ($wrestlers) use (&$processedCount) {
        foreach ($wrestlers as $wrestler) {
            $processedCount++;
            // Process wrestler
        }
    });
    
    $finalMemory = memory_get_usage();
    
    // Assert
    expect($processedCount)->toBe(1000);
    expect($finalMemory - $initialMemory)->toBeLessThan(10 * 1024 * 1024); // 10MB max
});
```

### Memory Leak Testing
```php
test('repeated operations do not leak memory', function () {
    // Arrange
    $initialMemory = memory_get_usage();
    
    // Act
    for ($i = 0; $i < 100; $i++) {
        $wrestler = Wrestler::factory()->make();
        $wrestler->isEmployed();
        unset($wrestler);
    }
    
    $finalMemory = memory_get_usage();
    
    // Assert
    $memoryDiff = $finalMemory - $initialMemory;
    expect($memoryDiff)->toBeLessThan(1 * 1024 * 1024); // 1MB max growth
});
```

## Response Time Testing

### HTTP Response Time Testing
```php
test('wrestler index loads within acceptable time', function () {
    // Arrange
    $admin = administrator();
    Wrestler::factory()->count(100)->create();
    
    // Act
    $startTime = microtime(true);
    $response = actingAs($admin)->get(route('wrestlers.index'));
    $endTime = microtime(true);
    
    // Assert
    $response->assertOk();
    expect($endTime - $startTime)->toBeLessThan(2.0); // 2 seconds max
});

test('complex search performs quickly', function () {
    // Arrange
    $admin = administrator();
    Wrestler::factory()->count(500)->create();
    
    // Act
    $startTime = microtime(true);
    $response = actingAs($admin)->get(route('wrestlers.index', [
        'search' => 'John',
        'status' => 'employed',
        'sort' => 'name',
    ]));
    $endTime = microtime(true);
    
    // Assert
    $response->assertOk();
    expect($endTime - $startTime)->toBeLessThan(1.0); // 1 second max
});
```

### Action Performance Testing
```php
test('wrestler employment action executes quickly', function () {
    // Arrange
    $wrestler = Wrestler::factory()->create();
    $action = app(EmployWrestlerAction::class);
    
    // Act
    $startTime = microtime(true);
    $result = $action->handle($wrestler, now());
    $endTime = microtime(true);
    
    // Assert
    expect($result)->toBeInstanceOf(WrestlerEmployment::class);
    expect($endTime - $startTime)->toBeLessThan(0.1); // 100ms max
});
```

## Load Testing

### Concurrent User Testing
```php
test('handles multiple concurrent requests', function () {
    // Arrange
    $admin = administrator();
    $wrestlers = Wrestler::factory()->count(50)->create();
    
    // Act
    $responses = [];
    $startTime = microtime(true);
    
    for ($i = 0; $i < 10; $i++) {
        $responses[] = actingAs($admin)->get(route('wrestlers.index'));
    }
    
    $endTime = microtime(true);
    
    // Assert
    foreach ($responses as $response) {
        $response->assertOk();
    }
    expect($endTime - $startTime)->toBeLessThan(5.0); // 5 seconds for 10 requests
});
```

### Bulk Operation Testing
```php
test('bulk wrestler creation performs efficiently', function () {
    // Arrange
    $wrestlerData = Wrestler::factory()->count(100)->make()->toArray();
    
    // Act
    $startTime = microtime(true);
    Wrestler::insert($wrestlerData);
    $endTime = microtime(true);
    
    // Assert
    expect(Wrestler::count())->toBe(100);
    expect($endTime - $startTime)->toBeLessThan(1.0); // 1 second max
});
```

## Cache Performance Testing

### Cache Hit Rate Testing
```php
test('cache improves subsequent requests', function () {
    // Arrange
    $wrestler = Wrestler::factory()->create();
    $repository = app(WrestlerRepository::class);
    
    // Act - First call (cache miss)
    $startTime1 = microtime(true);
    $result1 = $repository->findWithCache($wrestler->id);
    $endTime1 = microtime(true);
    
    // Act - Second call (cache hit)
    $startTime2 = microtime(true);
    $result2 = $repository->findWithCache($wrestler->id);
    $endTime2 = microtime(true);
    
    // Assert
    expect($result1->id)->toBe($wrestler->id);
    expect($result2->id)->toBe($wrestler->id);
    expect($endTime2 - $startTime2)->toBeLessThan($endTime1 - $startTime1);
});
```

### Cache Invalidation Testing
```php
test('cache invalidation works correctly', function () {
    // Arrange
    $wrestler = Wrestler::factory()->create();
    $repository = app(WrestlerRepository::class);
    
    // Act - Cache the wrestler
    $cachedWrestler = $repository->findWithCache($wrestler->id);
    
    // Update wrestler
    $wrestler->update(['name' => 'Updated Name']);
    
    // Get wrestler again
    $updatedWrestler = $repository->findWithCache($wrestler->id);
    
    // Assert
    expect($cachedWrestler->name)->not->toBe('Updated Name');
    expect($updatedWrestler->name)->toBe('Updated Name');
});
```

## Resource Usage Testing

### CPU Usage Testing
```php
test('complex calculation uses acceptable CPU', function () {
    // Arrange
    $wrestlers = Wrestler::factory()->count(1000)->create();
    
    // Act
    $startTime = microtime(true);
    $results = $wrestlers->map(function ($wrestler) {
        return [
            'id' => $wrestler->id,
            'bmi' => $wrestler->calculateBMI(),
            'career_length' => $wrestler->getCareerLength(),
        ];
    });
    $endTime = microtime(true);
    
    // Assert
    expect($results)->toHaveCount(1000);
    expect($endTime - $startTime)->toBeLessThan(2.0); // 2 seconds max
});
```

### File I/O Performance Testing
```php
test('file operations perform efficiently', function () {
    // Arrange
    Storage::fake('local');
    $data = collect(range(1, 1000))->map(fn($i) => "Line $i\n")->implode('');
    
    // Act
    $startTime = microtime(true);
    Storage::disk('local')->put('test-file.txt', $data);
    $retrievedData = Storage::disk('local')->get('test-file.txt');
    $endTime = microtime(true);
    
    // Assert
    expect($retrievedData)->toBe($data);
    expect($endTime - $startTime)->toBeLessThan(0.5); // 500ms max
});
```

## Scalability Testing

### Data Volume Testing
```php
test('handles large datasets efficiently', function () {
    // Arrange
    $wrestlers = Wrestler::factory()->count(5000)->create();
    
    // Act
    $startTime = microtime(true);
    $activeWrestlers = Wrestler::query()
        ->employed()
        ->with('currentEmployment')
        ->get();
    $endTime = microtime(true);
    
    // Assert
    expect($activeWrestlers)->toBeCollection();
    expect($endTime - $startTime)->toBeLessThan(3.0); // 3 seconds max
});
```

### Pagination Performance Testing
```php
test('pagination handles large datasets', function () {
    // Arrange
    Wrestler::factory()->count(10000)->create();
    $admin = administrator();
    
    // Act
    $startTime = microtime(true);
    $response = actingAs($admin)->get(route('wrestlers.index', ['page' => 100]));
    $endTime = microtime(true);
    
    // Assert
    $response->assertOk();
    expect($endTime - $startTime)->toBeLessThan(2.0); // 2 seconds max
});
```

## Performance Monitoring

### Query Monitoring
```php
test('monitors query performance', function () {
    // Arrange
    $wrestlers = Wrestler::factory()->count(100)->create();
    
    // Act
    DB::enableQueryLog();
    $results = Wrestler::with('currentEmployment')->get();
    $queryLog = DB::getQueryLog();
    
    // Assert
    expect($queryLog)->toHaveCount(2); // Main query + relationship query
    
    foreach ($queryLog as $query) {
        expect($query['time'])->toBeLessThan(100); // 100ms max per query
    }
});
```

### Memory Monitoring
```php
test('monitors memory usage patterns', function () {
    // Arrange
    $initialMemory = memory_get_usage();
    
    // Act
    $wrestlers = Wrestler::factory()->count(1000)->create();
    $peakMemory = memory_get_peak_usage();
    
    // Clean up
    $wrestlers = null;
    $finalMemory = memory_get_usage();
    
    // Assert
    expect($peakMemory - $initialMemory)->toBeLessThan(100 * 1024 * 1024); // 100MB max
    expect($finalMemory - $initialMemory)->toBeLessThan(10 * 1024 * 1024); // 10MB after cleanup
});
```

## Performance Benchmarking

### Comparative Performance Testing
```php
test('optimized query outperforms unoptimized query', function () {
    // Arrange
    Wrestler::factory()->count(1000)->create();
    
    // Act - Unoptimized query
    $startTime1 = microtime(true);
    $unoptimized = Wrestler::all()->filter(fn($w) => $w->isEmployed());
    $endTime1 = microtime(true);
    
    // Act - Optimized query
    $startTime2 = microtime(true);
    $optimized = Wrestler::employed()->get();
    $endTime2 = microtime(true);
    
    // Assert
    expect($unoptimized->count())->toBe($optimized->count());
    expect($endTime2 - $startTime2)->toBeLessThan($endTime1 - $startTime1);
});
```

### Performance Regression Testing
```php
test('performance does not regress', function () {
    // Arrange
    $wrestlers = Wrestler::factory()->count(500)->create();
    $baseline = 1.0; // 1 second baseline
    
    // Act
    $startTime = microtime(true);
    $results = Wrestler::with(['currentEmployment', 'currentChampionships'])
        ->employed()
        ->get();
    $endTime = microtime(true);
    
    // Assert
    expect($results)->toBeCollection();
    expect($endTime - $startTime)->toBeLessThan($baseline);
});
```

## Performance Test Tools

### Custom Performance Helpers
```php
function measureExecutionTime(callable $callback): float
{
    $startTime = microtime(true);
    $callback();
    $endTime = microtime(true);
    
    return $endTime - $startTime;
}

function assertExecutionTime(callable $callback, float $maxTime): void
{
    $executionTime = measureExecutionTime($callback);
    expect($executionTime)->toBeLessThan($maxTime);
}
```

### Database Query Counter
```php
function countQueries(callable $callback): int
{
    DB::enableQueryLog();
    $callback();
    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();
    
    return $queryCount;
}

function assertQueryCount(int $expectedCount, callable $callback): void
{
    $actualCount = countQueries($callback);
    expect($actualCount)->toBe($expectedCount);
}
```

## Performance Standards

### Acceptable Performance Thresholds
- **Unit Tests**: < 10ms per test
- **Integration Tests**: < 100ms per test
- **Feature Tests**: < 2s per test
- **Database Queries**: < 100ms per query
- **API Responses**: < 500ms for simple requests
- **Page Loads**: < 2s for complex pages

### Resource Limits
- **Memory Usage**: < 50MB for typical operations
- **Database Connections**: < 10 concurrent connections
- **File Operations**: < 500ms for typical file I/O
- **CPU Usage**: < 80% for sustained operations

## Performance Optimization Strategies

### Database Optimization
- Use appropriate indexes
- Implement eager loading
- Use database-level constraints
- Optimize query structures
- Use database query builders

### Application Optimization
- Implement caching strategies
- Use lazy loading where appropriate
- Optimize algorithm complexity
- Minimize memory allocations
- Use efficient data structures

### Testing Optimization
- Use database transactions
- Mock external dependencies
- Use factories efficiently
- Implement parallel test execution
- Use appropriate test data sizes

## Continuous Performance Monitoring

### Performance Metrics Collection
```php
test('collects performance metrics', function () {
    // Arrange
    $metrics = [];
    
    // Act
    $metrics['query_time'] = measureExecutionTime(function () {
        Wrestler::with('currentEmployment')->get();
    });
    
    $metrics['memory_usage'] = memory_get_peak_usage();
    
    // Assert
    expect($metrics['query_time'])->toBeLessThan(0.1);
    expect($metrics['memory_usage'])->toBeLessThan(50 * 1024 * 1024);
    
    // Log metrics for trend analysis
    Log::info('Performance metrics', $metrics);
});
```

This comprehensive performance testing approach ensures Ringside maintains optimal performance while scaling effectively.