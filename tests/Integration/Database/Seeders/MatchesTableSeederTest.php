<?php

declare(strict_types=1);

use App\Enums\MatchType;
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
        });

        test('creates event matches in database', function () {
            // Arrange & Act
            Artisan::call('db:seed', ['--class' => 'MatchesTableSeeder']);

            // Assert - Should create multiple event matches
            expect(EventMatch::count())->toBeGreaterThan(0);
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
            expect($eventMatches)->not->toBeEmpty();

            foreach ($eventMatches as $eventMatch) {
                expect($eventMatch->event_id)->toBeInt();
                expect($eventMatch->match_type)->toBeInstanceOf(MatchType::class);
                expect($eventMatch->match_number)->toBeInt();
                expect($eventMatch->match_number)->toBeGreaterThan(0);
            }
        });

        test('event matches have valid order sequence', function () {
            // Arrange
            $eventMatches = EventMatch::take(10)->get();

            // Assert
            expect($eventMatches)->not->toBeEmpty();

            foreach ($eventMatches as $eventMatch) {
                expect($eventMatch->match_number)->toBeBetween(1, 20); // Reasonable match number range
            }
        });

        test('event matches have valid preview text', function () {
            // Arrange
            $eventMatches = EventMatch::take(10)->get();

            // Assert
            expect($eventMatches)->not->toBeEmpty();

            foreach ($eventMatches as $eventMatch) {
                if ($eventMatch->preview === null) {
                    expect($eventMatch->preview)->toBeNull();

                    continue;
                }

                expect($eventMatch->preview)->not->toBeEmpty();
            }
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
            expect($eventMatches)->not->toBeEmpty();

            foreach ($eventMatches as $eventMatch) {
                expect($eventMatch->event_id)->toBeInt();
                expect($eventMatch->event_id)->toBeGreaterThan(0);
            }
        });

        test('event matches have valid match type associations', function () {
            // Arrange
            $eventMatches = EventMatch::take(10)->get();

            // Assert
            expect($eventMatches)->not->toBeEmpty();

            foreach ($eventMatches as $eventMatch) {
                expect($eventMatch->match_type)->toBeInstanceOf(MatchType::class);
            }
        });

        test('event matches can load relationships', function () {
            // Arrange
            $eventMatch = EventMatch::with(['event'])->firstOrFail();

            // Assert
            expect($eventMatch->event()->firstOrFail()->name)->toBeString();
            expect($eventMatch->match_type)->toBeInstanceOf(MatchType::class);
        });

        test('seeder creates consistent data', function () {
            // Arrange
            $initialCount = EventMatch::count();

            // Act
            Artisan::call('db:seed', ['--class' => 'MatchesTableSeeder']);

            // Assert - Should maintain or increase count
            expect(EventMatch::count())->toBeGreaterThanOrEqual($initialCount);
        });
    });
});
