<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories\Stables;

use App\Models\Stables\Stable;
use App\Models\Stables\StableActivityPeriod;

/**
 * Unit tests for StableActivityPeriodFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (activity period data)
 * - Factory state methods (current, past, ended periods)
 * - Factory relationship creation (stable associations)
 * - Activity timeline data (started_at, ended_at dates)
 * - Activity period validation and consistency
 *
 * These tests verify that the StableActivityPeriodFactory generates consistent,
 * realistic activity period data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see \Database\Factories\Stables\StableActivityPeriodFactory
 */
describe('StableActivityPeriodFactory Unit Tests', function () {
    describe('default attribute generation', function () {
        test('creates activity period with correct default attributes', function () {
            // Arrange & Act
            $activityPeriod = StableActivityPeriod::factory()->make();
            
            // Assert
            expect($activityPeriod->stable_id)->toBeInt();
            expect($activityPeriod->started_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
            expect($activityPeriod->ended_at)->toBeNull(); // Default is current activity
        });

        test('creates realistic activity dates', function () {
            // Arrange & Act
            $activityPeriod = StableActivityPeriod::factory()->make();
            
            // Assert
            expect($activityPeriod->started_at->isToday())->toBeTrue();
        });
    });

    describe('factory state methods', function () {
        test('current activity state works correctly', function () {
            // Arrange
            $stable = Stable::factory()->create();
            $startDate = now()->subMonths(6);

            // Act
            $activityPeriod = StableActivityPeriod::factory()->make([
                'stable_id' => $stable->id,
                'started_at' => $startDate,
                'ended_at' => null,
            ]);
            
            // Assert
            expect($activityPeriod->stable_id)->toBe($stable->id);
            expect($activityPeriod->started_at->format('Y-m-d H:i:s'))->toBe($startDate->format('Y-m-d H:i:s'));
            expect($activityPeriod->ended_at)->toBeNull();
        });

        test('ended activity state works correctly', function () {
            // Arrange
            $stable = Stable::factory()->create();
            $startDate = now()->subYears(2);
            $endDate = now()->subYear();

            // Act
            $activityPeriod = StableActivityPeriod::factory()->make([
                'stable_id' => $stable->id,
                'started_at' => $startDate,
                'ended_at' => $endDate,
            ]);
            
            // Assert
            expect($activityPeriod->stable_id)->toBe($stable->id);
            expect($activityPeriod->started_at->format('Y-m-d H:i:s'))->toBe($startDate->format('Y-m-d H:i:s'));
            expect($activityPeriod->ended_at->format('Y-m-d H:i:s'))->toBe($endDate->format('Y-m-d H:i:s'));
            expect($activityPeriod->ended_at->isAfter($activityPeriod->started_at))->toBeTrue();
        });
    });

    describe('factory customization', function () {
        test('accepts custom stable association', function () {
            // Arrange
            $stable = Stable::factory()->create();

            // Act
            $activityPeriod = StableActivityPeriod::factory()->make(['stable_id' => $stable->id]);
            
            // Assert
            expect($activityPeriod->stable_id)->toBe($stable->id);
        });

        test('accepts custom date ranges', function () {
            // Arrange
            $startDate = now()->subYears(3);
            $endDate = now()->subYears(2);

            // Act
            $activityPeriod = StableActivityPeriod::factory()->make([
                'started_at' => $startDate,
                'ended_at' => $endDate,
            ]);
            
            // Assert
            expect($activityPeriod->started_at->format('Y-m-d H:i:s'))->toBe($startDate->format('Y-m-d H:i:s'));
            expect($activityPeriod->ended_at->format('Y-m-d H:i:s'))->toBe($endDate->format('Y-m-d H:i:s'));
        });
    });

    describe('data consistency', function () {
        test('database creation works correctly', function () {
            // Arrange & Act
            $activityPeriod = StableActivityPeriod::factory()->create();
            
            // Assert
            expect($activityPeriod->exists)->toBeTrue();
            expect($activityPeriod->id)->toBeGreaterThan(0);
        });

        test('maintains date consistency', function () {
            // Arrange & Act
            $activityPeriod = StableActivityPeriod::factory()->make();
            
            // Assert
            expect($activityPeriod->started_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
            if ($activityPeriod->ended_at) {
                expect($activityPeriod->ended_at->isAfter($activityPeriod->started_at))->toBeTrue();
            }
        });
    });
});