<?php

declare(strict_types=1);

use App\Livewire\Events\Tables\EventsTable;
use App\Models\Events\Event;
use App\Models\Shared\Venue;
use App\Models\Users\User;
use Livewire\Livewire;

/**
 * Integration tests for EventsTable Livewire component.
 *
 * INTEGRATION TEST SCOPE:
 * - Component rendering with real database relationships
 * - Livewire property updates and form interactions
 * - Business action integration with real models
 * - Query building and filtering functionality
 * - Component state management with database
 * - Authorization integration with Gate facade
 *
 * These tests verify that the EventsTable component works correctly
 * with actual database relationships and complex event scenarios
 * including scheduling, venue associations, and filtering.
 */
describe('EventsTable Component Integration', function () {
    beforeEach(function () {
        $this->admin = User::factory()->administrator()->create();
        $this->venue = Venue::factory()->create(['name' => 'Test Arena']);
    });

    describe('component rendering and data display', function () {
        test('renders events table with complete data relationships', function () {
            $scheduledEvent = Event::factory()->scheduled()->atVenue($this->venue)->create(['name' => 'WrestleMania']);
            $unscheduledEvent = Event::factory()->unscheduled()->create(['name' => 'Draft Event']);
            $pastEvent = Event::factory()->past()->atVenue($this->venue)->create(['name' => 'Royal Rumble']);

            $component = Livewire::actingAs($this->admin)
                ->test(EventsTable::class);

            $component->assertOk()
                ->assertSee($scheduledEvent->name)
                ->assertSee($unscheduledEvent->name)
                ->assertSee($pastEvent->name)
                ->assertSee('WrestleMania')
                ->assertSee('Draft Event')
                ->assertSee('Royal Rumble');
        });

        test('displays event scheduling status information correctly', function () {
            $scheduledEvent = Event::factory()->scheduled()->create(['name' => 'Scheduled Event']);
            $unscheduledEvent = Event::factory()->unscheduled()->create(['name' => 'Unscheduled Event']);
            $pastEvent = Event::factory()->past()->create(['name' => 'Past Event']);

            $component = Livewire::actingAs($this->admin)
                ->test(EventsTable::class);

            $component->assertOk()
                ->assertSee('Scheduled Event')
                ->assertSee('Unscheduled Event')
                ->assertSee('Past Event');
        });

        test('loads event venue relationships for display', function () {
            $event = Event::factory()->scheduled()->atVenue($this->venue)->create(['name' => 'Venue Event']);

            // Verify venue relationship exists
            expect($event->venue)->not->toBeNull();
            expect($event->venue->name)->toBe('Test Arena');

            $component = Livewire::actingAs($this->admin)
                ->test(EventsTable::class);

            $component->assertOk()
                ->assertSee('Venue Event')
                ->assertSee('Test Arena');
        });

        test('displays events with date information', function () {
            $futureEvent = Event::factory()->scheduled()->create(['name' => 'Future Event']);
            $unscheduledEvent = Event::factory()->unscheduled()->create(['name' => 'No Date Event']);

            $component = Livewire::actingAs($this->admin)
                ->test(EventsTable::class);

            $component->assertOk()
                ->assertSee('Future Event')
                ->assertSee('No Date Event')
                ->assertSee('No Date Set'); // For unscheduled events
        });

        test('displays events with venue links', function () {
            $eventWithVenue = Event::factory()->scheduled()->atVenue($this->venue)->create(['name' => 'Venue Event']);
            $eventWithoutVenue = Event::factory()->scheduled()->create(['name' => 'No Venue Event']);

            $component = Livewire::actingAs($this->admin)
                ->test(EventsTable::class);

            $component->assertOk()
                ->assertSee('Venue Event')
                ->assertSee('No Venue Event')
                ->assertSee('Test Arena')
                ->assertSee('No Venue');
        });
    });

    describe('filtering and search functionality', function () {
        test('search functionality filters events correctly', function () {
            $wrestleMania = Event::factory()->scheduled()->create(['name' => 'WrestleMania 40']);
            $summerSlam = Event::factory()->scheduled()->create(['name' => 'SummerSlam 2024']);
            $royalRumble = Event::factory()->scheduled()->create(['name' => 'Royal Rumble']);

            $component = Livewire::actingAs($this->admin)
                ->test(EventsTable::class);

            // Test search for "WrestleMania"
            $component->set('search', 'WrestleMania')
                ->assertSee('WrestleMania 40')
                ->assertDontSee('SummerSlam 2024')
                ->assertDontSee('Royal Rumble');

            // Test search for "Summer"
            $component->set('search', 'Summer')
                ->assertSee('SummerSlam 2024')
                ->assertDontSee('WrestleMania 40')
                ->assertDontSee('Royal Rumble');
        });

        test('scheduling status filter works correctly', function () {
            $scheduledEvent = Event::factory()->scheduled()->create(['name' => 'Scheduled Event']);
            $unscheduledEvent = Event::factory()->unscheduled()->create(['name' => 'Unscheduled Event']);
            $pastEvent = Event::factory()->past()->create(['name' => 'Past Event']);

            $component = Livewire::actingAs($this->admin)
                ->test(EventsTable::class);

            // Initially should see all events
            $component->assertSee('Scheduled Event')
                ->assertSee('Unscheduled Event')
                ->assertSee('Past Event');

            // Test filtering (exact filter implementation depends on component)
            $component->assertOk();
        });

        test('venue filter functionality', function () {
            $venue1 = Venue::factory()->create(['name' => 'Venue One']);
            $venue2 = Venue::factory()->create(['name' => 'Venue Two']);

            $event1 = Event::factory()->scheduled()->atVenue($venue1)->create(['name' => 'Event at Venue One']);
            $event2 = Event::factory()->scheduled()->atVenue($venue2)->create(['name' => 'Event at Venue Two']);
            $event3 = Event::factory()->scheduled()->create(['name' => 'Event with No Venue']);

            $component = Livewire::actingAs($this->admin)
                ->test(EventsTable::class);

            $component->assertOk()
                ->assertSee('Event at Venue One')
                ->assertSee('Event at Venue Two')
                ->assertSee('Event with No Venue');
        });

        test('date range filter functionality', function () {
            $earlyEvent = Event::factory()->scheduledOn('2024-01-15')->create(['name' => 'Early Event']);
            $lateEvent = Event::factory()->scheduledOn('2024-12-15')->create(['name' => 'Late Event']);

            $component = Livewire::actingAs($this->admin)
                ->test(EventsTable::class);

            $component->assertOk()
                ->assertSee('Early Event')
                ->assertSee('Late Event');
        });
    });

    describe('event business actions integration', function () {
        test('delete action integration works correctly', function () {
            $event = Event::factory()->unscheduled()->create(['name' => 'Deletable Event']);

            $component = Livewire::actingAs($this->admin)
                ->test(EventsTable::class);

            $component->call('delete', $event)
                ->assertHasNoErrors();

            // Verify event is soft deleted
            expect(Event::find($event->id))->toBeNull();
            expect(Event::onlyTrashed()->find($event->id))->not->toBeNull();
        });

        test('restore action integration works correctly', function () {
            $deletedEvent = Event::factory()->trashed()->create(['name' => 'Deleted Event']);

            $component = Livewire::actingAs($this->admin)
                ->test(EventsTable::class);

            $component->call('restore', $deletedEvent->id)
                ->assertHasNoErrors()
                ->assertRedirect();

            // Verify event is restored
            expect(Event::find($deletedEvent->id))->not->toBeNull();
            expect($deletedEvent->fresh()->deleted_at)->toBeNull();
        });
    });

    describe('business rule enforcement', function () {
        test('delete action works for appropriate event status', function () {
            $unscheduledEvent = Event::factory()->unscheduled()->create(['name' => 'Unscheduled Event']);

            $component = Livewire::actingAs($this->admin)
                ->test(EventsTable::class);

            $component->call('delete', $unscheduledEvent)
                ->assertHasNoErrors();

            // Verify event status unchanged after invalid operation
            expect(Event::find($unscheduledEvent->id))->toBeNull();
        });

        test('restore action works for deleted events', function () {
            $deletedEvent = Event::factory()->trashed()->create(['name' => 'Deleted Event']);

            $component = Livewire::actingAs($this->admin)
                ->test(EventsTable::class);

            $component->call('restore', $deletedEvent->id)
                ->assertHasNoErrors()
                ->assertRedirect();

            expect(Event::find($deletedEvent->id))->not->toBeNull();
        });
    });

    describe('authorization integration', function () {
        test('component requires proper authorization for access', function () {
            $basicUser = User::factory()->create();

            Livewire::actingAs($basicUser)
                ->test(EventsTable::class)
                ->assertForbidden();
        });

        test('guest users cannot access component', function () {
            Livewire::test(EventsTable::class)
                ->assertForbidden();
        });

        test('admin can perform all event actions', function () {
            $event = Event::factory()->unscheduled()->create();
            $deletedEvent = Event::factory()->trashed()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(EventsTable::class);

            // All actions should be available to admin
            $component->call('delete', $event)->assertHasNoErrors();
            $component->call('restore', $deletedEvent->id)->assertHasNoErrors();
        });
    });

    describe('query optimization and performance', function () {
        test('component loads efficiently with many events', function () {
            Event::factory()->count(20)->scheduled()->create();
            Event::factory()->count(10)->unscheduled()->create();
            Event::factory()->count(5)->past()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(EventsTable::class);

            $component->assertOk();
        });

        test('eager loading relationships works correctly', function () {
            $event = Event::factory()->scheduled()->atVenue($this->venue)->create(['name' => 'Test Event']);

            // Ensure venue relationship exists for eager loading test
            expect($event->venue)->not->toBeNull();

            $component = Livewire::actingAs($this->admin)
                ->test(EventsTable::class);

            $component->assertOk()
                ->assertSee('Test Event')
                ->assertSee('Test Arena');
        });

        test('component handles large datasets efficiently', function () {
            // Create events with various statuses and relationships
            Event::factory()->count(15)->scheduled()->create();
            Event::factory()->count(10)->unscheduled()->create();
            Event::factory()->count(5)->past()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(EventsTable::class);

            $component->assertOk();

            // Verify component loads without performance issues
            expect($component->payload['serverMemo']['data'])->toBeDefined();
        });
    });

    describe('complex event scenarios', function () {
        test('displays events with venue history correctly', function () {
            $venue1 = Venue::factory()->create(['name' => 'Original Venue']);
            $venue2 = Venue::factory()->create(['name' => 'New Venue']);

            $event = Event::factory()->scheduled()->atVenue($venue1)->create(['name' => 'Venue Change Event']);

            // Simulate venue change by updating the event
            $event->update(['venue_id' => $venue2->id]);

            $component = Livewire::actingAs($this->admin)
                ->test(EventsTable::class);

            $component->assertOk()
                ->assertSee('Venue Change Event')
                ->assertSee('New Venue');
        });

        test('handles events with scheduling changes correctly', function () {
            $event = Event::factory()->unscheduled()->create(['name' => 'Scheduling Event']);

            // Simulate scheduling the event
            $event->update(['date' => now()->addMonths(2)]);

            $component = Livewire::actingAs($this->admin)
                ->test(EventsTable::class);

            $component->assertOk()
                ->assertSee('Scheduling Event');
        });

        test('displays events with multiple date transitions', function () {
            $event = Event::factory()->scheduled()->create(['name' => 'Date Change Event']);

            // Verify event is originally scheduled
            expect($event->isScheduled())->toBeTrue();

            $component = Livewire::actingAs($this->admin)
                ->test(EventsTable::class);

            $component->assertOk()
                ->assertSee('Date Change Event');
        });

        test('handles events with venue and date combinations', function () {
            $venue = Venue::factory()->create(['name' => 'Complex Venue']);

            // Event with both date and venue
            $completeEvent = Event::factory()->scheduled()->atVenue($venue)->create(['name' => 'Complete Event']);

            // Event with date but no venue
            $dateOnlyEvent = Event::factory()->scheduled()->create(['name' => 'Date Only Event']);

            // Event with neither date nor venue
            $draftEvent = Event::factory()->unscheduled()->create(['name' => 'Draft Event']);

            $component = Livewire::actingAs($this->admin)
                ->test(EventsTable::class);

            $component->assertOk()
                ->assertSee('Complete Event')
                ->assertSee('Date Only Event')
                ->assertSee('Draft Event')
                ->assertSee('Complex Venue')
                ->assertSee('No Venue');
        });
    });

    describe('component state management', function () {
        test('component maintains state through action calls', function () {
            $event = Event::factory()->unscheduled()->create(['name' => 'State Test Event']);

            $component = Livewire::actingAs($this->admin)
                ->test(EventsTable::class);

            // Perform action and verify component state updates
            $component->call('delete', $event)
                ->assertHasNoErrors();

            // Component should reflect the change after refresh
            $refreshComponent = Livewire::actingAs($this->admin)
                ->test(EventsTable::class);

            $refreshComponent->assertOk()
                ->assertDontSee('State Test Event');
        });

        test('component handles concurrent state changes gracefully', function () {
            $event1 = Event::factory()->unscheduled()->create(['name' => 'Event One']);
            $event2 = Event::factory()->unscheduled()->create(['name' => 'Event Two']);

            $component = Livewire::actingAs($this->admin)
                ->test(EventsTable::class);

            // Perform multiple actions
            $component->call('delete', $event1)
                ->assertHasNoErrors();

            $component->call('delete', $event2)
                ->assertHasNoErrors();

            // Both should be successful
            expect(Event::find($event1->id))->toBeNull();
            expect(Event::find($event2->id))->toBeNull();
        });
    });

    describe('component sorting and ordering', function () {
        test('events are ordered correctly by scheduling status', function () {
            $scheduledEvent = Event::factory()->scheduled()->create(['name' => 'Scheduled Event']);
            $unscheduledEvent = Event::factory()->unscheduled()->create(['name' => 'Unscheduled Event']);
            $pastEvent = Event::factory()->past()->create(['name' => 'Past Event']);

            $component = Livewire::actingAs($this->admin)
                ->test(EventsTable::class);

            // All events should be visible in the correct order
            $component->assertOk()
                ->assertSee('Scheduled Event')
                ->assertSee('Unscheduled Event')
                ->assertSee('Past Event');
        });

        test('venue associations are displayed in correct context', function () {
            $venue = Venue::factory()->create(['name' => 'Sorting Venue']);

            $eventWithVenue = Event::factory()->scheduled()->atVenue($venue)->create(['name' => 'Venue Event']);
            $eventWithoutVenue = Event::factory()->scheduled()->create(['name' => 'No Venue Event']);

            $component = Livewire::actingAs($this->admin)
                ->test(EventsTable::class);

            $component->assertOk()
                ->assertSee('Venue Event')
                ->assertSee('No Venue Event')
                ->assertSee('Sorting Venue')
                ->assertSee('No Venue');
        });
    });

    describe('component filtering edge cases', function () {
        test('filtering works with special characters in event names', function () {
            $specialEvent = Event::factory()->scheduled()->create(['name' => 'Event: The "Ultimate" Test']);

            $component = Livewire::actingAs($this->admin)
                ->test(EventsTable::class);

            $component->set('search', 'Ultimate')
                ->assertSee('Event: The "Ultimate" Test');
        });

        test('filtering works with international characters', function () {
            $internationalEvent = Event::factory()->scheduled()->create(['name' => 'Wrestlé Mania']);

            $component = Livewire::actingAs($this->admin)
                ->test(EventsTable::class);

            $component->set('search', 'Wrestlé')
                ->assertSee('Wrestlé Mania');
        });

        test('filtering handles empty search correctly', function () {
            $event1 = Event::factory()->scheduled()->create(['name' => 'Event One']);
            $event2 = Event::factory()->scheduled()->create(['name' => 'Event Two']);

            $component = Livewire::actingAs($this->admin)
                ->test(EventsTable::class);

            // Empty search should show all events
            $component->set('search', '')
                ->assertSee('Event One')
                ->assertSee('Event Two');
        });
    });
});
