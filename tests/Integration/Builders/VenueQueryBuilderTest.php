<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Shared\Venue;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Unit tests for VenueBuilder query scopes.
 *
 * UNIT TEST SCOPE:
 * - Query scope methods in isolation
 * - Filter logic validation
 * - Builder method chaining
 * - Event relationship query optimization
 * - Date-based filtering accuracy
 *
 * These tests verify that the VenueBuilder correctly filters
 * venues based on their event hosting history and future
 * bookings without executing complex database operations.
 */
describe('VenueBuilder Query Scopes', function () {
    beforeEach(function () {
        $this->venueWithEvents = Venue::factory()->create(['name' => 'Event Venue']);
        $this->venueWithoutEvents = Venue::factory()->create(['name' => 'Empty Venue']);
        $this->venueWithPastEvents = Venue::factory()->create(['name' => 'Past Events Venue']);
        $this->venueWithFutureEvents = Venue::factory()->create(['name' => 'Future Events Venue']);
        $this->venueWithBothEvents = Venue::factory()->create(['name' => 'Mixed Events Venue']);

        // Create events for different venues
        Event::factory()->atVenue($this->venueWithEvents)->create(['date' => Carbon::now()->subHour()]);  // Past event (1 hour ago)

        Event::factory()->atVenue($this->venueWithPastEvents)->create([
            'date' => Carbon::yesterday(),
            'name' => 'Past Event',
        ]);

        Event::factory()->atVenue($this->venueWithFutureEvents)->create([
            'date' => Carbon::tomorrow(),
            'name' => 'Future Event',
        ]);

        // Venue with both past and future events
        Event::factory()->atVenue($this->venueWithBothEvents)->create([
            'date' => Carbon::yesterday(),
            'name' => 'Past Mixed Event',
        ]);
        Event::factory()->atVenue($this->venueWithBothEvents)->create([
            'date' => Carbon::tomorrow(),
            'name' => 'Future Mixed Event',
        ]);
    });

    describe('withEvents scope', function () {
        test('returns venues that have any events', function () {
            $venuesWithEvents = Venue::query()->withEvents()->get();

            expect($venuesWithEvents->pluck('id'))->toContain($this->venueWithEvents->id);
            expect($venuesWithEvents->pluck('id'))->toContain($this->venueWithPastEvents->id);
            expect($venuesWithEvents->pluck('id'))->toContain($this->venueWithFutureEvents->id);
            expect($venuesWithEvents->pluck('id'))->toContain($this->venueWithBothEvents->id);
            expect($venuesWithEvents->pluck('id'))->not->toContain($this->venueWithoutEvents->id);
        });

        test('withEvents returns correct count', function () {
            $count = Venue::query()->withEvents()->count();

            expect($count)->toBe(4); // All venues except the one without events
        });

        test('withEvents can be chained with other methods', function () {
            $venues = Venue::query()
                ->withEvents()
                ->where('name', 'like', '%Event%')
                ->get();

            expect($venues)->toHaveCount(4);
            expect($venues->pluck('name'))->not->toContain('Empty Venue');
        });
    });

    describe('withPastEvents scope', function () {
        test('returns venues that have hosted past events', function () {
            $venuesWithPastEvents = Venue::query()->withPastEvents()->get();

            expect($venuesWithPastEvents->pluck('id'))->toContain($this->venueWithPastEvents->id);
            expect($venuesWithPastEvents->pluck('id'))->toContain($this->venueWithBothEvents->id);
            expect($venuesWithPastEvents->pluck('id'))->not->toContain($this->venueWithoutEvents->id);
            expect($venuesWithPastEvents->pluck('id'))->not->toContain($this->venueWithFutureEvents->id);
        });

        test('withPastEvents excludes venues with only future events', function () {
            $venuesWithPastEvents = Venue::query()->withPastEvents()->get();

            expect($venuesWithPastEvents->pluck('id'))->not->toContain($this->venueWithFutureEvents->id);
        });

        test('withPastEvents handles events without dates correctly', function () {
            // Create venue with unscheduled event
            $venueWithUnscheduled = Venue::factory()->create();
            Event::factory()->atVenue($venueWithUnscheduled)->create(['date' => null]);

            $venuesWithPastEvents = Venue::query()->withPastEvents()->get();

            expect($venuesWithPastEvents->pluck('id'))->not->toContain($venueWithUnscheduled->id);
        });

        test('withPastEvents returns correct count', function () {
            $count = Venue::query()->withPastEvents()->count();

            expect($count)->toBe(2); // venueWithPastEvents and venueWithBothEvents
        });
    });

    describe('withFutureEvents scope', function () {
        test('returns venues that have future events scheduled', function () {
            $venuesWithFutureEvents = Venue::query()->withFutureEvents()->get();

            expect($venuesWithFutureEvents->pluck('id'))->toContain($this->venueWithFutureEvents->id);
            expect($venuesWithFutureEvents->pluck('id'))->toContain($this->venueWithBothEvents->id);
            expect($venuesWithFutureEvents->pluck('id'))->not->toContain($this->venueWithoutEvents->id);
            expect($venuesWithFutureEvents->pluck('id'))->not->toContain($this->venueWithPastEvents->id);
        });

        test('withFutureEvents excludes venues with only past events', function () {
            $venuesWithFutureEvents = Venue::query()->withFutureEvents()->get();

            expect($venuesWithFutureEvents->pluck('id'))->not->toContain($this->venueWithPastEvents->id);
        });

        test('withFutureEvents handles today\'s events correctly', function () {
            // Create venue with today's event (should be considered future)
            $venueWithTodayEvent = Venue::factory()->create();
            Event::factory()->atVenue($venueWithTodayEvent)->create(['date' => Carbon::today()->addHours(6)]);

            $venuesWithFutureEvents = Venue::query()->withFutureEvents()->get();

            expect($venuesWithFutureEvents->pluck('id'))->toContain($venueWithTodayEvent->id);
        });

        test('withFutureEvents returns correct count', function () {
            $count = Venue::query()->withFutureEvents()->count();

            expect($count)->toBeGreaterThanOrEqual(2); // At least venueWithFutureEvents and venueWithBothEvents
        });
    });

    describe('withoutEvents scope', function () {
        test('returns venues that have no events', function () {
            $venuesWithoutEvents = Venue::query()->withoutEvents()->get();

            expect($venuesWithoutEvents->pluck('id'))->toContain($this->venueWithoutEvents->id);
            expect($venuesWithoutEvents->pluck('id'))->not->toContain($this->venueWithEvents->id);
            expect($venuesWithoutEvents->pluck('id'))->not->toContain($this->venueWithPastEvents->id);
            expect($venuesWithoutEvents->pluck('id'))->not->toContain($this->venueWithFutureEvents->id);
            expect($venuesWithoutEvents->pluck('id'))->not->toContain($this->venueWithBothEvents->id);
        });

        test('withoutEvents returns correct count', function () {
            $count = Venue::query()->withoutEvents()->count();

            expect($count)->toBe(1); // Only venueWithoutEvents
        });

        test('withoutEvents includes venues after events are deleted', function () {
            $venue = Venue::factory()->create();
            $event = Event::factory()->atVenue($venue)->create();

            // Initially venue has events
            expect(Venue::query()->withoutEvents()->get()->pluck('id'))->not->toContain($venue->id);

            // After deleting event, venue should have no events
            $event->delete();

            expect(Venue::query()->withoutEvents()->get()->pluck('id'))->toContain($venue->id);
        });
    });

    describe('scope method chaining', function () {
        test('can chain withEvents and additional filters', function () {
            $venues = Venue::query()
                ->withEvents()
                ->where('name', 'like', '%Mixed%')
                ->get();

            expect($venues)->toHaveCount(1);
            expect($venues->first()->name)->toBe('Mixed Events Venue');
        });

        test('can chain withPastEvents and withFutureEvents', function () {
            $venues = Venue::query()
                ->withPastEvents()
                ->withFutureEvents()
                ->get();

            expect($venues)->toHaveCount(1);
            expect($venues->first()->name)->toBe('Mixed Events Venue');
        });

        test('can chain withoutEvents with ordering', function () {
            Venue::factory()->create(['name' => 'Another Empty Venue']);

            $venues = Venue::query()
                ->withoutEvents()
                ->orderBy('name')
                ->get();

            expect($venues)->toHaveCount(2);
            expect($venues->first()->name)->toBe('Another Empty Venue');
        });

        test('can chain all scopes for complex filtering', function () {
            // Create a venue that meets all criteria
            $complexVenue = Venue::factory()->create(['name' => 'Complex Test Venue']);
            Event::factory()->atVenue($complexVenue)->create(['date' => Carbon::yesterday()]);

            $venues = Venue::query()
                ->withEvents()
                ->withPastEvents()
                ->where('name', 'like', '%Complex%')
                ->get();

            expect($venues)->toHaveCount(1);
            expect($venues->first()->name)->toBe('Complex Test Venue');
        });
    });

    describe('scope performance and optimization', function () {
        test('withEvents uses exists query for performance', function () {
            $query = Venue::query()->withEvents();
            $sql = $query->toSql();

            expect($sql)->toContain('exists');
            expect($sql)->toContain('events');
        });

        test('withPastEvents applies date filter in subquery', function () {
            $query = Venue::query()->withPastEvents();
            $sql = $query->toSql();

            expect($sql)->toContain('exists');
            expect($sql)->toContain('events');
            expect($sql)->toContain('date');
        });

        test('withFutureEvents applies date filter in subquery', function () {
            $query = Venue::query()->withFutureEvents();
            $sql = $query->toSql();

            expect($sql)->toContain('exists');
            expect($sql)->toContain('events');
            expect($sql)->toContain('date');
        });

        test('withoutEvents uses not exists query', function () {
            $query = Venue::query()->withoutEvents();
            $sql = $query->toSql();

            expect($sql)->toContain('not exists');
            expect($sql)->toContain('events');
        });
    });

    describe('scope edge cases', function () {
        test('scopes work with soft deleted venues', function () {
            $deletedVenue = Venue::factory()->create();
            Event::factory()->atVenue($deletedVenue)->create(['date' => Carbon::yesterday()]);
            $deletedVenue->delete();

            // Should not include soft deleted venues by default
            $venues = Venue::query()->withPastEvents()->get();
            expect($venues->pluck('id'))->not->toContain($deletedVenue->id);

            // Should include when specifically querying trashed
            $trashedVenues = Venue::onlyTrashed()->withPastEvents()->get();
            expect($trashedVenues->pluck('id'))->toContain($deletedVenue->id);
        });

        test('scopes work with venues that have mixed event types', function () {
            $venue = Venue::factory()->create();

            // Multiple events of different dates
            Event::factory()->atVenue($venue)->create(['date' => Carbon::yesterday()]);
            Event::factory()->atVenue($venue)->create(['date' => Carbon::tomorrow()]);
            Event::factory()->atVenue($venue)->create(['date' => null]); // Unscheduled

            expect(Venue::query()->withEvents()->get()->pluck('id'))->toContain($venue->id);
            expect(Venue::query()->withPastEvents()->get()->pluck('id'))->toContain($venue->id);
            expect(Venue::query()->withFutureEvents()->get()->pluck('id'))->toContain($venue->id);
            expect(Venue::query()->withoutEvents()->get()->pluck('id'))->not->toContain($venue->id);
        });

        test('scopes handle empty database gracefully', function () {
            // Clear all venues and events
            Event::query()->delete();
            Venue::query()->delete();

            expect(Venue::query()->withEvents()->count())->toBe(0);
            expect(Venue::query()->withPastEvents()->count())->toBe(0);
            expect(Venue::query()->withFutureEvents()->count())->toBe(0);
            expect(Venue::query()->withoutEvents()->count())->toBe(0);
        });

        test('scopes handle date boundary conditions correctly', function () {
            $venue = Venue::factory()->create();

            // Event exactly at midnight today
            Event::factory()->atVenue($venue)->create(['date' => Carbon::today()]);

            // Events exactly at today() boundary are not considered future (must be > today())
            expect(Venue::query()->withFutureEvents()->get()->pluck('id'))->not->toContain($venue->id);
            expect(Venue::query()->withPastEvents()->get()->pluck('id'))->not->toContain($venue->id);
        });
    });

    describe('scope return types and fluency', function () {
        test('all scopes return static for proper chaining', function () {
            $builder = Venue::query();

            expect($builder->withEvents())->toBeInstanceOf(get_class($builder));
            expect($builder->withPastEvents())->toBeInstanceOf(get_class($builder));
            expect($builder->withFutureEvents())->toBeInstanceOf(get_class($builder));
            expect($builder->withoutEvents())->toBeInstanceOf(get_class($builder));
        });

        test('scopes maintain query builder functionality', function () {
            $query = Venue::query()
                ->withEvents()
                ->select('id', 'name')
                ->orderBy('name')
                ->limit(10);

            expect($query)->toBeInstanceOf(Builder::class);
            expect($query->toSql())->toContain('select');
            expect($query->toSql())->toContain('order by');
            expect($query->toSql())->toContain('limit');
        });
    });
});
