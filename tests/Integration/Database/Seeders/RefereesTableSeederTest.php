<?php

declare(strict_types=1);

use App\Models\Referees\Referee;
use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\assertDatabaseCount;

/**
 * Integration tests for RefereesTableSeeder data seeding and validation.
 *
 * INTEGRATION TEST SCOPE:
 * - Seeder execution and database population
 * - Referee record creation and validation
 * - Data consistency and count verification
 * - Referee attribute validation
 *
 * These tests verify that the RefereesTableSeeder correctly populates
 * the database with referee records for development and testing purposes.
 *
 * @see \Database\Seeders\RefereesTableSeeder
 */
describe('RefereesTableSeeder Integration Tests', function () {
    describe('seeder execution', function () {
        test('successfully runs without errors', function () {
            // Act & Assert - Should not throw any exceptions
            expect(fn() => Artisan::call('db:seed', ['--class' => 'RefereesTableSeeder']))
                ->not->toThrow(Exception::class);
        });

        test('creates referees in database', function () {
            // Arrange & Act
            Artisan::call('db:seed', ['--class' => 'RefereesTableSeeder']);

            // Assert - Should create multiple referees
            expect(Referee::count())->toBeGreaterThan(0);
        });
    });

    describe('referee attributes', function () {
        beforeEach(function () {
            Artisan::call('db:seed', ['--class' => 'RefereesTableSeeder']);
        });

        test('referees have required attributes', function () {
            // Arrange
            $referees = Referee::take(10)->get();

            // Assert
            foreach ($referees as $referee) {
                expect($referee->first_name)->toBeString();
                expect($referee->first_name)->not->toBeEmpty();
                expect($referee->last_name)->toBeString();
                expect($referee->last_name)->not->toBeEmpty();
                expect($referee->status)->toBeInstanceOf(\App\Enums\Shared\EmploymentStatus::class);
            }
        });

        test('referees have realistic names', function () {
            // Arrange
            $referees = Referee::take(5)->get();

            // Assert
            foreach ($referees as $referee) {
                expect(strlen($referee->first_name))->toBeGreaterThan(2);
                expect(strlen($referee->last_name))->toBeGreaterThan(2);
                expect($referee->first_name)->not->toContain('Test');
                expect($referee->last_name)->not->toContain('Test');
            }
        });
    });

    describe('data consistency', function () {
        beforeEach(function () {
            Artisan::call('db:seed', ['--class' => 'RefereesTableSeeder']);
        });

        test('referees have unique name combinations', function () {
            // Arrange
            $referees = Referee::all();
            $fullNames = $referees->map(fn($referee) => $referee->first_name . ' ' . $referee->last_name);

            // Assert
            expect($fullNames->unique())->toHaveCount($referees->count());
        });

        test('referees have valid employment status', function () {
            // Arrange
            $referees = Referee::take(10)->get();

            // Assert
            foreach ($referees as $referee) {
                expect($referee->status)->toBeInstanceOf(\App\Enums\Shared\EmploymentStatus::class);
            }
        });

        test('seeder creates consistent data', function () {
            // Arrange
            $initialCount = Referee::count();

            // Act
            Artisan::call('db:seed', ['--class' => 'RefereesTableSeeder']);

            // Assert - Should maintain or increase count
            expect(Referee::count())->toBeGreaterThanOrEqual($initialCount);
        });
    });
});