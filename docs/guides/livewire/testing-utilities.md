# Testing Utilities

## Overview

This guide covers test helpers, factories, utilities, and supporting tools for testing Livewire components in Ringside. These utilities provide reusable functionality to make testing more efficient and maintainable.

## Test Helpers

### Component Test Helpers

Common helpers for testing Livewire components:

```php
/**
 * Create a Livewire component test instance with common setup
 */
function createComponentTest(string $componentClass, array $parameters = []): \Livewire\Testing\TestableLivewire
{
    $admin = User::factory()->administrator()->create();
    
    return Livewire::actingAs($admin)
        ->test($componentClass, $parameters);
}

/**
 * Test a table component with predefined data
 */
function testTableComponent(string $componentClass, array $data = []): \Livewire\Testing\TestableLivewire
{
    $modelClass = (new $componentClass)->getModelClass();
    
    if (empty($data)) {
        $modelClass::factory()->count(10)->create();
    } else {
        $modelClass::factory()->count(count($data))->create($data);
    }
    
    return createComponentTest($componentClass);
}

/**
 * Test a form component with valid data
 */
function testFormComponent(string $componentClass, array $formData = []): \Livewire\Testing\TestableLivewire
{
    $component = createComponentTest($componentClass);
    
    if (!empty($formData)) {
        foreach ($formData as $field => $value) {
            $component->set("form.{$field}", $value);
        }
    }
    
    return $component;
}

/**
 * Test a modal component in create mode
 */
function testModalInCreateMode(string $componentClass): \Livewire\Testing\TestableLivewire
{
    return createComponentTest($componentClass)
        ->call('openModal');
}

/**
 * Test a modal component in edit mode
 */
function testModalInEditMode(string $componentClass, $model): \Livewire\Testing\TestableLivewire
{
    return createComponentTest($componentClass)
        ->call('openModal', $model->id);
}
```

### Assertion Helpers

Common assertions for testing components:

```php
/**
 * Assert that a component dispatched specific events
 */
function assertEventsDispatched(\Livewire\Testing\TestableLivewire $component, array $events): void
{
    foreach ($events as $event => $parameters) {
        if (is_numeric($event)) {
            $component->assertDispatched($parameters);
        } else {
            $component->assertDispatched($event, $parameters);
        }
    }
}

/**
 * Assert that a component shows validation errors
 */
function assertValidationErrors(\Livewire\Testing\TestableLivewire $component, array $fields): void
{
    foreach ($fields as $field) {
        expect($component->instance()->form->getErrorBag()->has($field))->toBeTrue();
    }
}

/**
 * Assert that a component has no validation errors
 */
function assertNoValidationErrors(\Livewire\Testing\TestableLivewire $component): void
{
    expect($component->instance()->form->getErrorBag()->count())->toBe(0);
}

/**
 * Assert that a table component shows specific data
 */
function assertTableShowsData(\Livewire\Testing\TestableLivewire $component, array $data): void
{
    foreach ($data as $value) {
        $component->assertSee($value);
    }
}

/**
 * Assert that a table component hides specific data
 */
function assertTableHidesData(\Livewire\Testing\TestableLivewire $component, array $data): void
{
    foreach ($data as $value) {
        $component->assertDontSee($value);
    }
}

/**
 * Assert that a modal is open
 */
function assertModalOpen(\Livewire\Testing\TestableLivewire $component): void
{
    expect($component->instance()->isModalOpen)->toBeTrue();
}

/**
 * Assert that a modal is closed
 */
function assertModalClosed(\Livewire\Testing\TestableLivewire $component): void
{
    expect($component->instance()->isModalOpen)->toBeFalse();
}
```

### Data Assertion Helpers

Helpers for asserting data states:

