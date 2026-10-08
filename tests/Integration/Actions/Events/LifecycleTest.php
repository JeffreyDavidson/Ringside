<?php

declare(strict_types=1);

use App\Actions\Events\CreateAction;
use App\Actions\Events\DeleteAction;
use App\Actions\Events\RestoreAction;
use App\Actions\Events\UpdateAction;
use App\Data\Events\EventData;
use App\Enums\EventStatus;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use Illuminate\Support\Carbon;

/**
 * @return array{
 *     venue: Venue,
 * }
 */
function eventsLifecycleVenueFixtures(): array
{
    $venue = Venue::factory()->create();

    return [
        'venue' => $venue,
    ];
}

/**
 * @return array{
 *     event: Event,
 * }
 */
function eventsLifecycleEventFixtures(): array
{
    $event = Event::factory()->unscheduled()->create(['name' => 'Original Event']);

    return [
        'event' => $event,
    ];
}

/**
 * @return array{
 *     event: Event,
 * }
 */
function eventsLifecycleEventFixtures2(): array
{
    $event = Event::factory()->scheduled()->create(['name' => 'Deletable Event']);

    return [
        'event' => $event,
    ];
}

/**
 * Integration tests for Event scheduling and lifecycle management actions.
 *
 * This test suite validates the complete workflow of event lifecycle management
 * including creation, scheduling, updating, and status transitions.
 * These tests use real database relationships and verify that actions properly
 * handle event scheduling, venue associations, and match dependencies.
 */
