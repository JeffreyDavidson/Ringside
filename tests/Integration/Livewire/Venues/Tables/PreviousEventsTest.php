<?php

declare(strict_types=1);

use App\Builders\Events\EventBuilder;
use App\Livewire\Concerns\ShowTableTrait;
use App\Livewire\Table\Columns\DateColumn;
use App\Livewire\Table\Columns\LinkColumn;
use App\Livewire\Table\DataTableComponent;
use App\Livewire\Venues\Tables\PreviousEvents;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Users\User;

/**
 * Integration tests for PreviousEventsTable component query building and functionality.
 *
 * INTEGRATION TEST SCOPE:
 * - Venue-specific event filtering and query building
 * - Date-based ordering and chronological display
 * - Exception handling for missing venue context
 * - Column configuration for event navigation
 * - ShowTableTrait integration and functionality
 * - Event-venue relationship management
 *
 * These tests verify that the PreviousEventsTable correctly implements
 * venue-specific event history with proper filtering and display.
 *
 * @see PreviousEventsTable
 */
describe('PreviousEventsTable Integration Tests', function () {
    beforeEach(function () {
        $this->admin = User::factory()->administrator()->create();

        // Create test venue and events
        $this->venue = Venue::factory()->create(['name' => 'Test Arena']);
        $this->otherVenue = Venue::factory()->create(['name' => 'Other Arena']);

        // Create events for the test venue at different dates
        $this->recentEvent = Event::factory()->create([
            'name' => 'Recent Wrestling Show',
            'venue_id' => $this->venue->id,
            'date' => now()->subDays(5),
        ]);

        $this->oldEvent = Event::factory()->create([
            'name' => 'Classic Wrestling Event',
            'venue_id' => $this->venue->id,
            'date' => now()->subDays(30),
        ]);

        $this->futureEvent = Event::factory()->create([
            'name' => 'Upcoming Wrestling Show',
            'venue_id' => $this->venue->id,
            'date' => now()->addDays(10),
        ]);

        $this->unscheduledEvent = Event::factory()->unscheduled()->create([
            'name' => 'Unscheduled Wrestling Show',
            'venue_id' => $this->venue->id,
        ]);

        // Create event for other venue (should not appear in results)
        $this->otherVenueEvent = Event::factory()->create([
            'name' => 'Other Venue Event',
            'venue_id' => $this->otherVenue->id,
            'date' => now()->subDays(10),
        ]);
    });

    describe('component initialization and venue filtering', function () {
        test('renders successfully with venue ID set', function () {
            \Pest\Laravel\actingAs($this->admin);

            $component = \Pest\Livewire\livewire(PreviousEvents::class, ['venueId' => $this->venue->id]);

            $component->assertOk()
                ->assertSee('Recent Wrestling Show')
                ->assertSee('Classic Wrestling Event')
                ->assertSee('Upcoming Wrestling Show');
        });

        test('throws exception when venue ID is not provided', function () {
            expect(fn () => (new PreviousEvents())->builder())
                ->toThrow(LogicException::class, 'A venue was not provided.');
        });

        test('filters events by specific venue only', function () {
            \Pest\Laravel\actingAs($this->admin);

            $component = \Pest\Livewire\livewire(PreviousEvents::class, ['venueId' => $this->venue->id]);

            $component->assertOk()
                ->assertSee('Recent Wrestling Show')
                ->assertSee('Classic Wrestling Event')
                ->assertDontSee('Other Venue Event');
        });
    });

    describe('query builder configuration and optimization', function () {
        test('builder method returns correctly configured EventBuilder', function () {
            $table = new PreviousEvents();
            $table->venueId = $this->venue->id;

            $builder = $table->builder();

            expect($builder)->toBeInstanceOf(EventBuilder::class);

            $sql = $builder->toSql();
            expect($sql)->toContain('where "venue_id" = ?');

            expect($builder->getBindings())->toContain($this->venue->id);
        });

        test('builder applies descending date ordering for chronological display', function () {
            $table = new PreviousEvents();
            $table->venueId = $this->venue->id;

            expect($table->builder()->pluck('id')->all())->toBe([
                $this->futureEvent->id,
                $this->recentEvent->id,
                $this->oldEvent->id,
                $this->unscheduledEvent->id,
            ]);
        });

        test('builder efficiently filters by venue ID with single query', function () {
            $table = new PreviousEvents();
            $table->venueId = $this->venue->id;

            $events = $table->builder()->get();

            // All events should belong to the specified venue
            foreach ($events as $event) {
                expect($event->venue_id)->toBe($this->venue->id);
            }
        });
    });

    describe('date-based ordering and chronological display', function () {
        test('displays events in descending chronological order', function () {
            \Pest\Laravel\actingAs($this->admin);

            $component = \Pest\Livewire\livewire(PreviousEvents::class, ['venueId' => $this->venue->id]);

            $component->assertOk();

            // Verify events are in correct order (newest first)
            $table = new PreviousEvents();
            $table->venueId = $this->venue->id;
            expect($table->builder()->pluck('id')->all())->toBe([
                $this->futureEvent->id,
                $this->recentEvent->id,
                $this->oldEvent->id,
                $this->unscheduledEvent->id,
            ]);
        });

        test('includes both past and future events in chronological order', function () {
            \Pest\Laravel\actingAs($this->admin);

            $component = \Pest\Livewire\livewire(PreviousEvents::class, ['venueId' => $this->venue->id]);

            $component->assertOk()
                ->assertSee('Upcoming Wrestling Show')
                ->assertSee('Recent Wrestling Show')
                ->assertSee('Classic Wrestling Event');
        });
    });

    describe('column configuration and data presentation', function () {
        test('columns include LinkColumn for event navigation', function () {
            $table = new PreviousEvents();
            $columns = $table->columns();

            expect($columns)->toHaveCount(2); // name and date columns

            $linkColumn = collect($columns)->first(function ($column) {
                return $column instanceof LinkColumn;
            });

            expect($linkColumn)->not()->toBeNull();
        });

        test('columns include DateColumn for proper date formatting', function () {
            $table = new PreviousEvents();
            $columns = $table->columns();

            $dateColumn = collect($columns)->first(function ($column) {
                return $column instanceof DateColumn;
            });

            expect($dateColumn)->not()->toBeNull();
        });

        test('date column uses consistent Y-m-d format', function () {
            \Pest\Laravel\actingAs($this->admin);

            $component = \Pest\Livewire\livewire(PreviousEvents::class, ['venueId' => $this->venue->id]);

            $component->assertOk();

            // Events should display dates in Y-m-d format
            $expectedDate = requiredDate($this->recentEvent->date)->format('Y-m-d');
            $component->assertSee($expectedDate);
        });
    });

    describe('event navigation and link functionality', function () {
        test('link column provides navigation to event details', function () {
            $table = new PreviousEvents();
            $columns = $table->columns();

            $linkColumn = collect($columns)->first(function ($column) {
                return $column instanceof LinkColumn;
            });

            expect($linkColumn)->not()->toBeNull();
        });

        test('displays clickable event names for navigation', function () {
            \Pest\Laravel\actingAs($this->admin);

            $component = \Pest\Livewire\livewire(PreviousEvents::class, ['venueId' => $this->venue->id]);

            $component->assertOk()
                ->assertSee('Recent Wrestling Show')
                ->assertSee('Classic Wrestling Event');
        });
    });

    describe('ShowTableTrait integration', function () {
        test('uses ShowTableTrait for enhanced table functionality', function () {
            expect(class_uses(PreviousEvents::class))->toContain(ShowTableTrait::class);
        });

        test('has required properties from ShowTableTrait', function () {
            $table = new PreviousEvents();
            $reflection = new ReflectionClass($table);

            $databaseTableNameProperty = $reflection->getProperty('databaseTableName');
            $databaseTableNameProperty->setAccessible(true);
            expect($databaseTableNameProperty->getValue($table))->toBe('events');

            $resourceNameProperty = $reflection->getProperty('resourceName');
            $resourceNameProperty->setAccessible(true);
            expect($resourceNameProperty->getValue($table))->toBe('events');
        });
    });

    describe('error handling and edge cases', function () {
        test('handles venue with no events gracefully', function () {
            $emptyVenue = Venue::factory()->create(['name' => 'Empty Venue']);

            \Pest\Laravel\actingAs($this->admin);

            $component = \Pest\Livewire\livewire(PreviousEvents::class, ['venueId' => $emptyVenue->id]);

            $component->assertOk();
        });

        test('throws descriptive exception for missing venue ID', function () {
            expect(function () {
                $table = new PreviousEvents();
                $table->builder();
            })->toThrow(LogicException::class, 'A venue was not provided.');
        });

        test('handles invalid venue ID gracefully', function () {
            \Pest\Laravel\actingAs($this->admin);

            $component = \Pest\Livewire\livewire(PreviousEvents::class, ['venueId' => 999999]);

            $component->assertOk();
        });
    });

    describe('venue context and relationship integrity', function () {
        test('maintains venue context throughout component lifecycle', function () {
            $table = new PreviousEvents();
            $table->venueId = $this->venue->id;

            $events = $table->builder()->get();

            // All returned events must belong to the specified venue
            foreach ($events as $event) {
                expect($event->venue_id)->toBe($this->venue->id);
            }
        });

        test('preserves event-venue relationships in display', function () {
            \Pest\Laravel\actingAs($this->admin);

            $component = \Pest\Livewire\livewire(PreviousEvents::class, ['venueId' => $this->venue->id]);

            $component->assertOk();

            // Verify all displayed events are related to the venue
            $table = new PreviousEvents();
            $table->venueId = $this->venue->id;
            $events = $table->builder()->get();

            expect($events->count())->toBe(4);
            expect($events->pluck('venue_id')->unique()->firstOrFail())->toBe($this->venue->id);
        });
    });

    describe('performance and query optimization', function () {
        test('performs efficient single-query filtering by venue', function () {
            $table = new PreviousEvents();
            $table->venueId = $this->venue->id;

            $builder = $table->builder();
            $sql = $builder->toSql();

            // Should have only one WHERE clause for venue filtering
            expect(mb_substr_count($sql, 'where'))->toBe(1);
            expect($sql)->toContain('where "venue_id" = ?');
        });

        test('handles large event histories efficiently', function () {
            // Create many events for the venue
            Event::factory()->count(50)->create([
                'venue_id' => $this->venue->id,
                'date' => now()->subDays(random_int(1, 365)),
            ]);

            \Pest\Laravel\actingAs($this->admin);

            $component = \Pest\Livewire\livewire(PreviousEvents::class, ['venueId' => $this->venue->id]);

            $component->assertOk();

            expect(Event::where('venue_id', $this->venue->id)->count())->toBeGreaterThan(50);
        });
    });

    describe('component inheritance and structure', function () {
        test('extends DataTableComponent correctly', function () {
            $table = new PreviousEvents();
            expect($table)->toBeInstanceOf(DataTableComponent::class);
        });

        test('has required methods for table functionality', function () {
            $table = new PreviousEvents();
            $requiredMethods = ['builder', 'configure', 'columns'];

            foreach ($requiredMethods as $method) {
                expect(method_exists($table, $method))->toBeTrue("Method {$method} should exist");
            }
        });
    });
});
