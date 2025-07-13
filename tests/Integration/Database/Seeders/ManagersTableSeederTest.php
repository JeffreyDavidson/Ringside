<?php

declare(strict_types=1);

use App\Models\Managers\Manager;
use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\assertDatabaseCount;

/**
 * Integration tests for ManagersTableSeeder data seeding and validation.
 *
 * INTEGRATION TEST SCOPE:
 * - Seeder execution and database population
 * - Manager record creation and validation
 * - Data consistency and count verification
 * - Manager attribute validation
 *
 * These tests verify that the ManagersTableSeeder correctly populates
 * the database with manager records for development and testing purposes.
 *
 * @see \Database\Seeders\ManagersTableSeeder
 */
describe('ManagersTableSeeder Integration Tests', function () {
    describe('seeder execution', function () {
        test('successfully runs without errors', function () {
            // Act & Assert - Should not throw any exceptions
            expect(fn() => Artisan::call('db:seed', ['--class' => 'ManagersTableSeeder']))
                ->not->toThrow(Exception::class);
        });

        test('creates managers in database', function () {
            // Arrange & Act
            Artisan::call('db:seed', ['--class' => 'ManagersTableSeeder']);

            // Assert - Should create multiple managers
            expect(Manager::count())->toBeGreaterThan(0);
        });
    });

    describe('manager attributes', function () {
        beforeEach(function () {
            Artisan::call('db:seed', ['--class' => 'ManagersTableSeeder']);
        });

        test('managers have required attributes', function () {
            // Arrange
            $managers = Manager::take(10)->get();

            // Assert
            foreach ($managers as $manager) {
                expect($manager->first_name)->toBeString();
                expect($manager->first_name)->not->toBeEmpty();
                expect($manager->last_name)->toBeString();
                expect($manager->last_name)->not->toBeEmpty();
                expect($manager->status)->toBeInstanceOf(\App\Enums\Shared\EmploymentStatus::class);
            }
        });

        test('managers have realistic names', function () {
            // Arrange
            $managers = Manager::take(5)->get();

            // Assert
            foreach ($managers as $manager) {
                expect(strlen($manager->first_name))->toBeGreaterThan(2);
                expect(strlen($manager->last_name))->toBeGreaterThan(2);
                expect($manager->first_name)->not->toContain('Test');
                expect($manager->last_name)->not->toContain('Test');
            }
        });
    });

    describe('data consistency', function () {
        beforeEach(function () {
            Artisan::call('db:seed', ['--class' => 'ManagersTableSeeder']);
        });

        test('managers have unique name combinations', function () {
            // Arrange
            $managers = Manager::all();
            $fullNames = $managers->map(fn($manager) => $manager->first_name . ' ' . $manager->last_name);

            // Assert
            expect($fullNames->unique())->toHaveCount($managers->count());
        });

        test('managers have valid employment status', function () {
            // Arrange
            $managers = Manager::take(10)->get();

            // Assert
            foreach ($managers as $manager) {
                expect($manager->status)->toBeInstanceOf(\App\Enums\Shared\EmploymentStatus::class);
            }
        });

        test('seeder creates consistent data', function () {
            // Arrange
            $initialCount = Manager::count();

            // Act
            Artisan::call('db:seed', ['--class' => 'ManagersTableSeeder']);

            // Assert - Should maintain or increase count
            expect(Manager::count())->toBeGreaterThanOrEqual($initialCount);
        });
    });
});