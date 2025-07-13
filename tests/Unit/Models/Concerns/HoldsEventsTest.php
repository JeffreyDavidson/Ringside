<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use App\Models\Concerns\HoldsEvents;
use App\Models\Events\Event;
use App\Models\Shared\Venue;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ReflectionClass;

/**
 * Unit tests for HoldsEvents trait.
 *
 * UNIT TEST SCOPE:
 * - Trait relationship method definitions
 * - Trait integration with model functionality
 * - Event relationship configurations and behavior
 *
 * These tests verify that the HoldsEvents trait correctly provides
 * relationship methods for accessing events and related data.
 */
describe('HoldsEvents Trait Unit Tests', function () {
    describe('trait method definitions', function () {
        test('trait provides event relationship methods', function () {
            $reflection = new ReflectionClass(HoldsEvents::class);

            expect($reflection->hasMethod('events'))->toBeTrue();
            expect($reflection->hasMethod('previousEvents'))->toBeTrue();
            expect($reflection->hasMethod('futureEvents'))->toBeTrue();
            expect($reflection->hasMethod('getEventsRelation'))->toBeTrue();
        });

        test('event methods are public', function () {
            $reflection = new ReflectionClass(HoldsEvents::class);

            expect($reflection->getMethod('events')->isPublic())->toBeTrue();
            expect($reflection->getMethod('previousEvents')->isPublic())->toBeTrue();
            expect($reflection->getMethod('futureEvents')->isPublic())->toBeTrue();
        });

        test('getEventsRelation method is protected', function () {
            $reflection = new ReflectionClass(HoldsEvents::class);

            expect($reflection->getMethod('getEventsRelation')->isProtected())->toBeTrue();
        });
    });

    describe('event relationship functionality', function () {
        test('events relationship returns correct type', function () {
            $venue = Venue::factory()->make();
            expect($venue->events())->toBeInstanceOf(HasMany::class);
        });

        test('model can have events', function () {
            $venue = Venue::factory()->create();
            $event = Event::factory()->atVenue($venue)->create();

            expect($venue->events->pluck('id'))->toContain($event->id);
            expect($event->venue_id)->toBe($venue->id);
        });

        test('model can have multiple events', function () {
            $venue = Venue::factory()->create();
            $event1 = Event::factory()->atVenue($venue)->create(['name' => 'Event 1']);
            $event2 = Event::factory()->atVenue($venue)->create(['name' => 'Event 2']);

            $venue->load('events');

            expect($venue->events)->toHaveCount(2);
            expect($venue->events->pluck('name'))->toContain('Event 1');
            expect($venue->events->pluck('name'))->toContain('Event 2');
        });

        test('model can have no events', function () {
            $venue = Venue::factory()->create();
            expect($venue->events)->toBeEmpty();
        });
    });

    describe('previous events functionality', function () {
        test('previous events relationship returns correct type', function () {
            $venue = Venue::factory()->make();
            expect($venue->previousEvents())->toBeInstanceOf(HasMany::class);
        });

        test('previous events filters past events correctly', function () {
            $venue = Venue::factory()->create();

            // Create past event
            $pastEvent = Event::factory()->atVenue($venue)->create([
                'name' => 'Past Event',
                'date' => Carbon::yesterday(),
            ]);

            // Create future event
            $futureEvent = Event::factory()->atVenue($venue)->create([
                'name' => 'Future Event',
                'date' => Carbon::tomorrow(),
            ]);

            $venue->load('previousEvents');

            expect($venue->previousEvents)->toHaveCount(1);
            expect($venue->previousEvents->first()->name)->toBe('Past Event');
            expect($venue->previousEvents->pluck('name'))->not->toContain('Future Event');
        });

        test('with no past events returns empty collection', function () {
            $venue = Venue::factory()->create();

            // Only create future events
            Event::factory()->atVenue($venue)->create(['date' => Carbon::tomorrow()]);

            $venue->load('previousEvents');
            expect($venue->previousEvents)->toBeEmpty();
        });

        test('previous events excludes events without dates', function () {
            $venue = Venue::factory()->create();

            // Create unscheduled event
            Event::factory()->atVenue($venue)->create([
                'name' => 'Unscheduled Event',
                'date' => null,
            ]);

            // Create past event
            Event::factory()->atVenue($venue)->create([
                'name' => 'Past Event',
                'date' => Carbon::yesterday(),
            ]);

            $venue->load('previousEvents');

            expect($venue->previousEvents)->toHaveCount(1);
            expect($venue->previousEvents->first()->name)->toBe('Past Event');
        });
    });

    describe('event relationship business logic', function () {
        test('model can exist without events', function () {
            $venue = Venue::factory()->create();

            expect($venue->events()->count())->toBe(0);
            expect($venue->previousEvents()->count())->toBe(0);
        });

        test('with many past events filters correctly', function () {
            $venue = Venue::factory()->create();

            // Create multiple past events
            Event::factory()->count(5)->atVenue($venue)->create([
                'date' => Carbon::yesterday(),
            ]);

            // Create multiple future events
            Event::factory()->count(3)->atVenue($venue)->create([
                'date' => Carbon::tomorrow(),
            ]);

            $venue->load(['events', 'previousEvents']);

            expect($venue->events)->toHaveCount(8);
            expect($venue->previousEvents)->toHaveCount(5);
        });

        test('maintains relationship integrity when events are deleted', function () {
            $venue = Venue::factory()->create();
            $event = Event::factory()->atVenue($venue)->create();

            expect($venue->events->pluck('id'))->toContain($event->id);

            $event->delete();
            $venue->refresh();

            expect($venue->events()->count())->toBe(0);
        });

        test('can be associated with events after creation', function () {
            $venue = Venue::factory()->create();

            expect($venue->events)->toBeEmpty();

            $event = Event::factory()->create(['venue_id' => $venue->id]);
            $venue->refresh();

            expect($venue->events->pluck('id'))->toContain($event->id);
        });

        test('previous events updates when event dates change', function () {
            $venue = Venue::factory()->create();
            $event = Event::factory()->atVenue($venue)->create([
                'date' => Carbon::tomorrow(),
            ]);

            $venue->load('previousEvents');
            expect($venue->previousEvents)->toBeEmpty();

            // Change event to past date
            $event->update(['date' => Carbon::yesterday()]);
            $venue->refresh();
            $venue->load('previousEvents');

            expect($venue->previousEvents->pluck('id'))->toContain($event->id);
        });
    });
});
