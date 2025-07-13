<?php

declare(strict_types=1);

use App\Models\TagTeams\TagTeam;
use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\assertDatabaseCount;

/**
 * Integration tests for TagTeamsTableSeeder data seeding and validation.
 *
 * INTEGRATION TEST SCOPE:
 * - Seeder execution and database population
 * - Tag team record creation and validation
 * - Data consistency and count verification
 * - Tag team attribute validation
 *
 * These tests verify that the TagTeamsTableSeeder correctly populates
 * the database with tag team records for development and testing purposes.
 *
 * @see \Database\Seeders\TagTeamsTableSeeder
 */
describe('TagTeamsTableSeeder Integration Tests', function () {
    describe('seeder execution', function () {
        test('successfully runs without errors', function () {
            // Act & Assert - Should not throw any exceptions
            expect(fn() => Artisan::call('db:seed', ['--class' => 'TagTeamsTableSeeder']))
                ->not->toThrow(Exception::class);
        });

        test('creates tag teams in database', function () {
            // Arrange & Act
            Artisan::call('db:seed', ['--class' => 'TagTeamsTableSeeder']);

            // Assert - Should create multiple tag teams
            expect(TagTeam::count())->toBeGreaterThan(0);
        });
    });

    describe('tag team attributes', function () {
        beforeEach(function () {
            Artisan::call('db:seed', ['--class' => 'TagTeamsTableSeeder']);
        });

        test('tag teams have required attributes', function () {
            // Arrange
            $tagTeams = TagTeam::take(10)->get();

            // Assert
            foreach ($tagTeams as $tagTeam) {
                expect($tagTeam->name)->toBeString();
                expect($tagTeam->name)->not->toBeEmpty();
                expect($tagTeam->status)->toBeInstanceOf(\App\Enums\Shared\EmploymentStatus::class);
            }
        });

        test('tag teams have realistic names', function () {
            // Arrange
            $tagTeams = TagTeam::take(5)->get();

            // Assert
            foreach ($tagTeams as $tagTeam) {
                expect(strlen($tagTeam->name))->toBeGreaterThan(5);
                expect($tagTeam->name)->not->toContain('Test');
            }
        });
    });

    describe('data consistency', function () {
        beforeEach(function () {
            Artisan::call('db:seed', ['--class' => 'TagTeamsTableSeeder']);
        });

        test('all tag teams have unique names', function () {
            // Arrange
            $tagTeams = TagTeam::all();

            // Assert
            expect($tagTeams->pluck('name')->unique())->toHaveCount($tagTeams->count());
        });

        test('tag teams have valid employment status', function () {
            // Arrange
            $tagTeams = TagTeam::take(10)->get();

            // Assert
            foreach ($tagTeams as $tagTeam) {
                expect($tagTeam->status)->toBeInstanceOf(\App\Enums\Shared\EmploymentStatus::class);
            }
        });

        test('seeder creates consistent data', function () {
            // Arrange
            $initialCount = TagTeam::count();

            // Act
            Artisan::call('db:seed', ['--class' => 'TagTeamsTableSeeder']);

            // Assert - Should maintain or increase count
            expect(TagTeam::count())->toBeGreaterThanOrEqual($initialCount);
        });
    });
});