```php
/**
 * Assert that a model exists with specific attributes
 */
function assertModelExists(string $modelClass, array $attributes): void
{
    expect($modelClass::where($attributes)->exists())->toBeTrue();
}

/**
 * Assert that a model does not exist with specific attributes
 */
function assertModelDoesNotExist(string $modelClass, array $attributes): void
{
    expect($modelClass::where($attributes)->exists())->toBeFalse();
}

/**
 * Assert that a model has specific attribute values
 */
function assertModelHasAttributes($model, array $attributes): void
{
    foreach ($attributes as $attribute => $value) {
        expect($model->{$attribute})->toBe($value);
    }
}

/**
 * Assert that a collection contains models with specific attributes
 */
function assertCollectionContains($collection, array $attributes): void
{
    $found = $collection->first(function ($item) use ($attributes) {
        foreach ($attributes as $attribute => $value) {
            if ($item->{$attribute} !== $value) {
                return false;
            }
        }
        return true;
    });
    
    expect($found)->not->toBeNull();
}
```

## Factory Utilities

### Enhanced Model Factories

Extended factory methods for testing:

```php
/**
 * Event factory with common test scenarios
 */
class EventFactory extends Factory
{
    protected $model = Event::class;
    
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'date' => $this->faker->dateTimeBetween('now', '+1 year'),
            'venue_id' => Venue::factory(),
            'status' => $this->faker->randomElement(['active', 'inactive', 'cancelled']),
            'preview' => $this->faker->paragraph(),
            'published' => $this->faker->boolean(70),
        ];
    }
    
    public function published(): self
    {
        return $this->state(['published' => true]);
    }
    
    public function unpublished(): self
    {
        return $this->state(['published' => false]);
    }
    
    public function scheduled(): self
    {
        return $this->state(['status' => 'scheduled']);
    }
    
    public function completed(): self
    {
        return $this->state(['status' => 'completed']);
    }
    
    public function cancelled(): self
    {
        return $this->state(['status' => 'cancelled']);
    }
    
    public function past(): self
    {
        return $this->state([
            'date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'status' => 'completed',
        ]);
    }
    
    public function future(): self
    {
        return $this->state([
            'date' => $this->faker->dateTimeBetween('now', '+1 year'),
            'status' => 'scheduled',
        ]);
    }
    
    public function withVenue(Venue $venue): self
    {
        return $this->state(['venue_id' => $venue->id]);
    }
    
    public function withName(string $name): self
    {
        return $this->state(['name' => $name]);
    }
    
    public function withDate(string $date): self
    {
        return $this->state(['date' => $date]);
    }
}
```

### Factory Data Generators

Generators for creating test data efficiently:

```php
/**
 * Generate test data for table components
 */
class TableDataGenerator
{
    public static function createEventsWithVenues(int $count = 10): Collection
    {
        $venues = Venue::factory()->count(5)->create();
        
        return Event::factory()
            ->count($count)
            ->sequence(fn() => ['venue_id' => $venues->random()->id])
            ->create();
    }
    
    public static function createEventsWithStatusDistribution(int $count = 20): Collection
    {
        $statuses = ['active', 'inactive', 'cancelled'];
        
        return Event::factory()
            ->count($count)
            ->sequence(fn() => ['status' => fake()->randomElement($statuses)])
            ->create();
    }
    
    public static function createEventsForDateRange(string $start, string $end, int $count = 15): Collection
    {
        return Event::factory()
            ->count($count)
            ->sequence(fn() => ['date' => fake()->dateTimeBetween($start, $end)])
            ->create();
    }
    
    public static function createEventsWithSearchableNames(array $names): Collection
    {
        return Event::factory()
            ->count(count($names))
            ->sequence(fn($sequence) => ['name' => $names[$sequence->index]])
            ->create();
    }
}

/**
 * Generate test data for form components
 */
class FormDataGenerator
{
    public static function validEventData(): array
    {
        return [
            'name' => 'Test Event',
            'date' => '2024-12-01 19:00:00',
            'venue_id' => Venue::factory()->create()->id,
            'preview' => 'This is a test event preview.',
            'published' => true,
        ];
    }
    
    public static function invalidEventData(): array
    {
        return [
            'name' => '', // Required field empty
            'date' => 'invalid-date',
            'venue_id' => 999, // Non-existent venue
            'preview' => str_repeat('a', 1001), // Too long
        ];
    }
    
    public static function partialEventData(): array
    {
        return [
            'name' => 'Partial Event',
            'date' => '2024-12-01 19:00:00',
            // Missing venue_id and other fields
        ];
    }
    
    public static function duplicateEventData(): array
    {
        $existing = Event::factory()->create();
        
        return [
            'name' => $existing->name, // Duplicate name
            'date' => '2024-12-01 19:00:00',
            'venue_id' => Venue::factory()->create()->id,
        ];
    }
}
```

