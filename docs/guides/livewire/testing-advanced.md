# Advanced Testing Patterns

## Overview

This guide covers advanced testing patterns for Livewire components in Ringside, including mocking external dependencies, testing events and listeners, database transaction testing, and service integration testing.

## Mocking External Dependencies

### Repository Mocking
Mock repository dependencies to isolate component logic:

```php
use Mockery;
use App\Repositories\EventRepository;
use App\Livewire\Events\Forms\CreateEditForm;

test('handles repository exceptions gracefully', function () {
    // Arrange
    $repository = Mockery::mock(EventRepository::class);
    $repository->shouldReceive('create')
        ->once()
        ->andThrow(new \Exception('Database connection failed'));
    
    $this->app->instance(EventRepository::class, $repository);
    
    // Act
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $result = $form->store();
    
    // Assert
    expect($result)->toBeFalse();
    expect($form->getErrorBag()->has('general'))->toBeTrue();
});

test('repository is called with correct parameters', function () {
    // Arrange
    $repository = Mockery::mock(EventRepository::class);
    $repository->shouldReceive('create')
        ->once()
        ->with(Mockery::on(function ($data) {
            return $data['name'] === 'Test Event' && 
                   $data['venue_id'] === 1;
        }))
        ->andReturn(Event::factory()->make());
    
    $this->app->instance(EventRepository::class, $repository);
    
    // Act
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $form->venue_id = 1;
    $form->store();
    
    // Assert - expectations verified automatically
});
```

### Service Layer Mocking
Mock service layer dependencies:

```php
use App\Services\EmailService;
use App\Services\NotificationService;

test('continues when email service fails', function () {
    // Arrange
    $emailService = Mockery::mock(EmailService::class);
    $emailService->shouldReceive('sendEventNotification')
        ->once()
        ->andReturn(false);
    
    $this->app->instance(EmailService::class, $emailService);
    
    // Act
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $result = $form->store();
    
    // Assert
    expect($result)->toBeTrue(); // Event created despite email failure
    expect(Event::where('name', 'Test Event')->exists())->toBeTrue();
});

test('notification service receives correct data', function () {
    // Arrange
    $notificationService = Mockery::mock(NotificationService::class);
    $notificationService->shouldReceive('eventCreated')
        ->once()
        ->with(Mockery::type(Event::class))
        ->andReturn(true);
    
    $this->app->instance(NotificationService::class, $notificationService);
    
    // Act
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $form->store();
    
    // Assert verified by mock expectations
});
```

### External API Mocking
Mock external API dependencies:

```php
use App\Services\TicketingApiService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

test('handles ticketing API failures gracefully', function () {
    // Arrange
    $httpClient = Mockery::mock(Client::class);
    $httpClient->shouldReceive('post')
        ->once()
        ->andThrow(new \Exception('API unavailable'));
    
    $ticketingService = new TicketingApiService($httpClient);
    $this->app->instance(TicketingApiService::class, $ticketingService);
    
    // Act
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $result = $form->store();
    
    // Assert
    expect($result)->toBeTrue(); // Event created despite API failure
    expect(Event::where('name', 'Test Event')->exists())->toBeTrue();
});

test('ticketing API receives correct event data', function () {
    // Arrange
    $httpClient = Mockery::mock(Client::class);
    $httpClient->shouldReceive('post')
        ->once()
        ->with('events', Mockery::on(function ($options) {
            return isset($options['json']['name']) && 
                   $options['json']['name'] === 'Test Event';
        }))
        ->andReturn(new Response(200, [], json_encode(['id' => 123])));
    
    $ticketingService = new TicketingApiService($httpClient);
    $this->app->instance(TicketingApiService::class, $ticketingService);
    
    // Act
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $form->store();
    
    // Assert verified by mock expectations
});
```

## Event Testing

### Laravel Event Testing
Test Laravel event dispatching:

