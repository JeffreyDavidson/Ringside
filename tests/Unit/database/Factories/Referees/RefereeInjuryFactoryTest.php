<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories\Referees;

use App\Models\Referees\Referee;
use App\Models\Referees\RefereeInjury;
use Illuminate\Support\Carbon;

/**
 * Unit tests for RefereeInjuryFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (injury period data)
 * - Factory state methods (current, past, cleared injuries)
 * - Factory relationship creation (referee associations)
 * - Injury timeline data (started_at, ended_at dates)
 * - Injury period validation and consistency
 *
 * These tests verify that the RefereeInjuryFactory generates consistent,
 * realistic injury data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see \Database\Factories\Referees\RefereeInjuryFactory
 */
describe('RefereeInjuryFactory Unit Tests', function () {
    describe('default attribute generation', function () {
        test('creates injury with correct default attributes', function () {
            // Arrange & Act
            $injury = RefereeInjury::factory()->make();

            // Assert
            expect($injury->referee_id)->toBeInt();
            expect($injury->started_at)->toBeInstanceOf(Carbon::class);
            expect($injury->ended_at)->toBeNull(); // Default is current injury
        });

        test('creates realistic injury dates', function () {
            // Arrange & Act
            $injury = RefereeInjury::factory()->make();

            // Assert
            expect($injury->started_at->isToday())->toBeTrue();
        });
    });

    describe('factory state methods', function () {
        test('current injury state works correctly', function () {
            // Arrange
            $referee = Referee::factory()->create();
            $injuredDate = now()->subWeeks(2);

            // Act
            $injury = RefereeInjury::factory()->make([
                'referee_id' => $referee->id,
                'started_at' => $injuredDate,
                'ended_at' => null,
            ]);

            // Assert
            expect($injury->referee_id)->toBe($referee->id);
            expect($injury->started_at->format('Y-m-d H:i:s'))->toBe($injuredDate->format('Y-m-d H:i:s'));
            expect($injury->ended_at)->toBeNull();
        });

        test('cleared injury state works correctly', function () {
            // Arrange
            $referee = Referee::factory()->create();
            $injuredDate = now()->subMonths(3);
            $clearedDate = now()->subMonth();

            // Act
            $injury = RefereeInjury::factory()->make([
                'referee_id' => $referee->id,
                'started_at' => $injuredDate,
                'ended_at' => $clearedDate,
            ]);

            // Assert
            expect($injury->referee_id)->toBe($referee->id);
            expect($injury->started_at->format('Y-m-d H:i:s'))->toBe($injuredDate->format('Y-m-d H:i:s'));
            expect($injury->ended_at->format('Y-m-d H:i:s'))->toBe($clearedDate->format('Y-m-d H:i:s'));
            expect($injury->ended_at->isAfter($injury->started_at))->toBeTrue();
        });
    });

    describe('factory customization', function () {
        test('accepts custom referee association', function () {
            // Arrange
            $referee = Referee::factory()->create();

            // Act
            $injury = RefereeInjury::factory()->make(['referee_id' => $referee->id]);

            // Assert
            expect($injury->referee_id)->toBe($referee->id);
        });

        test('accepts custom date ranges', function () {
            // Arrange
            $injuredDate = now()->subMonths(6);
            $clearedDate = now()->subMonths(2);

            // Act
            $injury = RefereeInjury::factory()->make([
                'started_at' => $injuredDate,
                'ended_at' => $clearedDate,
            ]);

            // Assert
            expect($injury->started_at->format('Y-m-d H:i:s'))->toBe($injuredDate->format('Y-m-d H:i:s'));
            expect($injury->ended_at->format('Y-m-d H:i:s'))->toBe($clearedDate->format('Y-m-d H:i:s'));
        });
    });

    describe('data consistency', function () {
        test('database creation works correctly', function () {
            // Arrange & Act
            $injury = RefereeInjury::factory()->create();

            // Assert
            expect($injury->exists)->toBeTrue();
            expect($injury->id)->toBeGreaterThan(0);
        });

        test('maintains date consistency', function () {
            // Arrange & Act
            $injury = RefereeInjury::factory()->make();

            // Assert
            expect($injury->started_at)->toBeInstanceOf(Carbon::class);
            if ($injury->ended_at) {
                expect($injury->ended_at->isAfter($injury->started_at))->toBeTrue();
            }
        });
    });
});