### Batch Data Creation

Utilities for creating large datasets efficiently:

```php
/**
 * Create large datasets for performance testing
 */
class BatchDataCreator
{
    public static function createLargeEventDataset(int $eventCount = 1000): void
    {
        DB::disableQueryLog();
        
        $venues = Venue::factory()->count(50)->create();
        $statuses = ['active', 'inactive', 'cancelled'];
        
        $events = [];
        for ($i = 0; $i < $eventCount; $i++) {
            $events[] = [
                'name' => "Event {$i}",
                'venue_id' => $venues->random()->id,
                'date' => fake()->dateTimeBetween('now', '+1 year'),
                'status' => fake()->randomElement($statuses),
                'preview' => fake()->paragraph(),
                'published' => fake()->boolean(70),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            
            // Insert in batches for memory efficiency
            if (count($events) >= 100) {
                DB::table('events')->insert($events);
                $events = [];
            }
        }
        
        if (!empty($events)) {
            DB::table('events')->insert($events);
        }
        
        DB::enableQueryLog();
    }
    
    public static function createComplexRelationalDataset(int $baseCount = 100): void
    {
        DB::disableQueryLog();
        
        $venues = Venue::factory()->count(20)->create();
        $events = Event::factory()->count($baseCount)->create([
            'venue_id' => fn() => $venues->random()->id,
        ]);
        
        foreach ($events as $event) {
            Match::factory()->count(rand(1, 5))->create([
                'event_id' => $event->id,
            ]);
        }
        
        DB::enableQueryLog();
    }
}
```

## Test Database Utilities

### Database State Management

Utilities for managing test database state:

```php
/**
 * Database state management for tests
 */
class TestDatabaseManager
{
    public static function cleanSlate(): void
    {
        DB::table('events')->delete();
        DB::table('venues')->delete();
        DB::table('matches')->delete();
        DB::table('users')->delete();
    }
    
    public static function seedBasicData(): void
    {
        $admin = User::factory()->administrator()->create();
        $venues = Venue::factory()->count(5)->create();
        
        return compact('admin', 'venues');
    }
    
    public static function withTransaction(callable $callback): mixed
    {
        return DB::transaction(function () use ($callback) {
            return $callback();
        });
    }
    
    public static function withoutQueryLog(callable $callback): mixed
    {
        DB::disableQueryLog();
        
        try {
            return $callback();
        } finally {
            DB::enableQueryLog();
        }
    }
}
```

### Migration Utilities

Utilities for managing test migrations:

```php
/**
 * Test migration utilities
 */
class TestMigrationManager
{
    public static function refreshDatabase(): void
    {
        Artisan::call('migrate:fresh');
    }
    
    public static function rollbackMigration(string $migration): void
    {
        Artisan::call('migrate:rollback', [
            '--step' => 1,
            '--path' => "database/migrations/{$migration}.php",
        ]);
    }
    
    public static function runSpecificMigration(string $migration): void
    {
        Artisan::call('migrate', [
            '--path' => "database/migrations/{$migration}.php",
        ]);
    }
}
```

## Mock Utilities

### Service Mocking

Utilities for mocking services:

```php
/**
 * Service mocking utilities
 */
class ServiceMocker
{
    public static function mockEmailService(): \Mockery\MockInterface
    {
        $mock = Mockery::mock(EmailService::class);
        $mock->shouldReceive('sendNotification')->andReturn(true);
        app()->instance(EmailService::class, $mock);
        
        return $mock;
    }
    
    public static function mockFailingEmailService(): \Mockery\MockInterface
    {
        $mock = Mockery::mock(EmailService::class);
        $mock->shouldReceive('sendNotification')->andReturn(false);
        app()->instance(EmailService::class, $mock);
        
        return $mock;
    }
    
    public static function mockExternalApiService(): \Mockery\MockInterface
    {
        $mock = Mockery::mock(ExternalApiService::class);
        $mock->shouldReceive('fetchData')->andReturn(['status' => 'success']);
        app()->instance(ExternalApiService::class, $mock);
        
        return $mock;
    }
    
    public static function mockDatabaseError(): void
    {
        DB::shouldReceive('beginTransaction')
            ->andThrow(new \Exception('Database connection failed'));
    }
    
    public static function mockValidationError(): void
    {
        Validator::shouldReceive('make')
            ->andReturn(
                Mockery::mock()
                    ->shouldReceive('fails')
                    ->andReturn(true)
                    ->getMock()
            );
    }
}
```

