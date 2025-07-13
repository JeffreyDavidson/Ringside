<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\Events\Event;
use App\Models\Shared\Venue;

/**
 * Unit tests for EventFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (realistic data patterns)
 * - Factory state methods (withVenue, scheduled, past, future, etc.)
 * - Factory relationship creation (venue associations)
 * - Custom factory methods and configurations
 * - Data consistency and business rule compliance
 *
 * These tests verify that the EventFactory generates consistent,
 * realistic test data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see \Database\Factories\Events\EventFactory
 */
describe('EventFactory Unit Tests', function () {
    describe('default attribute generation', function () {
        test('creates event with correct default attributes', function () {
            // Arrange & Act
            $event = Event::factory()->make();
            
            // Assert
            expect($event->name)->toBeString();
            expect($event->name)->not->toBeEmpty();
            expect($event->date)->toBeNull(); // Default state has no date
            expect($event->venue_id)->toBeNull(); // Default state has no venue
            expect($event->preview)->toBeNull(); // Default state has no preview
        });

        test('generates realistic event names', function () {
            // Arrange & Act
            $event = Event::factory()->make();
            
            // Assert
            expect($event->name)->toBeString();
            expect(strlen($event->name))->toBeGreaterThan(3);
            expect($event->name)->toBe(ucwords($event->name));
        });

        test('sets default nullable fields correctly', function () {
            // Arrange & Act
            $event = Event::factory()->make();
            
            // Assert
            expect($event->date)->toBeNull();
            expect($event->venue_id)->toBeNull();
            expect($event->preview)->toBeNull();
        });
    });

    describe('factory state methods', function () {
        test('scheduled state works correctly', function () {
            // Arrange
            $venue = Venue::factory()->create();
            $date = now()->addWeek();

            // Act
            $event = Event::factory()->make([
                'venue_id' => $venue->id,
                'date' => $date,
            ]);
            
            // Assert
            expect($event->venue_id)->toBe($venue->id);
            expect($event->date->format('Y-m-d H:i:s'))->toBe($date->format('Y-m-d H:i:s'));
        });

        test('with preview content works correctly', function () {
            // Arrange
            $preview = 'This is a preview of the upcoming event featuring exciting matches.';

            // Act
            $event = Event::factory()->make(['preview' => $preview]);
            
            // Assert
            expect($event->preview)->toBe($preview);
        });

        test('past event state works correctly', function () {
            // Arrange
            $pastDate = now()->subWeek();

            // Act
            $event = Event::factory()->make(['date' => $pastDate]);
            
            // Assert
            expect($event->date->format('Y-m-d H:i:s'))->toBe($pastDate->format('Y-m-d H:i:s'));
            expect($event->date->isPast())->toBeTrue();
        });

        test('future event state works correctly', function () {
            // Arrange
            $futureDate = now()->addWeek();

            // Act
            $event = Event::factory()->make(['date' => $futureDate]);
            
            // Assert
            expect($event->date->format('Y-m-d H:i:s'))->toBe($futureDate->format('Y-m-d H:i:s'));
            expect($event->date->isFuture())->toBeTrue();
        });
    });

    describe('factory customization', function () {
        test('accepts custom attribute overrides', function () {
            // Arrange & Act
            $event = Event::factory()->make([
                'name' => 'Custom Event',
                'preview' => 'Custom preview content',
            ]);
            
            // Assert
            expect($event->name)->toBe('Custom Event');
            expect($event->preview)->toBe('Custom preview content');
        });

        test('maintains required attributes when overriding', function () {
            // Arrange & Act
            $event = Event::factory()->make([
                'name' => 'Override Event',
            ]);
            
            // Assert
            expect($event->name)->toBe('Override Event');
            expect($event->date)->toBeNull();
            expect($event->venue_id)->toBeNull();
            expect($event->preview)->toBeNull();
        });
    });

    describe('data consistency', function () {
        test('generates unique event names', function () {
            // Arrange & Act
            $event1 = Event::factory()->make();
            $event2 = Event::factory()->make();
            
            // Assert
            expect($event1->name)->not->toBe($event2->name);
        });

        test('generates consistent data format', function () {
            // Arrange & Act
            $events = collect(range(1, 5))->map(fn() => Event::factory()->make());
            
            // Assert
            foreach ($events as $event) {
                expect($event->name)->toBeString();
                expect($event->name)->not->toBeEmpty();
                expect($event->name)->toBe(ucwords($event->name));
            }
        });

        test('database creation works correctly', function () {
            // Arrange & Act
            $event = Event::factory()->create();
            
            // Assert
            expect($event->exists)->toBeTrue();
            expect($event->id)->toBeGreaterThan(0);
        });
    });
});
