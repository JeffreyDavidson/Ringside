<?php

declare(strict_types=1);

use App\Models\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\assertDatabaseCount;

/**
 * Integration tests for WrestlersTableSeeder data seeding and validation.
 *
 * INTEGRATION TEST SCOPE:
 * - Seeder execution and database population
 * - Wrestler record creation and validation
 * - Data consistency and count verification
 * - Wrestler attribute validation
 *
 * These tests verify that the WrestlersTableSeeder correctly populates
 * the database with wrestler records for development and testing purposes.
 *
 * @see \Database\Seeders\WrestlersTableSeeder
 */
describe('WrestlersTableSeeder Integration Tests', function () {
    describe('seeder execution', function () {
        test('successfully runs without errors', function () {
            // Act & Assert - Should not throw any exceptions
            expect(fn() => Artisan::call('db:seed', ['--class' => 'WrestlersTableSeeder']))
                ->not->toThrow(Exception::class);
        });

        test('creates wrestlers in database', function () {
            // Arrange & Act
            Artisan::call('db:seed', ['--class' => 'WrestlersTableSeeder']);

            // Assert - Should create multiple wrestlers
            expect(Wrestler::count())->toBeGreaterThan(0);
        });
    });

    describe('wrestler attributes', function () {
        beforeEach(function () {
            Artisan::call('db:seed', ['--class' => 'WrestlersTableSeeder']);
        });

        test('wrestlers have required attributes', function () {
            // Arrange
            $wrestlers = Wrestler::take(10)->get();

            // Assert
            foreach ($wrestlers as $wrestler) {
                expect($wrestler->name)->toBeString();
                expect($wrestler->name)->not->toBeEmpty();
                expect($wrestler->hometown)->toBeString();
                expect($wrestler->hometown)->not->toBeEmpty();
                expect($wrestler->height_feet)->toBeInt();
                expect($wrestler->height_inches)->toBeInt();
                expect($wrestler->weight)->toBeInt();
            }
        });

        test('wrestlers have realistic physical attributes', function () {
            // Arrange
            $wrestlers = Wrestler::take(10)->get();

            // Assert
            foreach ($wrestlers as $wrestler) {
                expect($wrestler->height_feet)->toBeBetween(4, 8);
                expect($wrestler->height_inches)->toBeBetween(0, 11);
                expect($wrestler->weight)->toBeBetween(100, 500);
            }
        });

        test('wrestlers have realistic hometown format', function () {
            // Arrange
            $wrestlers = Wrestler::take(5)->get();

            // Assert
            foreach ($wrestlers as $wrestler) {
                expect($wrestler->hometown)->toContain(','); // City, State format
            }
        });
    });

    describe('data consistency', function () {
        beforeEach(function () {
            Artisan::call('db:seed', ['--class' => 'WrestlersTableSeeder']);
        });

        test('all wrestlers have unique names', function () {
            // Arrange
            $wrestlers = Wrestler::all();

            // Assert
            expect($wrestlers->pluck('name')->unique())->toHaveCount($wrestlers->count());
        });

        test('wrestlers have valid employment status', function () {
            // Arrange
            $wrestlers = Wrestler::take(10)->get();

            // Assert
            foreach ($wrestlers as $wrestler) {
                expect($wrestler->status)->toBeInstanceOf(\App\Enums\Shared\EmploymentStatus::class);
            }
        });

        test('seeder creates consistent data', function () {
            // Arrange
            $initialCount = Wrestler::count();

            // Act
            Artisan::call('db:seed', ['--class' => 'WrestlersTableSeeder']);

            // Assert - Should maintain or increase count
            expect(Wrestler::count())->toBeGreaterThanOrEqual($initialCount);
        });
    });
});