### Event Mocking

Utilities for mocking events:

```php
/**
 * Event mocking utilities
 */
class EventMocker
{
    public static function fakeEvents(): void
    {
        Event::fake();
    }
    
    public static function fakeQueues(): void
    {
        Queue::fake();
    }
    
    public static function fakeStorage(): void
    {
        Storage::fake('public');
    }
    
    public static function fakeAll(): void
    {
        self::fakeEvents();
        self::fakeQueues();
        self::fakeStorage();
    }
    
    public static function assertEventDispatched(string $event, ?callable $callback = null): void
    {
        Event::assertDispatched($event, $callback);
    }
    
    public static function assertJobPushed(string $job, ?callable $callback = null): void
    {
        Queue::assertPushed($job, $callback);
    }
    
    public static function assertFileStored(string $path): void
    {
        Storage::disk('public')->assertExists($path);
    }
}
```

## Component Testing Utilities

### Component State Utilities

Utilities for managing component state:

```php
/**
 * Component state management utilities
 */
class ComponentStateManager
{
    public static function setFormData(\Livewire\Testing\TestableLivewire $component, array $data): \Livewire\Testing\TestableLivewire
    {
        foreach ($data as $field => $value) {
            $component->set("form.{$field}", $value);
        }
        
        return $component;
    }
    
    public static function setFilters(\Livewire\Testing\TestableLivewire $component, array $filters): \Livewire\Testing\TestableLivewire
    {
        foreach ($filters as $filter => $value) {
            $component->set("filters.{$filter}", $value);
        }
        
        return $component;
    }
    
    public static function setTableState(\Livewire\Testing\TestableLivewire $component, array $state): \Livewire\Testing\TestableLivewire
    {
        foreach ($state as $property => $value) {
            $component->set($property, $value);
        }
        
        return $component;
    }
    
    public static function resetComponent(\Livewire\Testing\TestableLivewire $component): \Livewire\Testing\TestableLivewire
    {
        return $component->call('resetComponent');
    }
}
```

### Component Interaction Utilities

Utilities for common component interactions:

```php
/**
 * Component interaction utilities
 */
class ComponentInteractionHelper
{
    public static function submitForm(\Livewire\Testing\TestableLivewire $component, array $data = []): \Livewire\Testing\TestableLivewire
    {
        if (!empty($data)) {
            ComponentStateManager::setFormData($component, $data);
        }
        
        return $component->call('save');
    }
    
    public static function openModal(\Livewire\Testing\TestableLivewire $component, $modelId = null): \Livewire\Testing\TestableLivewire
    {
        return $component->call('openModal', $modelId);
    }
    
    public static function closeModal(\Livewire\Testing\TestableLivewire $component): \Livewire\Testing\TestableLivewire
    {
        return $component->call('closeModal');
    }
    
    public static function filterTable(\Livewire\Testing\TestableLivewire $component, array $filters): \Livewire\Testing\TestableLivewire
    {
        return ComponentStateManager::setFilters($component, $filters);
    }
    
    public static function searchTable(\Livewire\Testing\TestableLivewire $component, string $query): \Livewire\Testing\TestableLivewire
    {
        return $component->set('search', $query);
    }
    
    public static function sortTable(\Livewire\Testing\TestableLivewire $component, string $column, string $direction = 'asc'): \Livewire\Testing\TestableLivewire
    {
        return $component->set('sortBy', $column)
            ->set('sortDirection', $direction);
    }
    
    public static function paginateTable(\Livewire\Testing\TestableLivewire $component, int $page): \Livewire\Testing\TestableLivewire
    {
        return $component->set('page', $page);
    }
}
```