```php
use Illuminate\Support\Facades\Event;
use App\Events\EventCreated;
use App\Events\EventUpdated;

test('dispatches event created event', function () {
    // Arrange
    Event::fake();
    
    // Act
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $form->store();
    
    // Assert
    Event::assertDispatched(EventCreated::class, function ($event) {
        return $event->event->name === 'Test Event';
    });
});

test('dispatches event updated event', function () {
    // Arrange
    Event::fake();
    $existingEvent = Event::factory()->create();
    
    // Act
    $form = new CreateEditForm();
    $form->setModel($existingEvent);
    $form->name = 'Updated Event';
    $form->store();
    
    // Assert
    Event::assertDispatched(EventUpdated::class, function ($event) use ($existingEvent) {
        return $event->event->id === $existingEvent->id &&
               $event->event->name === 'Updated Event';
    });
});

test('does not dispatch events on validation failure', function () {
    // Arrange
    Event::fake();
    
    // Act
    $form = new CreateEditForm();
    $form->name = ''; // Invalid data
    $form->store();
    
    // Assert
    Event::assertNotDispatched(EventCreated::class);
});
```

### Livewire Event Testing
Test Livewire component events:

```php
use App\Livewire\Events\Modals\FormModal;

test('modal dispatches events correctly', function () {
    // Arrange
    $component = Livewire::test(FormModal::class);
    
    // Act
    $component->call('openModal')
        ->set('form.name', 'Test Event')
        ->call('save');
    
    // Assert
    $component->assertDispatched('form-submitted');
    $component->assertDispatched('closeModal');
});

test('component receives and handles events', function () {
    // Arrange
    $component = Livewire::test(FormModal::class);
    
    // Act
    $component->dispatch('refresh-modal');
    
    // Assert
    $component->assertOk();
    // Additional state assertions based on event handling
});

test('events contain correct data', function () {
    // Arrange
    $component = Livewire::test(FormModal::class);
    
    // Act
    $component->call('openModal')
        ->set('form.name', 'Test Event')
        ->call('save');
    
    // Assert
    $component->assertDispatched('form-submitted');
    
    // Verify event data
    $event = Event::where('name', 'Test Event')->first();
    expect($event)->not->toBeNull();
});
```

### Event Listener Testing
Test event listeners:

```php
use App\Events\EventCreated;
use App\Listeners\SendEventNotification;
use App\Listeners\UpdateEventCache;

test('event listeners are called correctly', function () {
    // Arrange
    $listener = Mockery::spy(SendEventNotification::class);
    $this->app->instance(SendEventNotification::class, $listener);
    
    $event = Event::factory()->create();
    
    // Act
    event(new EventCreated($event));
    
    // Assert
    $listener->shouldHaveReceived('handle')
        ->once()
        ->with(Mockery::type(EventCreated::class));
});

test('multiple listeners handle same event', function () {
    // Arrange
    $notificationListener = Mockery::spy(SendEventNotification::class);
    $cacheListener = Mockery::spy(UpdateEventCache::class);
    
    $this->app->instance(SendEventNotification::class, $notificationListener);
    $this->app->instance(UpdateEventCache::class, $cacheListener);
    
    $event = Event::factory()->create();
    
    // Act
    event(new EventCreated($event));
    
    // Assert
    $notificationListener->shouldHaveReceived('handle')->once();
    $cacheListener->shouldHaveReceived('handle')->once();
});
```

## Database Transaction Testing

### Transaction Rollback Testing
Test database transaction rollback:

```php
use Illuminate\Support\Facades\DB;

test('rolls back transaction on failure', function () {
    // Arrange
    DB::beginTransaction();
    
    // Mock to simulate failure
    DB::shouldReceive('commit')->never();
    DB::shouldReceive('rollBack')->once();
    
    // Act
    $form = new CreateEditForm();
    $form->name = ''; // Invalid data
    $result = $form->store();
    
    // Assert
    expect($result)->toBeFalse();
    expect(Event::count())->toBe(0);
    
    DB::rollBack(); // Cleanup
});

test('commits transaction on success', function () {
    // Arrange
    DB::shouldReceive('beginTransaction')->once();
    DB::shouldReceive('commit')->once();
    DB::shouldReceive('rollBack')->never();
    
    // Act
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $result = $form->store();
    
    // Assert
    expect($result)->toBeTrue();
    expect(Event::where('name', 'Test Event')->exists())->toBeTrue();
});
```

### Complex Transaction Testing
Test complex multi-step transactions:

```php
test('handles complex multi-step transaction', function () {
    // Arrange
    $venue = Venue::factory()->create();
    
    // Act
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $form->venue_id = $venue->id;
    $result = $form->store();
    
    // Assert
    expect($result)->toBeTrue();
    
    // Verify all related operations completed
    $event = Event::where('name', 'Test Event')->first();
    expect($event)->not->toBeNull();
    expect($event->venue)->toBe($venue);
    
    // Verify audit log entry was created
    expect(AuditLog::where('event_id', $event->id)->exists())->toBeTrue();
});

test('rolls back all changes on partial failure', function () {
    // Arrange
    $venue = Venue::factory()->create();
    
    // Mock audit log creation to fail
    AuditLog::shouldReceive('create')->andThrow(new \Exception('Audit failure'));
    
    // Act
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $form->venue_id = $venue->id;
    $result = $form->store();
    
    // Assert
    expect($result)->toBeFalse();
    expect(Event::where('name', 'Test Event')->exists())->toBeFalse();
});
```

### Database Constraint Testing
Test database constraint handling:

```php
test('handles unique constraint violations', function () {
    // Arrange
    Event::factory()->create(['name' => 'Existing Event']);
    
    // Act
    $form = new CreateEditForm();
    $form->name = 'Existing Event';
    $result = $form->store();
    
    // Assert
    expect($result)->toBeFalse();
    expect($form->getErrorBag()->has('name'))->toBeTrue();
});

test('handles foreign key constraint violations', function () {
    // Act
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $form->venue_id = 999; // Non-existent venue
    $result = $form->store();
    
    // Assert
    expect($result)->toBeFalse();
    expect($form->getErrorBag()->has('venue_id'))->toBeTrue();
});
```

## Service Integration Testing

### Multi-Service Integration
Test integration between multiple services:

```php
use App\Services\EventService;
use App\Services\EmailService;
use App\Services\CacheService;

test('integrates multiple services correctly', function () {
    // Arrange
    $eventService = Mockery::mock(EventService::class);
    $emailService = Mockery::mock(EmailService::class);
    $cacheService = Mockery::mock(CacheService::class);
    
    $eventService->shouldReceive('create')
        ->once()
        ->andReturn(Event::factory()->make());
    
    $emailService->shouldReceive('sendNotification')
        ->once()
        ->andReturn(true);
    
    $cacheService->shouldReceive('invalidate')
        ->once()
        ->with('events');
    
    $this->app->instance(EventService::class, $eventService);
    $this->app->instance(EmailService::class, $emailService);
    $this->app->instance(CacheService::class, $cacheService);
    
    // Act
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $result = $form->store();
    
    // Assert
    expect($result)->toBeTrue();
});

test('handles service integration failures', function () {
    // Arrange
    $eventService = Mockery::mock(EventService::class);
    $emailService = Mockery::mock(EmailService::class);
    
    $eventService->shouldReceive('create')
        ->once()
        ->andReturn(Event::factory()->make());
    
    $emailService->shouldReceive('sendNotification')
        ->once()
        ->andThrow(new \Exception('Email service unavailable'));
    
    $this->app->instance(EventService::class, $eventService);
    $this->app->instance(EmailService::class, $emailService);
    
    // Act & Assert
    expect(function () {
        $form = new CreateEditForm();
        $form->name = 'Test Event';
        $form->store();
    })->not->toThrow();
    
    // Event should still be created despite email failure
    expect(Event::where('name', 'Test Event')->exists())->toBeTrue();
});
```

