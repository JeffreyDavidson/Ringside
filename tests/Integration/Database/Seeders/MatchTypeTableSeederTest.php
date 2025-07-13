<?php

declare(strict_types=1);

use App\Models\Matches\MatchType;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use Illuminate\Support\Facades\Artisan;

/**
 * Integration tests for MatchTypesTableSeeder data seeding and validation.
 *
 * INTEGRATION TEST SCOPE:
 * - Seeder execution and database population
 * - Required match type records validation
 * - Data consistency and count verification
 * - Match type name and slug validation
 *
 * These tests verify that the MatchTypesTableSeeder correctly populates
 * the database with all required match types needed for the
 * wrestling application business logic.
 *
 * @see \Database\Seeders\MatchTypesTableSeeder
 */
describe('MatchTypesTableSeeder Integration Tests', function () {
    describe('seeder execution', function () {
        test('successfully runs without errors', function () {
            // Act & Assert - Should not throw any exceptions
            expect(fn() => Artisan::call('db:seed', ['--class' => 'MatchTypesTableSeeder']))
                ->not->toThrow(Exception::class);
        });

        test('creates exact number of match types', function () {
            // Arrange & Act
            Artisan::call('db:seed', ['--class' => 'MatchTypesTableSeeder']);

            // Assert
            assertDatabaseCount('match_types', 14);
        });
    });

    describe('required match types', function () {
        beforeEach(function () {
            Artisan::call('db:seed', ['--class' => 'MatchTypesTableSeeder']);
        });

        test('creates standard individual match types', function () {
            // Assert
            assertDatabaseHas('match_types', ['name' => 'Singles', 'slug' => 'singles']);
            assertDatabaseHas('match_types', ['name' => 'Triangle', 'slug' => 'triangle']);
            assertDatabaseHas('match_types', ['name' => 'Triple Threat', 'slug' => 'triple-threat']);
            assertDatabaseHas('match_types', ['name' => 'Fatal 4 Way', 'slug' => 'fatal-4-way']);
        });

        test('creates tag team match types', function () {
            // Assert
            assertDatabaseHas('match_types', ['name' => 'Tag Team', 'slug' => 'tag-team']);
            assertDatabaseHas('match_types', ['name' => '6 Man Tag Team', 'slug' => '6-man']);
            assertDatabaseHas('match_types', ['name' => '8 Man Tag Team', 'slug' => '8-man']);
            assertDatabaseHas('match_types', ['name' => '10 Man Tag Team', 'slug' => '10-man']);
            assertDatabaseHas('match_types', ['name' => 'Tornado Tag Team', 'slug' => 'tornado-tag']);
        });

        test('creates handicap match types', function () {
            // Assert
            assertDatabaseHas('match_types', ['name' => 'Two On One Handicap', 'slug' => '2-1-handicap']);
            assertDatabaseHas('match_types', ['name' => 'Three On Two Handicap', 'slug' => '3-2-handicap']);
        });

        test('creates multi-person match types', function () {
            // Assert
            assertDatabaseHas('match_types', ['name' => 'Battle Royal', 'slug' => 'battle-royal']);
            assertDatabaseHas('match_types', ['name' => 'Royal Rumble', 'slug' => 'royal-rumble']);
            assertDatabaseHas('match_types', ['name' => 'Gauntlet', 'slug' => 'gauntlet']);
        });
    });

    describe('data consistency', function () {
        beforeEach(function () {
            Artisan::call('db:seed', ['--class' => 'MatchTypesTableSeeder']);
        });

        test('all match types have unique names', function () {
            // Arrange
            $matchTypes = MatchType::all();

            // Assert
            expect($matchTypes->pluck('name')->unique())->toHaveCount(14);
        });

        test('all match types have unique slugs', function () {
            // Arrange
            $matchTypes = MatchType::all();

            // Assert
            expect($matchTypes->pluck('slug')->unique())->toHaveCount(14);
        });

        test('seeder can be run multiple times safely', function () {
            // Act
            Artisan::call('db:seed', ['--class' => 'MatchTypesTableSeeder']);
            Artisan::call('db:seed', ['--class' => 'MatchTypesTableSeeder']);

            // Assert - Should still have exactly 14 records
            assertDatabaseCount('match_types', 14);
        });
    });
});
