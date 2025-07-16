<?php

declare(strict_types=1);

use App\Models\Events\Event;
use Illuminate\Support\Facades\Artisan;

/**
 * Integration tests for EventsTableSeeder data seeding and validation.
 *
 * INTEGRATION TEST SCOPE:
 * - Seeder execution and database population
 * - Event record creation and validation
 * - Data consistency and count verification
 * - Event attribute validation
 *
 * These tests verify that the EventsTableSeeder correctly populates
 * the database with event records for development and testing purposes.
 *
 * @see Database\Seeders\EventsTableSeeder
 */
describe('EventsTableSeeder Integration Tests', function () {
    describe('seeder execution', function () {
        test('successfully runs without errors', function () {
            // Act & Assert - Should not throw any exceptions
            expect(fn () => Artisan::call('db:seed', ['--class' => 'EventsTableSeeder']))
                ->not->toThrow(Exception::class);
        });

        test('creates events in database', function () {
            // Arrange & Act
            Artisan::call('db:seed', ['--class' => 'EventsTableSeeder']);

            // Assert - Should create multiple events
            expect(Event::count())->toBeGreaterThan(0);
        });
    });

    describe('event attributes', function () {
        beforeEach(function () {
            Artisan::call('db:seed', ['--class' => 'EventsTableSeeder']);
        });

        test('events have required attributes', function () {
            // Arrange
            $events = Event::take(10)->get();

            // Assert
            foreach ($events as $event) {
                expect($event->name)->toBeString();
                expect($event->name)->not->toBeEmpty();
                expect($event->date)->toBeInstanceOf(Illuminate\Support\Carbon::class);
                // venue_id can be null for future events without assigned venues
                if ($event->venue_id !== null) {
                    expect($event->venue_id)->toBeInt();
                }
            }
        });

        test('events have realistic names', function () {
            // Arrange
            $events = Event::take(5)->get();

            // Assert
            foreach ($events as $event) {
                expect(mb_strlen($event->name))->toBeGreaterThanOrEqual(3);
                expect($event->name)->not->toContain('Test');
            }
        });

        test('events have valid dates', function () {
            // Arrange
            $events = Event::take(10)->get();

            // Assert
            foreach ($events as $event) {
                expect($event->date)->toBeInstanceOf(Illuminate\Support\Carbon::class);
                // Events should be in the past or future (not null)
                expect($event->date)->not->toBeNull();
            }
        });
    });

    describe('data consistency', function () {
        beforeEach(function () {
            Artisan::call('db:seed', ['--class' => 'EventsTableSeeder']);
        });

        test('events have valid venue associations', function () {
            // Arrange
            $events = Event::take(10)->get();

            // Assert
            foreach ($events as $event) {
                // venue_id can be null for future events without assigned venues
                if ($event->venue_id !== null) {
                    expect($event->venue_id)->toBeInt();
                    expect($event->venue_id)->toBeGreaterThan(0);
                }
            }
        });

        test('events can load venue relationships', function () {
            // Arrange - Find an event that has a venue assigned
            $event = Event::with('venue')->whereNotNull('venue_id')->first();

            // Assert
            if ($event) {
                expect($event->venue)->not->toBeNull();
                expect($event->venue->name)->toBeString();
            } else {
                // If no events have venues, that's also valid (all future events)
                expect(Event::whereNotNull('venue_id')->count())->toBe(0);
            }
        });

        test('seeder creates consistent data', function () {
            // Arrange
            $initialCount = Event::count();

            // Act
            Artisan::call('db:seed', ['--class' => 'EventsTableSeeder']);

            // Assert - Should maintain or increase count
            expect(Event::count())->toBeGreaterThanOrEqual($initialCount);
        });
    });
});
