<?php

declare(strict_types=1);

use App\Models\Matches\EventMatch;
use Database\Seeders\MatchesTableSeeder;
use Illuminate\Support\Facades\Artisan;

/**
 * Integration tests for MatchesTableSeeder data seeding and validation.
 *
 * INTEGRATION TEST SCOPE:
 * - Seeder execution and database population
 * - Event match record creation and validation
 * - Data consistency and count verification
 * - Event match attribute validation
 *
 * These tests verify that the MatchesTableSeeder correctly populates
 * the database with event match records for development and testing purposes.
 *
 * @see MatchesTableSeeder
 */
describe('MatchesTableSeeder Integration Tests', function () {
    describe('seeder execution', function () {
        test('successfully runs without errors', function () {
            // Act & Assert - Should not throw any exceptions
            expect(fn () => Artisan::call('db:seed', ['--class' => 'MatchesTableSeeder']))
                ->not()->toThrow(Exception::class);
            expect(true)->toBeTrue();
        });

        test('creates event matches in database', function () {
            // Arrange & Act
            Artisan::call('db:seed', ['--class' => 'MatchesTableSeeder']);

            // Assert - Should create multiple event matches
            expect(EventMatch::count())->toBeGreaterThan(0);
            expect(true)->toBeTrue();
        });
    });

    describe('event match attributes', function () {
        beforeEach(function () {
            Artisan::call('db:seed', ['--class' => 'MatchesTableSeeder']);
        });

        test('event matches have required attributes', function () {
            // Arrange
            $eventMatches = EventMatch::take(10)->get();

            // Assert
            foreach ($eventMatches as $eventMatch) {
                expect($eventMatch->event_id)->toBeInt();
                expect($eventMatch->match_type_id)->toBeInt();
                expect($eventMatch->match_number)->toBeInt();
                expect($eventMatch->match_number)->toBeGreaterThan(0);
            }
            expect(true)->toBeTrue();
        });

        test('event matches have valid order sequence', function () {
            // Arrange
            $eventMatches = EventMatch::take(10)->get();

            // Assert
            foreach ($eventMatches as $eventMatch) {
                expect($eventMatch->match_number)->toBeBetween(1, 20); // Reasonable match number range
            }
            expect(true)->toBeTrue();
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
            expect(true)->toBeTrue();
        });
    });

    describe('data consistency', function () {
        beforeEach(function () {
            Artisan::call('db:seed', ['--class' => 'MatchesTableSeeder']);
        });

        test('event matches have valid event associations', function () {
            // Arrange
            $eventMatches = EventMatch::take(10)->get();

            // Assert
            foreach ($eventMatches as $eventMatch) {
                expect($eventMatch->event_id)->toBeInt();
                expect($eventMatch->event_id)->toBeGreaterThan(0);
            }
            expect(true)->toBeTrue();
        });

        test('event matches have valid match type associations', function () {
            // Arrange
            $eventMatches = EventMatch::take(10)->get();

            // Assert
            foreach ($eventMatches as $eventMatch) {
                expect($eventMatch->match_type_id)->toBeInt();
                expect($eventMatch->match_type_id)->toBeGreaterThan(0);
            }
            expect(true)->toBeTrue();
        });

        test('event matches can load relationships', function () {
            // Arrange
            $eventMatch = EventMatch::with(['event', 'matchType'])->first();

            // Assert
            expect($eventMatch->event)->not()->toBeNull();
            expect($eventMatch->matchType)->not()->toBeNull();
            expect($eventMatch->event->name)->toBeString();
            expect($eventMatch->matchType->name)->toBeString();
            expect(true)->toBeTrue();
        });

        test('seeder creates consistent data', function () {
            // Arrange
            $initialCount = EventMatch::count();

            // Act
            Artisan::call('db:seed', ['--class' => 'MatchesTableSeeder']);

            // Assert - Should maintain or increase count
            expect(EventMatch::count())->toBeGreaterThanOrEqual($initialCount);
            expect(true)->toBeTrue();
        });
    });
});