### Service Chain Testing
Test service chain execution:

```php
test('executes service chain in correct order', function () {
    // Arrange
    $executionOrder = [];
    
    $validationService = Mockery::mock(ValidationService::class);
    $validationService->shouldReceive('validate')
        ->once()
        ->andReturnUsing(function () use (&$executionOrder) {
            $executionOrder[] = 'validate';
            return true;
        });
    
    $eventService = Mockery::mock(EventService::class);
    $eventService->shouldReceive('create')
        ->once()
        ->andReturnUsing(function () use (&$executionOrder) {
            $executionOrder[] = 'create';
            return Event::factory()->make();
        });
    
    $notificationService = Mockery::mock(NotificationService::class);
    $notificationService->shouldReceive('notify')
        ->once()
        ->andReturnUsing(function () use (&$executionOrder) {
            $executionOrder[] = 'notify';
            return true;
        });
    
    $this->app->instance(ValidationService::class, $validationService);
    $this->app->instance(EventService::class, $eventService);
    $this->app->instance(NotificationService::class, $notificationService);
    
    // Act
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $form->store();
    
    // Assert
    expect($executionOrder)->toBe(['validate', 'create', 'notify']);
});
```

## Cache Testing

### Cache Integration Testing
Test cache integration:

```php
use Illuminate\Support\Facades\Cache;

test('cache is updated after event creation', function () {
    // Arrange
    Cache::shouldReceive('forget')
        ->once()
        ->with('events.all');
    
    Cache::shouldReceive('remember')
        ->once()
        ->with('events.all', 3600, Mockery::type('callable'))
        ->andReturn(collect());
    
    // Act
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $form->store();
    
    // Assert verified by mock expectations
});

test('cache failures do not prevent event creation', function () {
    // Arrange
    Cache::shouldReceive('forget')
        ->once()
        ->andThrow(new \Exception('Cache unavailable'));
    
    // Act
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $result = $form->store();
    
    // Assert
    expect($result)->toBeTrue();
    expect(Event::where('name', 'Test Event')->exists())->toBeTrue();
});
```

### Cache Invalidation Testing
Test cache invalidation patterns:

```php
test('invalidates related cache keys on update', function () {
    // Arrange
    $event = Event::factory()->create();
    
    Cache::shouldReceive('forget')
        ->once()
        ->with('events.all');
    
    Cache::shouldReceive('forget')
        ->once()
        ->with("events.{$event->id}");
    
    // Act
    $form = new CreateEditForm();
    $form->setModel($event);
    $form->name = 'Updated Event';
    $form->store();
    
    // Assert verified by mock expectations
});
```

## Queue Testing

### Job Dispatching Testing
Test job dispatching:

```php
use Illuminate\Support\Facades\Queue;
use App\Jobs\SendEventNotification;

test('dispatches notification job on event creation', function () {
    // Arrange
    Queue::fake();
    
    // Act
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $form->store();
    
    // Assert
    Queue::assertPushed(SendEventNotification::class, function ($job) {
        return $job->event->name === 'Test Event';
    });
});

test('does not dispatch jobs on validation failure', function () {
    // Arrange
    Queue::fake();
    
    // Act
    $form = new CreateEditForm();
    $form->name = ''; // Invalid data
    $form->store();
    
    // Assert
    Queue::assertNotPushed(SendEventNotification::class);
});
```

## File System Testing

### File Upload Testing
Test file upload handling:

