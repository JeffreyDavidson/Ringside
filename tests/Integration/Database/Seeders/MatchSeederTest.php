<?php

declare(strict_types=1);

use App\Models\Matches\EventMatch;
use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\assertDatabaseCount;

/**
 * Integration tests for EventMatchSeeder data seeding and validation.
 *
 * INTEGRATION TEST SCOPE:
 * - Seeder execution and database population
 * - Event match record creation and validation
 * - Data consistency and count verification
 * - Event match attribute validation
 *
 * These tests verify that the EventMatchSeeder correctly populates
 * the database with event match records for development and testing purposes.
 *
 * @see \Database\Seeders\EventMatchSeeder
 */
describe('EventMatchSeeder Integration Tests', function () {
    describe('seeder execution', function () {
        test('successfully runs without errors', function () {
            // Act & Assert - Should not throw any exceptions
            expect(fn() => Artisan::call('db:seed', ['--class' => 'EventMatchSeeder']))
                ->not->toThrow(Exception::class);
        });

        test('creates event matches in database', function () {
            // Arrange & Act
            Artisan::call('db:seed', ['--class' => 'EventMatchSeeder']);

            // Assert - Should create multiple event matches
            expect(EventMatch::count())->toBeGreaterThan(0);
        });
    });

    describe('event match attributes', function () {
        beforeEach(function () {
            Artisan::call('db:seed', ['--class' => 'EventMatchSeeder']);
        });

        test('event matches have required attributes', function () {
            // Arrange
            $eventMatches = EventMatch::take(10)->get();

            // Assert
            foreach ($eventMatches as $eventMatch) {
                expect($eventMatch->event_id)->toBeInt();
                expect($eventMatch->match_type_id)->toBeInt();
                expect($eventMatch->order)->toBeInt();
                expect($eventMatch->order)->toBeGreaterThan(0);
            }
        });

        test('event matches have valid order sequence', function () {
            // Arrange
            $eventMatches = EventMatch::take(10)->get();

            // Assert
            foreach ($eventMatches as $eventMatch) {
                expect($eventMatch->order)->toBeBetween(1, 20); // Reasonable match order range
            }
        });

        test('event matches can have preview text', function () {
            // Arrange
            $eventMatches = EventMatch::whereNotNull('preview')->take(5)->get();

            // Assert
            foreach ($eventMatches as $eventMatch) {
                if ($eventMatch->preview) {
                    expect($eventMatch->preview)->toBeString();
                    expect($eventMatch->preview)->not->toBeEmpty();
                }
            }
        });
    });

    describe('data consistency', function () {
        beforeEach(function () {
            Artisan::call('db:seed', ['--class' => 'EventMatchSeeder']);
        });

        test('event matches have valid event associations', function () {
            // Arrange
            $eventMatches = EventMatch::take(10)->get();

            // Assert
            foreach ($eventMatches as $eventMatch) {
                expect($eventMatch->event_id)->toBeInt();
                expect($eventMatch->event_id)->toBeGreaterThan(0);
            }
        });

        test('event matches have valid match type associations', function () {
            // Arrange
            $eventMatches = EventMatch::take(10)->get();

            // Assert
            foreach ($eventMatches as $eventMatch) {
                expect($eventMatch->match_type_id)->toBeInt();
                expect($eventMatch->match_type_id)->toBeGreaterThan(0);
            }
        });

        test('event matches can load relationships', function () {
            // Arrange
            $eventMatch = EventMatch::with(['event', 'matchType'])->first();

            // Assert
            expect($eventMatch->event)->not->toBeNull();
            expect($eventMatch->matchType)->not->toBeNull();
            expect($eventMatch->event->name)->toBeString();
            expect($eventMatch->matchType->name)->toBeString();
        });

        test('seeder creates consistent data', function () {
            // Arrange
            $initialCount = EventMatch::count();

            // Act
            Artisan::call('db:seed', ['--class' => 'EventMatchSeeder']);

            // Assert - Should maintain or increase count
            expect(EventMatch::count())->toBeGreaterThanOrEqual($initialCount);
        });
    });
});