## Test Scenario Builders

### Scenario Builder Classes

Build complex test scenarios:

```php
/**
 * Event testing scenario builder
 */
class EventTestScenarioBuilder
{
    private array $events = [];
    private array $venues = [];
    
    public function withVenues(int $count = 5): self
    {
        $this->venues = Venue::factory()->count($count)->create();
        return $this;
    }
    
    public function withEvents(int $count = 10): self
    {
        $this->events = Event::factory()
            ->count($count)
            ->sequence(fn() => [
                'venue_id' => $this->venues ? $this->venues->random()->id : Venue::factory()->create()->id
            ])
            ->create();
        
        return $this;
    }
    
    public function withPastEvents(int $count = 5): self
    {
        $pastEvents = Event::factory()
            ->count($count)
            ->past()
            ->create();
        
        $this->events = array_merge($this->events, $pastEvents->toArray());
        return $this;
    }
    
    public function withFutureEvents(int $count = 5): self
    {
        $futureEvents = Event::factory()
            ->count($count)
            ->future()
            ->create();
        
        $this->events = array_merge($this->events, $futureEvents->toArray());
        return $this;
    }
    
    public function withPublishedEvents(int $count = 3): self
    {
        $publishedEvents = Event::factory()
            ->count($count)
            ->published()
            ->create();
        
        $this->events = array_merge($this->events, $publishedEvents->toArray());
        return $this;
    }
    
    public function build(): array
    {
        return [
            'events' => $this->events,
            'venues' => $this->venues,
        ];
    }
}

/**
 * Form testing scenario builder
 */
class FormTestScenarioBuilder
{
    private array $formData = [];
    private array $validationErrors = [];
    
    public function withValidData(): self
    {
        $this->formData = FormDataGenerator::validEventData();
        return $this;
    }
    
    public function withInvalidData(): self
    {
        $this->formData = FormDataGenerator::invalidEventData();
        $this->validationErrors = ['name', 'date', 'venue_id'];
        return $this;
    }
    
    public function withPartialData(): self
    {
        $this->formData = FormDataGenerator::partialEventData();
        return $this;
    }
    
    public function withCustomData(array $data): self
    {
        $this->formData = array_merge($this->formData, $data);
        return $this;
    }
    
    public function expectValidationErrors(array $fields): self
    {
        $this->validationErrors = $fields;
        return $this;
    }
    
    public function build(): array
    {
        return [
            'formData' => $this->formData,
            'validationErrors' => $this->validationErrors,
        ];
    }
}
```

### Scenario Execution

Execute built scenarios:

```php
/**
 * Execute test scenarios
 */
class ScenarioExecutor
{
    public static function runTableScenario(string $componentClass, EventTestScenarioBuilder $builder): \Livewire\Testing\TestableLivewire
    {
        $scenario = $builder->build();
        
        return createComponentTest($componentClass);
    }
    
    public static function runFormScenario(string $componentClass, FormTestScenarioBuilder $builder): \Livewire\Testing\TestableLivewire
    {
        $scenario = $builder->build();
        
        $component = createComponentTest($componentClass);
        
        if (!empty($scenario['formData'])) {
            ComponentStateManager::setFormData($component, $scenario['formData']);
        }
        
        return $component;
    }
}
```

## Test Configuration Utilities

### Environment Configuration

Utilities for configuring test environment:

```php
/**
 * Test environment configuration
 */
class TestEnvironmentConfig
{
    public static function setDebugMode(bool $enabled = true): void
    {
        config(['app.debug' => $enabled]);
    }
    
    public static function setDatabaseConnection(string $connection = 'testing'): void
    {
        config(['database.default' => $connection]);
    }
    
    public static function setCacheDriver(string $driver = 'array'): void
    {
        config(['cache.default' => $driver]);
    }
    
    public static function setQueueDriver(string $driver = 'sync'): void
    {
        config(['queue.default' => $driver]);
    }
    
    public static function setMailDriver(string $driver = 'array'): void
    {
        config(['mail.default' => $driver]);
    }
    
    public static function configureForTesting(): void
    {
        self::setDebugMode(false);
        self::setDatabaseConnection('testing');
        self::setCacheDriver('array');
        self::setQueueDriver('sync');
        self::setMailDriver('array');
    }
}
```

