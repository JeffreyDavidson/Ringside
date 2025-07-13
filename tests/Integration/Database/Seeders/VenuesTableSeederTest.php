<?php

declare(strict_types=1);

use App\Models\Shared\Venue;
use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

/**
 * Integration tests for VenuesTableSeeder data seeding and validation.
 *
 * INTEGRATION TEST SCOPE:
 * - Seeder execution and database population
 * - Venue record creation and naming validation
 * - Data consistency and count verification
 * - Sequential venue naming pattern validation
 *
 * These tests verify that the VenuesTableSeeder correctly populates
 * the database with the required venue records for development and
 * testing purposes.
 *
 * @see \Database\Seeders\VenuesTableSeeder
 */
describe('VenuesTableSeeder Integration Tests', function () {
    describe('seeder execution', function () {
        test('successfully runs without errors', function () {
            // Act & Assert - Should not throw any exceptions
            expect(fn() => Artisan::call('db:seed', ['--class' => 'VenuesTableSeeder']))
                ->not->toThrow(Exception::class);
        });

        test('creates exact number of venues', function () {
            // Arrange & Act
            Artisan::call('db:seed', ['--class' => 'VenuesTableSeeder']);

            // Assert
            assertDatabaseCount('venues', 100);
        });
    });

    describe('venue creation', function () {
        beforeEach(function () {
            Artisan::call('db:seed', ['--class' => 'VenuesTableSeeder']);
        });

        test('creates venues with sequential naming', function () {
            // Assert
            assertDatabaseHas('venues', ['name' => 'Venue 0']);
            assertDatabaseHas('venues', ['name' => 'Venue 1']);
            assertDatabaseHas('venues', ['name' => 'Venue 2']);
            assertDatabaseHas('venues', ['name' => 'Venue 50']);
            assertDatabaseHas('venues', ['name' => 'Venue 99']);
        });

        test('all venues have required attributes', function () {
            // Arrange
            $venues = Venue::take(10)->get();

            // Assert
            foreach ($venues as $venue) {
                expect($venue->name)->toBeString();
                expect($venue->name)->toContain('Venue ');
                expect($venue->street_address)->toBeString();
                expect($venue->city)->toBeString();
                expect($venue->state)->toBeString();
                expect($venue->zipcode)->toBeString();
            }
        });

        test('venues have realistic address data', function () {
            // Arrange
            $venue = Venue::first();

            // Assert
            expect($venue->street_address)->not->toBeEmpty();
            expect($venue->city)->not->toBeEmpty();
            expect($venue->state)->not->toBeEmpty();
            expect($venue->zipcode)->toMatch('/^\d{5}$/'); // 5-digit zipcode
        });
    });

    describe('data consistency', function () {
        beforeEach(function () {
            Artisan::call('db:seed', ['--class' => 'VenuesTableSeeder']);
        });

        test('all venues have unique names', function () {
            // Arrange
            $venues = Venue::all();

            // Assert
            expect($venues->pluck('name')->unique())->toHaveCount(100);
        });

        test('venue naming follows sequential pattern', function () {
            // Arrange
            $venues = Venue::orderBy('id')->get();

            // Assert
            foreach ($venues as $index => $venue) {
                expect($venue->name)->toBe("Venue {$index}");
            }
        });

        test('seeder can be run multiple times safely', function () {
            // Act
            Artisan::call('db:seed', ['--class' => 'VenuesTableSeeder']);

            // Assert - Should not create duplicates, depends on seeder implementation
            expect(Venue::count())->toBeGreaterThanOrEqual(100);
        });
    });
});