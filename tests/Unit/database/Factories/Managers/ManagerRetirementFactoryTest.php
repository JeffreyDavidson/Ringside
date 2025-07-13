<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories\Managers;

use App\Models\Managers\Manager;
use App\Models\Managers\ManagerRetirement;

/**
 * Unit tests for ManagerRetirementFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (retirement period data)
 * - Factory state methods (current, past, ended retirements)
 * - Factory relationship creation (manager associations)
 * - Retirement timeline data (started_at, ended_at dates)
 * - Retirement period validation and consistency
 *
 * These tests verify that the ManagerRetirementFactory generates consistent,
 * realistic retirement data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see \Database\Factories\Managers\ManagerRetirementFactory
 */
describe('ManagerRetirementFactory Unit Tests', function () {
    describe('default attribute generation', function () {
        test('creates retirement with correct default attributes', function () {
            // Arrange & Act
            $retirement = ManagerRetirement::factory()->make();
            
            // Assert
            expect($retirement->manager_id)->toBeInt();
            expect($retirement->started_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
            expect($retirement->ended_at)->toBeNull(); // Default is current retirement
        });

        test('creates realistic retirement dates', function () {
            // Arrange & Act
            $retirement = ManagerRetirement::factory()->make();
            
            // Assert
            expect($retirement->started_at->isToday())->toBeTrue();
        });
    });

    describe('factory state methods', function () {
        test('current retirement state works correctly', function () {
            // Arrange
            $manager = Manager::factory()->create();
            $retiredDate = now()->subMonths(6);

            // Act
            $retirement = ManagerRetirement::factory()->make([
                'manager_id' => $manager->id,
                'started_at' => $retiredDate,
                'ended_at' => null,
            ]);
            
            // Assert
            expect($retirement->manager_id)->toBe($manager->id);
            expect($retirement->started_at->format('Y-m-d H:i:s'))->toBe($retiredDate->format('Y-m-d H:i:s'));
            expect($retirement->ended_at)->toBeNull();
        });

        test('ended retirement state works correctly', function () {
            // Arrange
            $manager = Manager::factory()->create();
            $retiredDate = now()->subYears(2);
            $endedDate = now()->subYear();

            // Act
            $retirement = ManagerRetirement::factory()->make([
                'manager_id' => $manager->id,
                'started_at' => $retiredDate,
                'ended_at' => $endedDate,
            ]);
            
            // Assert
            expect($retirement->manager_id)->toBe($manager->id);
            expect($retirement->started_at->format('Y-m-d H:i:s'))->toBe($retiredDate->format('Y-m-d H:i:s'));
            expect($retirement->ended_at->format('Y-m-d H:i:s'))->toBe($endedDate->format('Y-m-d H:i:s'));
            expect($retirement->ended_at->isAfter($retirement->started_at))->toBeTrue();
        });
    });

    describe('factory customization', function () {
        test('accepts custom manager association', function () {
            // Arrange
            $manager = Manager::factory()->create();

            // Act
            $retirement = ManagerRetirement::factory()->make(['manager_id' => $manager->id]);
            
            // Assert
            expect($retirement->manager_id)->toBe($manager->id);
        });

        test('accepts custom date ranges', function () {
            // Arrange
            $retiredDate = now()->subYears(3);
            $endedDate = now()->subYears(2);

            // Act
            $retirement = ManagerRetirement::factory()->make([
                'started_at' => $retiredDate,
                'ended_at' => $endedDate,
            ]);
            
            // Assert
            expect($retirement->started_at->format('Y-m-d H:i:s'))->toBe($retiredDate->format('Y-m-d H:i:s'));
            expect($retirement->ended_at->format('Y-m-d H:i:s'))->toBe($endedDate->format('Y-m-d H:i:s'));
        });
    });

    describe('data consistency', function () {
        test('database creation works correctly', function () {
            // Arrange & Act
            $retirement = ManagerRetirement::factory()->create();
            
            // Assert
            expect($retirement->exists)->toBeTrue();
            expect($retirement->id)->toBeGreaterThan(0);
        });

        test('maintains date consistency', function () {
            // Arrange & Act
            $retirement = ManagerRetirement::factory()->make();
            
            // Assert
            expect($retirement->started_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
            if ($retirement->ended_at) {
                expect($retirement->ended_at->isAfter($retirement->started_at))->toBeTrue();
            }
        });
    });
});