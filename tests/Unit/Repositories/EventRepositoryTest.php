<?php

declare(strict_types=1);

use App\Data\Events\EventData;
use App\Models\Events\Event;
use App\Models\Shared\Venue;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\EventRepository;
use Illuminate\Support\Carbon;

/**
 * Unit tests for EventRepository business logic and data operations.
 *
 * UNIT TEST SCOPE:
 * - Repository configuration and structure verification
 * - Core CRUD operations (create, update, delete, restore)
 * - Event specific business logic (venue associations, date handling)
 *
 * These tests verify that the EventRepository correctly implements
 * all business operations and data persistence requirements.
 *
 * @see EventRepository
 */
describe('EventRepository Unit Tests', function () {
    beforeEach(function () {
        $this->repository = app(EventRepository::class);
    });

    describe('repository configuration', function () {
        test('repository can be resolved from container', function () {
            expect($this->repository)->toBeInstanceOf(EventRepository::class);
            expect($this->repository)->toBeInstanceOf(EventRepositoryInterface::class);
        });

        test('repository has all expected methods', function () {
            $methods = [
                'create', 'update', 'delete', 'restore'
            ];

            foreach ($methods as $method) {
                expect(method_exists($this->repository, $method))
                    ->toBeTrue("Repository should have {$method} method");
            }
        });
    });

    describe('core CRUD operations', function () {
        test('can create event with minimal data', function () {
            // Arrange
            $data = new EventData('Example Event Name', null, null, null);

            // Act
            $event = $this->repository->create($data);

            // Assert
            expect($event)
                ->toBeInstanceOf(Event::class)
                ->name->toEqual('Example Event Name')
                ->date->toBeNull()
                ->venue_id->toBeNull()
                ->preview->toBeNull();

            $this->assertDatabaseHas('events', [
                'name' => 'Example Event Name',
                'date' => null,
                'venue_id' => null,
                'preview' => null,
            ]);
        });

        test('can create event with date', function () {
            // Arrange
            $date = Carbon::tomorrow();
            $data = new EventData('Example Event Name', $date, null, null);

            // Act
            $event = $this->repository->create($data);

            // Assert
            expect($event)
                ->name->toBe('Example Event Name')
                ->date->toEqual($date->toDateTimeString())
                ->venue_id->toBeNull()
                ->preview->toBeNull();

            $this->assertDatabaseHas('events', [
                'name' => 'Example Event Name',
                'date' => $date,
                'venue_id' => null,
                'preview' => null,
            ]);
        });

        test('can create event with date and venue', function () {
            // Arrange
            $venue = Venue::factory()->create();
            $date = Carbon::tomorrow();
            $data = new EventData('Example Event Name', $date, $venue, null);

            // Act
            $event = $this->repository->create($data);

            // Assert
            expect($event)
                ->name->toBe('Example Event Name')
                ->date->toEqual($date->toDateTimeString())
                ->venue_id->toBe($venue->id)
                ->preview->toBeNull();

            $this->assertDatabaseHas('events', [
                'name' => 'Example Event Name',
                'date' => $date,
                'venue_id' => $venue->id,
                'preview' => null,
            ]);
        });

        test('can create event with all data', function () {
            // Arrange
            $date = Carbon::tomorrow();
            $venue = Venue::factory()->create();
            $preview = fake()->paragraph();
            $data = new EventData('Example Event Name', $date, $venue, $preview);

            // Act
            $event = $this->repository->create($data);

            // Assert
            expect($event)
                ->name->toBe('Example Event Name')
                ->date->toEqual($date->toDateTimeString())
                ->venue_id->toBe($venue->id)
                ->preview->toEqual($preview);

            $this->assertDatabaseHas('events', [
                'name' => 'Example Event Name',
                'date' => $date,
                'venue_id' => $venue->id,
                'preview' => $preview,
            ]);
        });

        test('can soft delete event', function () {
            // Arrange
            $event = Event::factory()->create();

            // Act
            $this->repository->delete($event);

            // Assert
            expect($event->fresh()->deleted_at)->not->toBeNull();
            $this->assertSoftDeleted('events', ['id' => $event->id]);
        });

        test('can restore soft deleted event', function () {
            // Arrange
            $event = Event::factory()->trashed()->create();

            // Act
            $this->repository->restore($event);

            // Assert
            expect($event->fresh()->deleted_at)->toBeNull();
            $this->assertDatabaseHas('events', [
                'id' => $event->id,
                'deleted_at' => null,
            ]);
        });
    });
});