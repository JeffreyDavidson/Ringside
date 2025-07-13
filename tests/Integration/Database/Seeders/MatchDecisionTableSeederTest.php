<?php

declare(strict_types=1);

use function Pest\Laravel\assertDatabaseCount;

use function Pest\Laravel\assertDatabaseHas;
use Illuminate\Support\Facades\Artisan;

/**
 * Integration tests for MatchDecisionsTableSeeder data seeding and validation.
 *
 * INTEGRATION TEST SCOPE:
 * - Seeder execution and database population
 * - Required match decision records validation
 * - Data consistency and count verification
 * - Match decision name and slug validation
 *
 * These tests verify that the MatchDecisionsTableSeeder correctly populates
 * the database with all required match decision types needed for the
 * wrestling application business logic.
 *
 * @see \Database\Seeders\MatchDecisionsTableSeeder
 */
describe('MatchDecisionsTableSeeder Integration Tests', function () {
    describe('seeder execution', function () {
        test('successfully runs without errors', function () {
            // Act & Assert - Should not throw any exceptions
            expect(fn() => Artisan::call('db:seed', ['--class' => 'MatchDecisionsTableSeeder']))
                ->not->toThrow(Exception::class);
        });

        test('creates exact number of match decisions', function () {
            // Arrange & Act
            Artisan::call('db:seed', ['--class' => 'MatchDecisionsTableSeeder']);

            // Assert
            assertDatabaseCount('match_decisions', 10);
        });
    });

    describe('required match decisions', function () {
        beforeEach(function () {
            Artisan::call('db:seed', ['--class' => 'MatchDecisionsTableSeeder']);
        });

        test('creates standard victory conditions', function () {
            // Assert
            assertDatabaseHas('match_decisions', ['name' => 'Pinfall', 'slug' => 'pinfall']);
            assertDatabaseHas('match_decisions', ['name' => 'Submission', 'slug' => 'submission']);
            assertDatabaseHas('match_decisions', ['name' => 'Knockout', 'slug' => 'knockout']);
        });

        test('creates disqualification and forfeit conditions', function () {
            // Assert
            assertDatabaseHas('match_decisions', ['name' => 'Disqualification', 'slug' => 'dq']);
            assertDatabaseHas('match_decisions', ['name' => 'Countout', 'slug' => 'countout']);
            assertDatabaseHas('match_decisions', ['name' => 'Forfeit', 'slug' => 'forfeit']);
        });

        test('creates special match conditions', function () {
            // Assert
            assertDatabaseHas('match_decisions', ['name' => 'Stipulation', 'slug' => 'stipulation']);
            assertDatabaseHas('match_decisions', ['name' => 'Time Limit Draw', 'slug' => 'draw']);
            assertDatabaseHas('match_decisions', ['name' => 'No Decision', 'slug' => 'nodecision']);
            assertDatabaseHas('match_decisions', ['name' => 'Reverse Decision', 'slug' => 'rev-decision']);
        });
    });

    describe('data consistency', function () {
        beforeEach(function () {
            Artisan::call('db:seed', ['--class' => 'MatchDecisionsTableSeeder']);
        });

        test('all match decisions have unique names', function () {
            // Arrange
            $matchDecisions = \App\Models\Matches\MatchDecision::all();

            // Assert
            expect($matchDecisions->pluck('name')->unique())->toHaveCount(10);
        });

        test('all match decisions have unique slugs', function () {
            // Arrange
            $matchDecisions = \App\Models\Matches\MatchDecision::all();

            // Assert
            expect($matchDecisions->pluck('slug')->unique())->toHaveCount(10);
        });

        test('seeder can be run multiple times safely', function () {
            // Act
            Artisan::call('db:seed', ['--class' => 'MatchDecisionsTableSeeder']);
            Artisan::call('db:seed', ['--class' => 'MatchDecisionsTableSeeder']);

            // Assert - Should still have exactly 10 records
            assertDatabaseCount('match_decisions', 10);
        });
    });
});