### Performance Configuration

Configuration for performance testing:

```php
/**
 * Performance testing configuration
 */
class PerformanceTestConfig
{
    public static function disableQueryLog(): void
    {
        DB::disableQueryLog();
    }
    
    public static function enableQueryLog(): void
    {
        DB::enableQueryLog();
    }
    
    public static function setMemoryLimit(string $limit = '512M'): void
    {
        ini_set('memory_limit', $limit);
    }
    
    public static function setTimeLimit(int $seconds = 300): void
    {
        set_time_limit($seconds);
    }
    
    public static function configureForPerformanceTesting(): void
    {
        self::disableQueryLog();
        self::setMemoryLimit('1G');
        self::setTimeLimit(600);
    }
}
```

## Test Cleanup Utilities

### Cleanup Helpers

Utilities for cleaning up after tests:

```php
/**
 * Test cleanup utilities
 */
class TestCleanupManager
{
    public static function clearAllCaches(): void
    {
        Cache::flush();
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
    }
    
    public static function clearStorage(): void
    {
        Storage::fake('public');
    }
    
    public static function resetDatabase(): void
    {
        DB::table('events')->delete();
        DB::table('venues')->delete();
        DB::table('matches')->delete();
    }
    
    public static function closeMockery(): void
    {
        Mockery::close();
    }
    
    public static function fullCleanup(): void
    {
        self::clearAllCaches();
        self::clearStorage();
        self::resetDatabase();
        self::closeMockery();
    }
}
```

## Usage Examples

### Complete Test Setup

Example of using utilities in a test:

```php
describe('EventsTable with Utilities', function () {
    beforeEach(function () {
        TestEnvironmentConfig::configureForTesting();
        TestDatabaseManager::cleanSlate();
        
        $this->scenario = (new EventTestScenarioBuilder())
            ->withVenues(5)
            ->withEvents(20)
            ->withPastEvents(5)
            ->withFutureEvents(5)
            ->build();
    });
    
    afterEach(function () {
        TestCleanupManager::fullCleanup();
    });
    
    test('filters events correctly', function () {
        $component = createComponentTest(EventsTable::class);
        
        ComponentInteractionHelper::filterTable($component, [
            'status' => 'active',
            'published' => true,
        ]);
        
        ComponentInteractionHelper::searchTable($component, 'Test');
        
        assertTableShowsData($component, ['Test Event']);
    });
});
```

### Performance Testing Setup

Example of using utilities for performance testing:

```php
describe('Performance Testing with Utilities', function () {
    beforeEach(function () {
        PerformanceTestConfig::configureForPerformanceTesting();
        BatchDataCreator::createLargeEventDataset(1000);
    });
    
    test('table performs well with large dataset', function () {
        $component = createComponentTest(EventsTable::class);
        
        $startTime = microtime(true);
        $component->render();
        $endTime = microtime(true);
        
        expect($endTime - $startTime)->toBeLessThan(1.0);
    });
});
```

## Best Practices

### Utility Organization
- Group related utilities in logical classes
- Use static methods for stateless utilities
- Provide clear, descriptive method names
- Document complex utility functions

### Reusability
- Create generic utilities that work across components
- Use builders for complex scenarios
- Provide sensible defaults
- Allow customization when needed

### Maintenance
- Keep utilities simple and focused
- Test utilities themselves when complex
- Update utilities when component patterns change
- Document utility usage patterns

### Performance
- Use batch operations for large datasets
- Minimize database queries in utilities
- Clean up resources appropriately
- Monitor memory usage in utilities

## Related Documentation

- [Testing Guide](testing-guide.md) - Main testing overview
- [Best Practices](testing-best-practices.md) - Testing best practices
- [Performance Testing](testing-performance.md) - Performance testing strategies
- [Component Architecture](../../architecture/livewire/component-architecture.md) - Overall architecture