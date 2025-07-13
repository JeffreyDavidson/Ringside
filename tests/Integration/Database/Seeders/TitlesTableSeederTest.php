<?php

declare(strict_types=1);

use App\Models\Titles\Title;
use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\assertDatabaseCount;

/**
 * Integration tests for TitlesTableSeeder data seeding and validation.
 *
 * INTEGRATION TEST SCOPE:
 * - Seeder execution and database population
 * - Title record creation and validation
 * - Data consistency and count verification
 * - Title attribute validation
 *
 * These tests verify that the TitlesTableSeeder correctly populates
 * the database with title records for development and testing purposes.
 *
 * @see \Database\Seeders\TitlesTableSeeder
 */
describe('TitlesTableSeeder Integration Tests', function () {
    describe('seeder execution', function () {
        test('successfully runs without errors', function () {
            // Act & Assert - Should not throw any exceptions
            expect(fn() => Artisan::call('db:seed', ['--class' => 'TitlesTableSeeder']))
                ->not->toThrow(Exception::class);
        });

        test('creates titles in database', function () {
            // Arrange & Act
            Artisan::call('db:seed', ['--class' => 'TitlesTableSeeder']);

            // Assert - Should create multiple titles
            expect(Title::count())->toBeGreaterThan(0);
        });
    });

    describe('title attributes', function () {
        beforeEach(function () {
            Artisan::call('db:seed', ['--class' => 'TitlesTableSeeder']);
        });

        test('titles have required attributes', function () {
            // Arrange
            $titles = Title::take(10)->get();

            // Assert
            foreach ($titles as $title) {
                expect($title->name)->toBeString();
                expect($title->name)->not->toBeEmpty();
                expect($title->status)->toBeInstanceOf(\App\Enums\Titles\TitleStatus::class);
            }
        });

        test('titles have realistic names', function () {
            // Arrange
            $titles = Title::take(5)->get();

            // Assert
            foreach ($titles as $title) {
                expect(strlen($title->name))->toBeGreaterThan(5);
                expect($title->name)->not->toContain('Test');
                // Wrestling titles often contain words like "Championship", "Title", "Belt"
                $hasWrestlingTerms = str_contains($title->name, 'Championship') ||
                                   str_contains($title->name, 'Title') ||
                                   str_contains($title->name, 'Belt') ||
                                   str_contains($title->name, 'World') ||
                                   str_contains($title->name, 'Heavyweight');
                expect($hasWrestlingTerms)->toBeTrue();
            }
        });
    });

    describe('data consistency', function () {
        beforeEach(function () {
            Artisan::call('db:seed', ['--class' => 'TitlesTableSeeder']);
        });

        test('all titles have unique names', function () {
            // Arrange
            $titles = Title::all();

            // Assert
            expect($titles->pluck('name')->unique())->toHaveCount($titles->count());
        });

        test('titles have valid status', function () {
            // Arrange
            $titles = Title::take(10)->get();

            // Assert
            foreach ($titles as $title) {
                expect($title->status)->toBeInstanceOf(\App\Enums\Titles\TitleStatus::class);
            }
        });

        test('seeder creates consistent data', function () {
            // Arrange
            $initialCount = Title::count();

            // Act
            Artisan::call('db:seed', ['--class' => 'TitlesTableSeeder']);

            // Assert - Should maintain or increase count
            expect(Title::count())->toBeGreaterThanOrEqual($initialCount);
        });
    });
});