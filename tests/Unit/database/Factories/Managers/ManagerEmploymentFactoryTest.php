<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories\Managers;

use App\Models\Managers\Manager;
use App\Models\Managers\ManagerEmployment;

/**
 * Unit tests for ManagerEmploymentFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (employment periods)
 * - Factory state methods (current, past, ended, etc.)
 * - Factory relationship creation (manager associations)
 * - Employment timeline data (started_at, ended_at dates)
 * - Employment period validation and consistency
 *
 * These tests verify that the ManagerEmploymentFactory generates consistent,
 * realistic employment data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see \Database\Factories\Managers\ManagerEmploymentFactory
 */
describe('ManagerEmploymentFactory Unit Tests', function () {
    describe('default attribute generation', function () {
        test('creates employment with correct default attributes', function () {
            // Arrange & Act
            $employment = ManagerEmployment::factory()->make();
            
            // Assert
            expect($employment->manager_id)->toBeInt();
            expect($employment->started_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
            expect($employment->ended_at)->toBeNull(); // Default is current employment
        });

        test('creates realistic employment dates', function () {
            // Arrange & Act
            $employment = ManagerEmployment::factory()->make();
            
            // Assert
            expect($employment->started_at->isToday())->toBeTrue();
        });
    });

    describe('factory state methods', function () {
        test('current employment state works correctly', function () {
            // Arrange
            $manager = Manager::factory()->create();
            $startDate = now()->subMonth();

            // Act
            $employment = ManagerEmployment::factory()->make([
                'manager_id' => $manager->id,
                'started_at' => $startDate,
                'ended_at' => null,
            ]);
            
            // Assert
            expect($employment->manager_id)->toBe($manager->id);
            expect($employment->started_at->format('Y-m-d H:i:s'))->toBe($startDate->format('Y-m-d H:i:s'));
            expect($employment->ended_at)->toBeNull();
        });

        test('past employment state works correctly', function () {
            // Arrange
            $manager = Manager::factory()->create();
            $startDate = now()->subYear();
            $endDate = now()->subMonths(6);

            // Act
            $employment = ManagerEmployment::factory()->make([
                'manager_id' => $manager->id,
                'started_at' => $startDate,
                'ended_at' => $endDate,
            ]);
            
            // Assert
            expect($employment->manager_id)->toBe($manager->id);
            expect($employment->started_at->format('Y-m-d H:i:s'))->toBe($startDate->format('Y-m-d H:i:s'));
            expect($employment->ended_at->format('Y-m-d H:i:s'))->toBe($endDate->format('Y-m-d H:i:s'));
            expect($employment->ended_at->isAfter($employment->started_at))->toBeTrue();
        });
    });

    describe('factory customization', function () {
        test('accepts custom manager association', function () {
            // Arrange
            $manager = Manager::factory()->create();

            // Act
            $employment = ManagerEmployment::factory()->make(['manager_id' => $manager->id]);
            
            // Assert
            expect($employment->manager_id)->toBe($manager->id);
        });

        test('accepts custom date ranges', function () {
            // Arrange
            $startDate = now()->subYear();
            $endDate = now()->subMonths(3);

            // Act
            $employment = ManagerEmployment::factory()->make([
                'started_at' => $startDate,
                'ended_at' => $endDate,
            ]);
            
            // Assert
            expect($employment->started_at->format('Y-m-d H:i:s'))->toBe($startDate->format('Y-m-d H:i:s'));
            expect($employment->ended_at->format('Y-m-d H:i:s'))->toBe($endDate->format('Y-m-d H:i:s'));
        });
    });

    describe('data consistency', function () {
        test('database creation works correctly', function () {
            // Arrange & Act
            $employment = ManagerEmployment::factory()->create();
            
            // Assert
            expect($employment->exists)->toBeTrue();
            expect($employment->id)->toBeGreaterThan(0);
        });

        test('maintains date consistency', function () {
            // Arrange & Act
            $employment = ManagerEmployment::factory()->make();
            
            // Assert
            expect($employment->started_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
            if ($employment->ended_at) {
                expect($employment->ended_at->isAfter($employment->started_at))->toBeTrue();
            }
        });
    });
});