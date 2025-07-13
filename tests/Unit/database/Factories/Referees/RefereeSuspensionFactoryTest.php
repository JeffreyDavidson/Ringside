<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories\Referees;

use App\Models\Referees\Referee;
use App\Models\Referees\RefereeSuspension;

/**
 * Unit tests for RefereeSuspensionFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (suspension period data)
 * - Factory state methods (current, past, reinstated suspensions)
 * - Factory relationship creation (referee associations)
 * - Suspension timeline data (started_at, ended_at dates)
 * - Suspension period validation and consistency
 *
 * These tests verify that the RefereeSuspensionFactory generates consistent,
 * realistic suspension data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see \Database\Factories\Referees\RefereeSuspensionFactory
 */
describe('RefereeSuspensionFactory Unit Tests', function () {
    describe('default attribute generation', function () {
        test('creates suspension with correct default attributes', function () {
            // Arrange & Act
            $suspension = RefereeSuspension::factory()->make();

            // Assert
            expect($suspension->referee_id)->toBeInt();
            expect($suspension->started_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
            expect($suspension->ended_at)->toBeNull(); // Default is current suspension
        });

        test('creates realistic suspension dates', function () {
            // Arrange & Act
            $suspension = RefereeSuspension::factory()->make();

            // Assert
            expect($suspension->started_at->isToday())->toBeTrue();
        });
    });

    describe('factory state methods', function () {
        test('current suspension state works correctly', function () {
            // Arrange
            $referee = Referee::factory()->create();
            $suspendedDate = now()->subWeeks(3);

            // Act
            $suspension = RefereeSuspension::factory()->make([
                'referee_id' => $referee->id,
                'started_at' => $suspendedDate,
                'ended_at' => null,
            ]);

            // Assert
            expect($suspension->referee_id)->toBe($referee->id);
            expect($suspension->started_at->format('Y-m-d H:i:s'))->toBe($suspendedDate->format('Y-m-d H:i:s'));
            expect($suspension->ended_at)->toBeNull();
        });

        test('reinstated suspension state works correctly', function () {
            // Arrange
            $referee = Referee::factory()->create();
            $suspendedDate = now()->subMonths(4);
            $reinstatedDate = now()->subMonths(2);

            // Act
            $suspension = RefereeSuspension::factory()->make([
                'referee_id' => $referee->id,
                'started_at' => $suspendedDate,
                'ended_at' => $reinstatedDate,
            ]);

            // Assert
            expect($suspension->referee_id)->toBe($referee->id);
            expect($suspension->started_at->format('Y-m-d H:i:s'))->toBe($suspendedDate->format('Y-m-d H:i:s'));
            expect($suspension->ended_at->format('Y-m-d H:i:s'))->toBe($reinstatedDate->format('Y-m-d H:i:s'));
            expect($suspension->ended_at->isAfter($suspension->started_at))->toBeTrue();
        });
    });

    describe('factory customization', function () {
        test('accepts custom referee association', function () {
            // Arrange
            $referee = Referee::factory()->create();

            // Act
            $suspension = RefereeSuspension::factory()->make(['referee_id' => $referee->id]);

            // Assert
            expect($suspension->referee_id)->toBe($referee->id);
        });

        test('accepts custom date ranges', function () {
            // Arrange
            $suspendedDate = now()->subMonths(8);
            $reinstatedDate = now()->subMonths(5);

            // Act
            $suspension = RefereeSuspension::factory()->make([
                'started_at' => $suspendedDate,
                'ended_at' => $reinstatedDate,
            ]);

            // Assert
            expect($suspension->started_at->format('Y-m-d H:i:s'))->toBe($suspendedDate->format('Y-m-d H:i:s'));
            expect($suspension->ended_at->format('Y-m-d H:i:s'))->toBe($reinstatedDate->format('Y-m-d H:i:s'));
        });
    });

    describe('data consistency', function () {
        test('database creation works correctly', function () {
            // Arrange & Act
            $suspension = RefereeSuspension::factory()->create();

            // Assert
            expect($suspension->exists)->toBeTrue();
            expect($suspension->id)->toBeGreaterThan(0);
        });

        test('maintains date consistency', function () {
            // Arrange & Act
            $suspension = RefereeSuspension::factory()->make();

            // Assert
            expect($suspension->started_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
            if ($suspension->ended_at) {
                expect($suspension->ended_at->isAfter($suspension->started_at))->toBeTrue();
            }
        });
    });
});
