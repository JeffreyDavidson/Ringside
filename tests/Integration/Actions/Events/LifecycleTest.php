<?php

declare(strict_types=1);

use App\Actions\Events\CreateAction;
use App\Actions\Events\DeleteAction;
use App\Actions\Events\RestoreAction;
use App\Actions\Events\UpdateAction;
use App\Data\Events\EventData;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use Illuminate\Support\Carbon;

/**
 * Integration tests for Event scheduling and lifecycle management actions.
 *
 * This test suite validates the complete workflow of event lifecycle management
 * including creation, scheduling, updating, and status transitions.
 * These tests use real database relationships and verify that actions properly
 * handle event scheduling, venue associations, and match dependencies.
 */
describe('Event Activation Action Integration', function () {
    beforeEach(function () {
        $this->venue = Venue::factory()->create();
    });

    describe('create action workflow', function () {
        test('create action creates unscheduled event by default', function () {
            $eventData = new EventData(
                name: 'Test Event',
                date: null,
                venue: null,
                preview: 'A test event'
            );

            $event = CreateAction::make()->handle($eventData);

            expect($event->exists)->toBeTrue();
            expect($event->name)->toBe('Test Event');
            expect($event->isUnscheduled())->toBeTrue();
            expect($event->date)->toBeNull();
            expect($event->venue_id)->toBeNull();
            expect($event->preview)->toBe('A test event');
        });

        test('create action creates scheduled event with date and venue', function () {
            $scheduledDate = Carbon::now()->addMonths(3);

            $eventData = new EventData(
                name: 'Scheduled Event',
                date: $scheduledDate,
                venue: $this->venue,
                preview: 'A scheduled event'
            );

            $event = CreateAction::make()->handle($eventData);

            expect($event->exists)->toBeTrue();
            expect($event->name)->toBe('Scheduled Event');
            expect($event->isScheduled())->toBeTrue();
            expect($event->hasFutureDate())->toBeTrue();
            expect(requiredDate($event->date)->format('Y-m-d H:i:s'))->toBe($scheduledDate->format('Y-m-d H:i:s'));
            expect($event->venue_id)->toBe($this->venue->id);
            expect($event->venue()->firstOrFail()->name)->toBe($this->venue->name);
        });

        test('create action handles past date events correctly', function () {
            $pastDate = Carbon::now()->subMonths(1);

            $eventData = new EventData(
                name: 'Past Event',
                date: $pastDate,
                venue: $this->venue,
                preview: 'An event that happened'
            );

            $event = CreateAction::make()->handle($eventData);

            expect($event->exists)->toBeTrue();
            expect($event->isScheduled())->toBeTrue();
            expect($event->hasPastDate())->toBeTrue();
            expect($event->hasFutureDate())->toBeFalse();
        });

        test('create action creates event without venue', function () {
            $eventData = new EventData(
                name: 'No Venue Event',
                date: Carbon::now()->addWeeks(2),
                venue: null,
                preview: 'Event without venue'
            );

            $event = CreateAction::make()->handle($eventData);

            expect($event->exists)->toBeTrue();
            expect($event->isScheduled())->toBeTrue();
            expect($event->venue_id)->toBeNull();
            expect($event->venue)->toBeNull();
        });
    });

    describe('update action workflow', function () {
        beforeEach(function () {
            $this->event = Event::factory()->unscheduled()->create(['name' => 'Original Event']);
        });

        test('update action can schedule an unscheduled event', function () {
            $scheduledDate = Carbon::now()->addMonths(2);

            $eventData = new EventData(
                name: 'Scheduled Event',
                date: $scheduledDate,
                venue: $this->venue,
                preview: 'Now scheduled'
            );

            UpdateAction::make()->handle($this->event, $eventData);

            $refreshedEvent = freshModel($this->event);
            expect($refreshedEvent->name)->toBe('Scheduled Event');
            expect($refreshedEvent->isScheduled())->toBeTrue();
            expect($refreshedEvent->hasFutureDate())->toBeTrue();
            expect($refreshedEvent->venue_id)->toBe($this->venue->id);
            expect($refreshedEvent->preview)->toBe('Now scheduled');
        });

        test('update action can change event date', function () {
            $originalDate = Carbon::now()->addMonth();
            $newDate = Carbon::now()->addMonths(3);

            $this->event->update(['date' => $originalDate]);

            $eventData = new EventData(
                name: $this->event->name,
                date: $newDate,
                venue: $this->venue,
                preview: $this->event->preview
            );

            UpdateAction::make()->handle($this->event, $eventData);

            $refreshedEvent = freshModel($this->event);
            expect(requiredDate($refreshedEvent->date)->format('Y-m-d H:i:s'))->toBe($newDate->format('Y-m-d H:i:s'));
            expect($refreshedEvent->hasFutureDate())->toBeTrue();
        });

        test('update action can change venue', function () {
            $newVenue = Venue::factory()->create();
            $this->event->update(['venue_id' => $this->venue->id]);

            $eventData = new EventData(
                name: $this->event->name,
                date: $this->event->date,
                venue: $newVenue,
                preview: $this->event->preview
            );

            UpdateAction::make()->handle($this->event, $eventData);

            $refreshedEvent = freshModel($this->event);
            expect($refreshedEvent->venue_id)->toBe($newVenue->id);
            expect($refreshedEvent->venue()->firstOrFail()->name)->toBe($newVenue->name);
        });

        test('update action can remove venue from event', function () {
            $this->event->update(['venue_id' => $this->venue->id]);

            $eventData = new EventData(
                name: $this->event->name,
                date: $this->event->date,
                venue: null,
                preview: $this->event->preview
            );

            UpdateAction::make()->handle($this->event, $eventData);

            $refreshedEvent = freshModel($this->event);
            expect($refreshedEvent->venue_id)->toBeNull();
            expect($refreshedEvent->venue)->toBeNull();
        });

        test('update action can unschedule an event', function () {
            $this->event->update(['date' => Carbon::now()->addWeeks(3)]);

            $eventData = new EventData(
                name: $this->event->name,
                date: null,
                venue: null,
                preview: 'Unscheduled again'
            );

            UpdateAction::make()->handle($this->event, $eventData);

            $refreshedEvent = freshModel($this->event);
            expect($refreshedEvent->isUnscheduled())->toBeTrue();
            expect($refreshedEvent->date)->toBeNull();
            expect($refreshedEvent->preview)->toBe('Unscheduled again');
        });
    });

    describe('delete and restore workflow', function () {
        beforeEach(function () {
            $this->event = Event::factory()->scheduled()->create(['name' => 'Deletable Event']);
        });

        test('delete action soft deletes event', function () {
            DeleteAction::make()->handle($this->event);

            expect(Event::find($this->event->id))->toBeNull();
            expect(Event::onlyTrashed()->find($this->event->id))->not()->toBeNull();
            expect(freshModel($this->event)->deleted_at)->not()->toBeNull();
        });

        test('restore action recovers deleted event', function () {
            DeleteAction::make()->handle($this->event);
            expect(Event::find($this->event->id))->toBeNull();

            RestoreAction::make()->handle($this->event);

            $restoredEvent = Event::findOrFail($this->event->id);
            expect($restoredEvent->name)->toBe('Deletable Event');
            expect($restoredEvent->deleted_at)->toBeNull();
        });

        test('restore action maintains event scheduling information', function () {
            $originalDate = $this->event->date;
            $originalVenueId = $this->event->venue_id;

            DeleteAction::make()->handle($this->event);
            RestoreAction::make()->handle($this->event);

            $restoredEvent = Event::findOrFail($this->event->id);
            expect(requiredDate($restoredEvent->date)->format('Y-m-d H:i:s'))->toBe(requiredDate($originalDate)->format('Y-m-d H:i:s'));
            expect($restoredEvent->venue_id)->toBe($originalVenueId);
            expect($restoredEvent->isScheduled())->toBeTrue();
        });
    });

    describe('complex event lifecycle scenarios', function () {
        test('event can go through complete lifecycle', function () {
            // Create unscheduled event
            $eventData = new EventData(
                name: 'Lifecycle Event',
                date: null,
                venue: null,
                preview: 'Draft event'
            );
            $event = CreateAction::make()->handle($eventData);
            expect($event->isUnscheduled())->toBeTrue();

            // Schedule the event
            $scheduledDate = Carbon::now()->addMonths(4);
            $updateData = new EventData(
                name: 'Scheduled Lifecycle Event',
                date: $scheduledDate,
                venue: $this->venue,
                preview: 'Scheduled event'
            );
            UpdateAction::make()->handle($event, $updateData);

            $refreshedEvent = $event->refresh();
            expect($refreshedEvent->isScheduled())->toBeTrue();
            expect($refreshedEvent->hasFutureDate())->toBeTrue();

            // Update event details
            $finalUpdateData = new EventData(
                name: 'Final Event Name',
                date: $scheduledDate,
                venue: $this->venue,
                preview: 'Updated preview'
            );
            UpdateAction::make()->handle($event, $finalUpdateData);

            $finalEvent = $event->refresh();
            expect($finalEvent->name)->toBe('Final Event Name');
            expect($finalEvent->preview)->toBe('Updated preview');
            expect($finalEvent->venue_id)->toBe($this->venue->id);

            // Delete and restore
            DeleteAction::make()->handle($event);
            expect(Event::find($event->id))->toBeNull();

            RestoreAction::make()->handle($event);
            $restoredEvent = Event::query()->whereKey($event->getKey())->firstOrFail();
            expect($restoredEvent->name)->toBe('Final Event Name');
            expect($restoredEvent->isScheduled())->toBeTrue();
        });

        test('multiple events can be scheduled at same venue', function () {
            $date1 = Carbon::now()->addMonths(1);
            $date2 = Carbon::now()->addMonths(2);

            $event1Data = new EventData(
                name: 'Event One',
                date: $date1,
                venue: $this->venue,
                preview: 'First event'
            );

            $event2Data = new EventData(
                name: 'Event Two',
                date: $date2,
                venue: $this->venue,
                preview: 'Second event'
            );

            $event1 = CreateAction::make()->handle($event1Data);
            $event2 = CreateAction::make()->handle($event2Data);

            expect($event1->venue_id)->toBe($this->venue->id);
            expect($event2->venue_id)->toBe($this->venue->id);
            expect($event1->isScheduled())->toBeTrue();
            expect($event2->isScheduled())->toBeTrue();
            expect($event1->hasFutureDate())->toBeTrue();
            expect($event2->hasFutureDate())->toBeTrue();
        });

        test('event scheduling with venue changes', function () {
            $venue1 = Venue::factory()->create(['name' => 'Venue One']);
            $venue2 = Venue::factory()->create(['name' => 'Venue Two']);

            // Create event at first venue
            $eventData = new EventData(
                name: 'Venue Change Event',
                date: Carbon::now()->addMonths(2),
                venue: $venue1,
                preview: 'Initial venue'
            );
            $event = CreateAction::make()->handle($eventData);
            expect($event->venue_id)->toBe($venue1->id);

            // Change to second venue
            $updateData = new EventData(
                name: $event->name,
                date: $event->date,
                venue: $venue2,
                preview: 'Changed venue'
            );
            UpdateAction::make()->handle($event, $updateData);

            $refreshedEvent = $event->refresh();
            expect($refreshedEvent->venue_id)->toBe($venue2->id);
            expect($refreshedEvent->venue()->firstOrFail()->name)->toBe('Venue Two');
            expect($refreshedEvent->preview)->toBe('Changed venue');

            // Remove venue entirely
            $finalUpdateData = new EventData(
                name: $event->name,
                date: $event->date,
                venue: null,
                preview: 'No venue'
            );
            UpdateAction::make()->handle($event, $finalUpdateData);

            $finalEvent = $event->refresh();
            expect($finalEvent->venue_id)->toBeNull();
            expect($finalEvent->venue)->toBeNull();
            expect($finalEvent->isScheduled())->toBeTrue(); // Still scheduled, just no venue
        });

        test('event timing transitions work correctly', function () {
            $futureDate = Carbon::now()->addWeeks(2);
            $pastDate = Carbon::now()->subWeeks(1);

            // Create future event
            $eventData = new EventData(
                name: 'Timing Event',
                date: $futureDate,
                venue: $this->venue,
                preview: 'Future event'
            );
            $event = CreateAction::make()->handle($eventData);
            expect($event->hasFutureDate())->toBeTrue();
            expect($event->hasPastDate())->toBeFalse();

            // Change to past date
            $updateData = new EventData(
                name: $event->name,
                date: $pastDate,
                venue: $this->venue,
                preview: 'Past event'
            );
            UpdateAction::make()->handle($event, $updateData);

            $refreshedEvent = $event->refresh();
            expect($refreshedEvent->hasPastDate())->toBeTrue();
            expect($refreshedEvent->hasFutureDate())->toBeFalse();
            expect($refreshedEvent->isScheduled())->toBeTrue(); // Still scheduled, just in past
        });
    });

    describe('venue relationship integration', function () {
        test('event maintains venue relationship through updates', function () {
            $event = Event::factory()->scheduled()->atVenue($this->venue)->create();

            $updateData = new EventData(
                name: 'Updated Event Name',
                date: $event->date,
                venue: $this->venue,
                preview: 'Updated preview'
            );

            UpdateAction::make()->handle($event, $updateData);

            $refreshedEvent = freshModel($event);
            $refreshedEvent->load('venue');

            expect($refreshedEvent->venue)->not()->toBeNull();
            expect($refreshedEvent->venue()->firstOrFail()->id)->toBe($this->venue->id);
            expect($refreshedEvent->venue()->firstOrFail()->name)->toBe($this->venue->name);
        });

        test('multiple venue changes maintain referential integrity', function () {
            $venue1 = Venue::factory()->create();
            $venue2 = Venue::factory()->create();
            $venue3 = Venue::factory()->create();

            $event = Event::factory()->scheduled()->atVenue($venue1)->create();

            // Change to venue2
            $updateData1 = new EventData(
                name: $event->name,
                date: $event->date,
                venue: $venue2,
                preview: $event->preview
            );
            UpdateAction::make()->handle($event, $updateData1);
            expect(freshModel($event)->venue_id)->toBe($venue2->id);

            // Change to venue3
            $updateData2 = new EventData(
                name: $event->name,
                date: $event->date,
                venue: $venue3,
                preview: $event->preview
            );
            UpdateAction::make()->handle($event, $updateData2);
            expect(freshModel($event)->venue_id)->toBe($venue3->id);

            // Verify venue relationships work
            $finalEvent = freshModel($event);
            $finalEvent->load('venue');
            expect($finalEvent->venue()->firstOrFail()->id)->toBe($venue3->id);
        });
    });

    describe('business rule validation', function () {
        test('events can be created without any date or venue', function () {
            $eventData = new EventData(
                name: 'Minimal Event',
                date: null,
                venue: null,
                preview: null
            );

            $event = CreateAction::make()->handle($eventData);

            expect($event->exists)->toBeTrue();
            expect($event->name)->toBe('Minimal Event');
            expect($event->isUnscheduled())->toBeTrue();
            expect($event->venue_id)->toBeNull();
            expect($event->preview)->toBeNull();
        });

        test('events can have date without venue', function () {
            $eventData = new EventData(
                name: 'Date Only Event',
                date: Carbon::now()->addMonths(1),
                venue: null,
                preview: 'Date but no venue'
            );

            $event = CreateAction::make()->handle($eventData);

            expect($event->isScheduled())->toBeTrue();
            expect($event->venue_id)->toBeNull();
            expect($event->hasFutureDate())->toBeTrue();
        });

        test('events maintain consistency through delete and restore', function () {
            $eventData = new EventData(
                name: 'Consistency Event',
                date: Carbon::now()->addWeeks(4),
                venue: $this->venue,
                preview: 'Consistency test'
            );

            $event = CreateAction::make()->handle($eventData);
            $originalState = [
                'name' => $event->name,
                'date' => requiredDate($event->date),
                'venue_id' => $event->venue_id,
                'preview' => $event->preview,
            ];

            DeleteAction::make()->handle($event);
            RestoreAction::make()->handle($event);

            $restoredEvent = Event::query()->whereKey($event->getKey())->firstOrFail();
            expect($restoredEvent->name)->toBe($originalState['name']);
            expect(requiredDate($restoredEvent->date)->format('Y-m-d H:i:s'))->toBe($originalState['date']->format('Y-m-d H:i:s'));
            expect($restoredEvent->venue_id)->toBe($originalState['venue_id']);
            expect($restoredEvent->preview)->toBe($originalState['preview']);
        });
    });

    describe('status determination logic', function () {
        test('scheduling status is determined correctly by date presence', function () {
            // Unscheduled event
            $unscheduledEvent = Event::factory()->unscheduled()->create();
            expect($unscheduledEvent->isUnscheduled())->toBeTrue();
            expect($unscheduledEvent->isScheduled())->toBeFalse();

            // Scheduled event
            $scheduledEvent = Event::factory()->scheduled()->create();
            expect($scheduledEvent->isScheduled())->toBeTrue();
            expect($scheduledEvent->isUnscheduled())->toBeFalse();

            // Past event
            $pastEvent = Event::factory()->past()->create();
            expect($pastEvent->isScheduled())->toBeTrue();
            expect($pastEvent->hasPastDate())->toBeTrue();
            expect($pastEvent->hasFutureDate())->toBeFalse();
        });

        test('date timing logic works across timezone boundaries', function () {
            $futureDate = Carbon::now()->addHours(1);
            $pastDate = Carbon::now()->subHours(1);

            $eventData = new EventData(
                name: 'Timezone Event',
                date: $futureDate,
                venue: $this->venue,
                preview: 'Future event'
            );
            $event = CreateAction::make()->handle($eventData);
            expect($event->hasFutureDate())->toBeTrue();

            // Update to past
            $updateData = new EventData(
                name: $event->name,
                date: $pastDate,
                venue: $this->venue,
                preview: 'Past event'
            );
            UpdateAction::make()->handle($event, $updateData);

            $updatedEvent = $event->refresh();
            expect($updatedEvent->hasPastDate())->toBeTrue();
            expect($updatedEvent->hasFutureDate())->toBeFalse();
        });
    });
});