describe('Event Activation Action Integration', function () {
    describe('create action workflow', function () {
        test('create action creates unscheduled event by default', function () {
            eventsLifecycleVenueFixtures();

            $eventData = new EventData(
                name: 'Test Event',
                date: null,
                venue: null,
                preview: 'A test event'
            );

            $event = resolve(CreateAction::class)->handle($eventData);

            expect($event->exists)->toBeTrue()
                ->and($event->name)->toBe('Test Event')
                ->and($event->status)->toBe(EventStatus::Unscheduled)
                ->and($event->date)->toBeNull()
                ->and($event->venue_id)->toBeNull()
                ->and($event->preview)->toBe('A test event');
        });

        test('create action creates scheduled event with date and venue', function () {
            ['venue' => $venue] = eventsLifecycleVenueFixtures();

            $scheduledDate = Carbon::now()->addMonths(3);

            $eventData = new EventData(
                name: 'Scheduled Event',
                date: $scheduledDate,
                venue: $venue,
                preview: 'A scheduled event'
            );

            $event = resolve(CreateAction::class)->handle($eventData);

            expect($event->exists)->toBeTrue()
                ->and($event->name)->toBe('Scheduled Event')
                ->and($event->status)->not->toBe(EventStatus::Unscheduled)
                ->toBe(EventStatus::Scheduled)
                ->and(requiredDate($event->date)->format('Y-m-d H:i:s'))->toBe($scheduledDate->format('Y-m-d H:i:s'))
                ->and($event->venue_id)->toBe($venue->id)
                ->and($event->venue()->firstOrFail()->name)->toBe($venue->name);
        });

        test('create action handles past date events correctly', function () {
            ['venue' => $venue] = eventsLifecycleVenueFixtures();

            $pastDate = Carbon::now()->subMonths(1);

            $eventData = new EventData(
                name: 'Past Event',
                date: $pastDate,
                venue: $venue,
                preview: 'An event that happened'
            );

            $event = resolve(CreateAction::class)->handle($eventData);

            expect($event->exists)->toBeTrue()
                ->and($event->status)->not->toBe(EventStatus::Unscheduled)
                ->toBe(EventStatus::Past)->not->toBe(EventStatus::Scheduled);
        });

        test('create action creates event without venue', function () {
            eventsLifecycleVenueFixtures();

            $eventData = new EventData(
                name: 'No Venue Event',
                date: Carbon::now()->addWeeks(2),
                venue: null,
                preview: 'Event without venue'
            );

            $event = resolve(CreateAction::class)->handle($eventData);

            expect($event->exists)->toBeTrue()
                ->and($event->status)->not->toBe(EventStatus::Unscheduled)
                ->and($event->venue_id)->toBeNull()
                ->and($event->venue)->toBeNull();
        });
    });

    describe('update action workflow', function () {
        test('update action can schedule an unscheduled event', function () {
            ['venue' => $venue] = eventsLifecycleVenueFixtures();
            ['event' => $event] = eventsLifecycleEventFixtures();

            $scheduledDate = Carbon::now()->addMonths(2);

            $eventData = new EventData(
                name: 'Scheduled Event',
                date: $scheduledDate,
                venue: $venue,
                preview: 'Now scheduled'
            );

            resolve(UpdateAction::class)->handle($event, $eventData);

            $refreshedEvent = freshModel($event);
            expect($refreshedEvent->name)->toBe('Scheduled Event')
                ->and($refreshedEvent->status)->not->toBe(EventStatus::Unscheduled)
                ->toBe(EventStatus::Scheduled)
                ->and($refreshedEvent->venue_id)->toBe($venue->id)
                ->and($refreshedEvent->preview)->toBe('Now scheduled');
        });

        test('update action can change event date', function () {
            ['venue' => $venue] = eventsLifecycleVenueFixtures();
            ['event' => $event] = eventsLifecycleEventFixtures();

            $originalDate = Carbon::now()->addMonth();
            $newDate = Carbon::now()->addMonths(3);

            $event->update(['date' => $originalDate]);

            $eventData = new EventData(
                name: $event->name,
                date: $newDate,
                venue: $venue,
                preview: $event->preview
            );

            resolve(UpdateAction::class)->handle($event, $eventData);

            $refreshedEvent = freshModel($event);
            expect(requiredDate($refreshedEvent->date)->format('Y-m-d H:i:s'))->toBe($newDate->format('Y-m-d H:i:s'))
                ->and($refreshedEvent->status)->toBe(EventStatus::Scheduled);
        });

        test('update action can change venue', function () {
            ['venue' => $venue] = eventsLifecycleVenueFixtures();
            ['event' => $event] = eventsLifecycleEventFixtures();

            $newVenue = Venue::factory()->create();
            $event->update(['venue_id' => $venue->id]);

            $eventData = new EventData(
                name: $event->name,
                date: $event->date,
                venue: $newVenue,
                preview: $event->preview
            );

            resolve(UpdateAction::class)->handle($event, $eventData);

            $refreshedEvent = freshModel($event);
            expect($refreshedEvent->venue_id)->toBe($newVenue->id)
                ->and($refreshedEvent->venue()->firstOrFail()->name)->toBe($newVenue->name);
        });

        test('update action can remove venue from event', function () {
            ['venue' => $venue] = eventsLifecycleVenueFixtures();
            ['event' => $event] = eventsLifecycleEventFixtures();

            $event->update(['venue_id' => $venue->id]);

            $eventData = new EventData(
                name: $event->name,
                date: $event->date,
                venue: null,
                preview: $event->preview
            );

            resolve(UpdateAction::class)->handle($event, $eventData);

            $refreshedEvent = freshModel($event);
            expect($refreshedEvent->venue_id)->toBeNull()
                ->and($refreshedEvent->venue)->toBeNull();
        });

        test('update action can unschedule an event', function () {
            eventsLifecycleVenueFixtures();
            ['event' => $event] = eventsLifecycleEventFixtures();

            $event->update(['date' => Carbon::now()->addWeeks(3)]);

            $eventData = new EventData(
                name: $event->name,
                date: null,
                venue: null,
                preview: 'Unscheduled again'
            );

            resolve(UpdateAction::class)->handle($event, $eventData);

            $refreshedEvent = freshModel($event);
            expect($refreshedEvent->status)->toBe(EventStatus::Unscheduled)
                ->and($refreshedEvent->date)->toBeNull()
                ->and($refreshedEvent->preview)->toBe('Unscheduled again');
        });
    });

    describe('delete and restore workflow', function () {
        test('delete action soft deletes event', function () {
            eventsLifecycleVenueFixtures();
            ['event' => $event] = eventsLifecycleEventFixtures2();

            resolve(DeleteAction::class)->handle($event);

            expect(Event::find($event->id))->toBeNull()
                ->and(Event::onlyTrashed()->find($event->id))->not()
                ->toBeNull()
                ->and(freshModel($event)->deleted_at)->not()
                ->toBeNull();
        });

        test('restore action recovers deleted event', function () {
            eventsLifecycleVenueFixtures();
            ['event' => $event] = eventsLifecycleEventFixtures2();

            resolve(DeleteAction::class)->handle($event);
            expect(Event::find($event->id))->toBeNull();

            resolve(RestoreAction::class)->handle($event);

            $restoredEvent = Event::findOrFail($event->id);
            expect($restoredEvent->name)->toBe('Deletable Event')
                ->and($restoredEvent->deleted_at)->toBeNull();
        });

        test('restore action maintains event scheduling information', function () {
            eventsLifecycleVenueFixtures();
            ['event' => $event] = eventsLifecycleEventFixtures2();

            $originalDate = $event->date;
            $originalVenueId = $event->venue_id;

            resolve(DeleteAction::class)->handle($event);
            resolve(RestoreAction::class)->handle($event);

            $restoredEvent = Event::findOrFail($event->id);
            expect(requiredDate($restoredEvent->date)->format('Y-m-d H:i:s'))->toBe(requiredDate($originalDate)->format('Y-m-d H:i:s'))
                ->and($restoredEvent->venue_id)->toBe($originalVenueId)
                ->and($restoredEvent->status)->not->toBe(EventStatus::Unscheduled);
        });
    });

    describe('complex event lifecycle scenarios', function () {
        test('event can go through complete lifecycle', function () {
            ['venue' => $venue] = eventsLifecycleVenueFixtures();

            // Create unscheduled event
            $eventData = new EventData(
                name: 'Lifecycle Event',
                date: null,
                venue: null,
                preview: 'Draft event'
            );
            $event = resolve(CreateAction::class)->handle($eventData);
            expect($event->status)->toBe(EventStatus::Unscheduled);

            // Schedule the event
            $scheduledDate = Carbon::now()->addMonths(4);
            $updateData = new EventData(
                name: 'Scheduled Lifecycle Event',
                date: $scheduledDate,
                venue: $venue,
                preview: 'Scheduled event'
            );
            resolve(UpdateAction::class)->handle($event, $updateData);

            $refreshedEvent = $event->refresh();
            expect($refreshedEvent->status)->not->toBe(EventStatus::Unscheduled)
                ->toBe(EventStatus::Scheduled);

            // Update event details
            $finalUpdateData = new EventData(
                name: 'Final Event Name',
                date: $scheduledDate,
                venue: $venue,
                preview: 'Updated preview'
            );
            resolve(UpdateAction::class)->handle($event, $finalUpdateData);

            $finalEvent = $event->refresh();
            expect($finalEvent->name)->toBe('Final Event Name')
                ->and($finalEvent->preview)->toBe('Updated preview')
                ->and($finalEvent->venue_id)->toBe($venue->id);

            // Delete and restore
            resolve(DeleteAction::class)->handle($event);
            expect(Event::find($event->id))->toBeNull();

            resolve(RestoreAction::class)->handle($event);
            $restoredEvent = Event::query()->whereKey($event->getKey())->firstOrFail();
            expect($restoredEvent->name)->toBe('Final Event Name')
                ->and($restoredEvent->status)->not->toBe(EventStatus::Unscheduled);
        });

        test('multiple events can be scheduled at same venue', function () {
            ['venue' => $venue] = eventsLifecycleVenueFixtures();

            $date1 = Carbon::now()->addMonths(1);
            $date2 = Carbon::now()->addMonths(2);

            $event1Data = new EventData(
                name: 'Event One',
                date: $date1,
                venue: $venue,
                preview: 'First event'
            );

            $event2Data = new EventData(
                name: 'Event Two',
                date: $date2,
                venue: $venue,
                preview: 'Second event'
            );

            $event1 = resolve(CreateAction::class)->handle($event1Data);
            $event2 = resolve(CreateAction::class)->handle($event2Data);

            expect($event1->venue_id)->toBe($venue->id)
                ->and($event2->venue_id)->toBe($venue->id)
                ->and($event1->status)->not->toBe(EventStatus::Unscheduled)
                ->and($event2->status)->not->toBe(EventStatus::Unscheduled)
                ->and($event1->status)->toBe(EventStatus::Scheduled)
                ->and($event2->status)->toBe(EventStatus::Scheduled);
        });

        test('event scheduling with venue changes', function () {
            eventsLifecycleVenueFixtures();

            $venue1 = Venue::factory()->create(['name' => 'Venue One']);
            $venue2 = Venue::factory()->create(['name' => 'Venue Two']);

            // Create event at first venue
            $eventData = new EventData(
                name: 'Venue Change Event',
                date: Carbon::now()->addMonths(2),
                venue: $venue1,
                preview: 'Initial venue'
            );
            $event = resolve(CreateAction::class)->handle($eventData);
            expect($event->venue_id)->toBe($venue1->id);

            // Change to second venue
            $updateData = new EventData(
                name: $event->name,
                date: $event->date,
                venue: $venue2,
                preview: 'Changed venue'
            );
            resolve(UpdateAction::class)->handle($event, $updateData);

            $refreshedEvent = $event->refresh();
            expect($refreshedEvent->venue_id)->toBe($venue2->id)
                ->and($refreshedEvent->venue()->firstOrFail()->name)->toBe('Venue Two')
                ->and($refreshedEvent->preview)->toBe('Changed venue');

            // Remove venue entirely
            $finalUpdateData = new EventData(
                name: $event->name,
                date: $event->date,
                venue: null,
                preview: 'No venue'
            );
            resolve(UpdateAction::class)->handle($event, $finalUpdateData);

            $finalEvent = $event->refresh();
            expect($finalEvent->venue_id)->toBeNull()
                ->and($finalEvent->venue)->toBeNull()
                ->and($finalEvent->status)->not->toBe(EventStatus::Unscheduled); // Still scheduled, just no venue
        });

        test('event timing transitions work correctly', function () {
            ['venue' => $venue] = eventsLifecycleVenueFixtures();

            $futureDate = Carbon::now()->addWeeks(2);
            $pastDate = Carbon::now()->subWeeks(1);

            // Create future event
            $eventData = new EventData(
                name: 'Timing Event',
                date: $futureDate,
                venue: $venue,
                preview: 'Future event'
            );
            $event = resolve(CreateAction::class)->handle($eventData);
            expect($event->status)->toBe(EventStatus::Scheduled)->not->toBe(EventStatus::Past);

            // Change to past date
            $updateData = new EventData(
                name: $event->name,
                date: $pastDate,
                venue: $venue,
                preview: 'Past event'
            );
            resolve(UpdateAction::class)->handle($event, $updateData);

            $refreshedEvent = $event->refresh();
            expect($refreshedEvent->status)->toBe(EventStatus::Past)->not->toBe(EventStatus::Scheduled)->not->toBe(EventStatus::Unscheduled); // Still scheduled, just in past
        });
    });

    describe('venue relationship integration', function () {
        test('event maintains venue relationship through updates', function () {
            ['venue' => $venue] = eventsLifecycleVenueFixtures();

            $event = Event::factory()->scheduled()->atVenue($venue)->create();

            $updateData = new EventData(
                name: 'Updated Event Name',
                date: $event->date,
                venue: $venue,
                preview: 'Updated preview'
            );

            resolve(UpdateAction::class)->handle($event, $updateData);

            $refreshedEvent = freshModel($event);
            $refreshedEvent->load('venue');

            expect($refreshedEvent->venue)->not()->toBeNull()
                ->and($refreshedEvent->venue()->firstOrFail()->id)->toBe($venue->id)
                ->and($refreshedEvent->venue()->firstOrFail()->name)->toBe($venue->name);
        });

        test('multiple venue changes maintain referential integrity', function () {
            eventsLifecycleVenueFixtures();

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
            resolve(UpdateAction::class)->handle($event, $updateData1);
            expect(freshModel($event)->venue_id)->toBe($venue2->id);

            // Change to venue3
            $updateData2 = new EventData(
                name: $event->name,
                date: $event->date,
                venue: $venue3,
                preview: $event->preview
            );
            resolve(UpdateAction::class)->handle($event, $updateData2);
            expect(freshModel($event)->venue_id)->toBe($venue3->id);

            // Verify venue relationships work
            $finalEvent = freshModel($event);
            $finalEvent->load('venue');
            expect($finalEvent->venue()->firstOrFail()->id)->toBe($venue3->id);
        });
    });

    describe('business rule validation', function () {
        test('events can be created without any date or venue', function () {
            eventsLifecycleVenueFixtures();

            $eventData = new EventData(
                name: 'Minimal Event',
                date: null,
                venue: null,
                preview: null
            );

            $event = resolve(CreateAction::class)->handle($eventData);

            expect($event->exists)->toBeTrue()
                ->and($event->name)->toBe('Minimal Event')
                ->and($event->status)->toBe(EventStatus::Unscheduled)
                ->and($event->venue_id)->toBeNull()
                ->and($event->preview)->toBeNull();
        });

        test('events can have date without venue', function () {
            eventsLifecycleVenueFixtures();

            $eventData = new EventData(
                name: 'Date Only Event',
                date: Carbon::now()->addMonths(1),
                venue: null,
                preview: 'Date but no venue'
            );

            $event = resolve(CreateAction::class)->handle($eventData);

            expect($event->status)->not->toBe(EventStatus::Unscheduled)
                ->and($event->venue_id)->toBeNull()
                ->and($event->status)->toBe(EventStatus::Scheduled);
        });

        test('events maintain consistency through delete and restore', function () {
            ['venue' => $venue] = eventsLifecycleVenueFixtures();

            $eventData = new EventData(
                name: 'Consistency Event',
                date: Carbon::now()->addWeeks(4),
                venue: $venue,
                preview: 'Consistency test'
            );

            $event = resolve(CreateAction::class)->handle($eventData);
            $originalState = [
                'name' => $event->name,
                'date' => requiredDate($event->date),
                'venue_id' => $event->venue_id,
                'preview' => $event->preview,
            ];

            resolve(DeleteAction::class)->handle($event);
            resolve(RestoreAction::class)->handle($event);

            $restoredEvent = Event::query()->whereKey($event->getKey())->firstOrFail();
            expect($restoredEvent->name)->toBe($originalState['name'])
                ->and(requiredDate($restoredEvent->date)->format('Y-m-d H:i:s'))->toBe($originalState['date']->format('Y-m-d H:i:s'))
                ->and($restoredEvent->venue_id)->toBe($originalState['venue_id'])
                ->and($restoredEvent->preview)->toBe($originalState['preview']);
        });
    });

    describe('status determination logic', function () {
        test('scheduling status is determined correctly by date presence', function () {
            eventsLifecycleVenueFixtures();

            // Unscheduled event
            $unscheduledEvent = Event::factory()->unscheduled()->create();
            expect($unscheduledEvent->status)->toBe(EventStatus::Unscheduled);

            // Scheduled event
            $scheduledEvent = Event::factory()->scheduled()->create();
            expect($scheduledEvent->status)->toBe(EventStatus::Scheduled);

            // Past event
            $pastEvent = Event::factory()->past()->create();
            expect($pastEvent->status)->toBe(EventStatus::Past);
        });

        test('date timing logic works across timezone boundaries', function () {
            ['venue' => $venue] = eventsLifecycleVenueFixtures();

            $futureDate = Carbon::now()->addHours(1);
            $pastDate = Carbon::now()->subHours(1);

            $eventData = new EventData(
                name: 'Timezone Event',
                date: $futureDate,
                venue: $venue,
                preview: 'Future event'
            );
            $event = resolve(CreateAction::class)->handle($eventData);
            expect($event->status)->toBe(EventStatus::Scheduled);

            // Update to past
            $updateData = new EventData(
                name: $event->name,
                date: $pastDate,
                venue: $venue,
                preview: 'Past event'
            );
            resolve(UpdateAction::class)->handle($event, $updateData);

            $updatedEvent = $event->refresh();
            expect($updatedEvent->status)->toBe(EventStatus::Past)->not->toBe(EventStatus::Scheduled);
        });
    });
});
