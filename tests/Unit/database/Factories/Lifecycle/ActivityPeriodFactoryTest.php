<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories\Lifecycle;

use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Titles\Title;
use Database\Factories\Lifecycle\ActivityPeriodFactory;
use Illuminate\Support\Carbon;

/**
 * Unit tests for ActivityPeriodFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (activity period data)
 * - Factory state methods (current, past, ended periods)
 * - Factory relationship creation (title associations)
 * - Activity timeline data (started_at, ended_at dates)
 * - Activity period validation and consistency
 *
 * These tests verify that the ActivityPeriodFactory generates consistent,
 * realistic activity period data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see ActivityPeriodFactory
 */
describe('ActivityPeriodFactory Unit Tests', function () {
    describe('default attribute generation', function () {
        test('creates activity period with correct default attributes', function () {
            // Arrange & Act
            $activityPeriod = ActivityPeriod::factory()->make();

            // Assert
            expect($activityPeriod->getAttributes())->not->toHaveKey('activeable_id');
            expect($activityPeriod->started_at)->toBeInstanceOf(Carbon::class);
            expect($activityPeriod->ended_at)->toBeNull(); // Default is current activity
        });

        test('creates realistic activity dates', function () {
            // Arrange & Act
            $activityPeriod = ActivityPeriod::factory()->make();

            // Assert
            expect($activityPeriod->started_at->isYesterday())->toBeTrue();
        });
    });

    describe('factory state methods', function () {
        test('current activity state works correctly', function () {
            // Arrange
            $title = Title::factory()->create();
            $startDate = now()->subMonths(6);

            // Act
            $activityPeriod = ActivityPeriod::factory()->make([
                'activeable_id' => $title->id,
                'started_at' => $startDate,
                'ended_at' => null,
            ]);

            // Assert
            expect($activityPeriod->activeable_id)->toBe($title->id);
            expect(requiredDate($activityPeriod->started_at)->format('Y-m-d H:i:s'))->toBe($startDate->format('Y-m-d H:i:s'));
            expect($activityPeriod->ended_at)->toBeNull();
        });

        test('ended activity state works correctly', function () {
            // Arrange
            $title = Title::factory()->create();
            $startDate = now()->subYears(2);
            $endDate = now()->subYear();

            // Act
            $activityPeriod = ActivityPeriod::factory()->make([
                'activeable_id' => $title->id,
                'started_at' => $startDate,
                'ended_at' => $endDate,
            ]);

            // Assert
            expect($activityPeriod->activeable_id)->toBe($title->id);
            expect(requiredDate($activityPeriod->started_at)->format('Y-m-d H:i:s'))->toBe($startDate->format('Y-m-d H:i:s'));
            expect(requiredDate($activityPeriod->ended_at)->format('Y-m-d H:i:s'))->toBe($endDate->format('Y-m-d H:i:s'));
            expect(requiredDate($activityPeriod->ended_at)->isAfter($activityPeriod->started_at))->toBeTrue();
        });
    });

    describe('factory customization', function () {
        test('accepts custom title association', function () {
            // Arrange
            $title = Title::factory()->create();

            // Act
            $activityPeriod = ActivityPeriod::factory()->make(['activeable_id' => $title->id]);

            // Assert
            expect($activityPeriod->activeable_id)->toBe($title->id);
        });

        test('accepts custom date ranges', function () {
            // Arrange
            $startDate = now()->subYears(3);
            $endDate = now()->subYears(2);

            // Act
            $activityPeriod = ActivityPeriod::factory()->make([
                'started_at' => $startDate,
                'ended_at' => $endDate,
            ]);

            // Assert
            expect(requiredDate($activityPeriod->started_at)->format('Y-m-d H:i:s'))->toBe($startDate->format('Y-m-d H:i:s'));
            expect(requiredDate($activityPeriod->ended_at)->format('Y-m-d H:i:s'))->toBe($endDate->format('Y-m-d H:i:s'));
        });
    });

    describe('data consistency', function () {
        test('database creation works correctly', function () {
            // Arrange & Act
            $activityPeriod = ActivityPeriod::factory()->forRandomActiveable()->create();

            // Assert
            expect($activityPeriod->exists)->toBeTrue();
            expect($activityPeriod->id)->toBeGreaterThan(0);
        });

        test('maintains date consistency', function () {
            // Arrange & Act
            $activityPeriod = ActivityPeriod::factory()->make();

            // Assert
            expect($activityPeriod->started_at)->toBeInstanceOf(Carbon::class);
            if ($activityPeriod->ended_at) {
                expect(requiredDate($activityPeriod->ended_at)->isAfter($activityPeriod->started_at))->toBeTrue();
            }
        });
    });
});