```php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('handles file uploads correctly', function () {
    // Arrange
    Storage::fake('public');
    $file = UploadedFile::fake()->image('event-poster.jpg');
    
    // Act
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $form->poster = $file;
    $result = $form->store();
    
    // Assert
    expect($result)->toBeTrue();
    
    $event = Event::where('name', 'Test Event')->first();
    expect($event->poster)->not->toBeNull();
    
    Storage::disk('public')->assertExists($event->poster);
});

test('handles file upload failures gracefully', function () {
    // Arrange
    Storage::shouldReceive('disk->put')
        ->once()
        ->andThrow(new \Exception('Storage unavailable'));
    
    $file = UploadedFile::fake()->image('event-poster.jpg');
    
    // Act
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $form->poster = $file;
    $result = $form->store();
    
    // Assert
    expect($result)->toBeFalse();
    expect($form->getErrorBag()->has('poster'))->toBeTrue();
});
```

## Testing Complex Workflows

### Multi-Step Workflow Testing
Test complex multi-step workflows:

```php
test('handles complete event creation workflow', function () {
    // Arrange
    $venue = Venue::factory()->create();
    
    // Mock all services
    $validationService = Mockery::mock(ValidationService::class);
    $eventService = Mockery::mock(EventService::class);
    $emailService = Mockery::mock(EmailService::class);
    $cacheService = Mockery::mock(CacheService::class);
    
    $validationService->shouldReceive('validate')->once()->andReturn(true);
    $eventService->shouldReceive('create')->once()->andReturn(Event::factory()->make());
    $emailService->shouldReceive('sendNotification')->once()->andReturn(true);
    $cacheService->shouldReceive('invalidate')->once();
    
    $this->app->instance(ValidationService::class, $validationService);
    $this->app->instance(EventService::class, $eventService);
    $this->app->instance(EmailService::class, $emailService);
    $this->app->instance(CacheService::class, $cacheService);
    
    // Act
    $form = new CreateEditForm();
    $form->name = 'Test Event';
    $form->venue_id = $venue->id;
    $result = $form->store();
    
    // Assert
    expect($result)->toBeTrue();
    
    // Verify all workflow steps completed
    Event::fake();
    Queue::assertPushed(SendEventNotification::class);
    Cache::assertForgotten('events.all');
});
```

### Conditional Workflow Testing
Test conditional workflow execution:

```php
test('executes different workflows based on conditions', function () {
    // Arrange
    $publishedEvent = Event::factory()->create(['published' => true]);
    $draftEvent = Event::factory()->create(['published' => false]);
    
    $emailService = Mockery::mock(EmailService::class);
    
    // Should only send notification for published events
    $emailService->shouldReceive('sendPublicNotification')
        ->once()
        ->with(Mockery::type(Event::class));
    
    $emailService->shouldReceive('sendDraftNotification')
        ->never();
    
    $this->app->instance(EmailService::class, $emailService);
    
    // Act
    $form = new CreateEditForm();
    $form->setModel($publishedEvent);
    $form->name = 'Updated Published Event';
    $form->store();
    
    // Assert verified by mock expectations
});
```

## Best Practices for Advanced Testing

### Mock Management
- Use `Mockery::mock()` for precise control over mock behavior
- Use `Mockery::spy()` when you need to verify calls after execution
- Clean up mocks in `tearDown()` or use `Mockery::close()`
- Use type hints in mock expectations for better error messages

### Transaction Testing
- Always test both success and failure paths
- Verify rollback behavior on exceptions
- Test constraint violations and their handling
- Use database transactions in tests for isolation

### Service Testing
- Mock external dependencies to avoid side effects
- Test service integration patterns
- Verify error handling and fallback behavior
- Test service chain execution order

### Event Testing
- Use `Event::fake()` for Laravel events
- Test both event dispatching and listener execution
- Verify event data and parameters
- Test event handling failures

## Related Documentation

- [Testing Guide](testing-guide.md) - Main testing overview
- [Form Testing](testing-forms.md) - Form component testing
- [Modal Testing](testing-modals.md) - Modal component testing
- [Performance Testing](testing-performance.md) - Performance testing strategies
- [Best Practices](testing-best-practices.md) - Testing best practices