<?php

declare(strict_types=1);

use App\Models\Stables\Stable;
use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\assertDatabaseCount;

/**
 * Integration tests for StablesTableSeeder data seeding and validation.
 *
 * INTEGRATION TEST SCOPE:
 * - Seeder execution and database population
 * - Stable record creation and validation
 * - Data consistency and count verification
 * - Stable attribute validation
 *
 * These tests verify that the StablesTableSeeder correctly populates
 * the database with stable records for development and testing purposes.
 *
 * @see \Database\Seeders\StablesTableSeeder
 */
describe('StablesTableSeeder Integration Tests', function () {
    describe('seeder execution', function () {
        test('successfully runs without errors', function () {
            // Act & Assert - Should not throw any exceptions
            expect(fn() => Artisan::call('db:seed', ['--class' => 'StablesTableSeeder']))
                ->not->toThrow(Exception::class);
        });

        test('creates stables in database', function () {
            // Arrange & Act
            Artisan::call('db:seed', ['--class' => 'StablesTableSeeder']);

            // Assert - Should create multiple stables
            expect(Stable::count())->toBeGreaterThan(0);
        });
    });

    describe('stable attributes', function () {
        beforeEach(function () {
            Artisan::call('db:seed', ['--class' => 'StablesTableSeeder']);
        });

        test('stables have required attributes', function () {
            // Arrange
            $stables = Stable::take(10)->get();

            // Assert
            foreach ($stables as $stable) {
                expect($stable->name)->toBeString();
                expect($stable->name)->not->toBeEmpty();
                expect($stable->status)->toBeInstanceOf(\App\Enums\Stables\StableStatus::class);
            }
        });

        test('stables have realistic names', function () {
            // Arrange
            $stables = Stable::take(5)->get();

            // Assert
            foreach ($stables as $stable) {
                expect(strlen($stable->name))->toBeGreaterThan(5);
                expect($stable->name)->not->toContain('Test');
            }
        });
    });

    describe('data consistency', function () {
        beforeEach(function () {
            Artisan::call('db:seed', ['--class' => 'StablesTableSeeder']);
        });

        test('all stables have unique names', function () {
            // Arrange
            $stables = Stable::all();

            // Assert
            expect($stables->pluck('name')->unique())->toHaveCount($stables->count());
        });

        test('stables have valid status', function () {
            // Arrange
            $stables = Stable::take(10)->get();

            // Assert
            foreach ($stables as $stable) {
                expect($stable->status)->toBeInstanceOf(\App\Enums\Stables\StableStatus::class);
            }
        });

        test('seeder creates consistent data', function () {
            // Arrange
            $initialCount = Stable::count();

            // Act
            Artisan::call('db:seed', ['--class' => 'StablesTableSeeder']);

            // Assert - Should maintain or increase count
            expect(Stable::count())->toBeGreaterThanOrEqual($initialCount);
        });
    });
});