<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories\Wrestlers;

use App\Models\Wrestlers\Wrestler;
use App\Models\Wrestlers\WrestlerRetirement;
use Illuminate\Support\Carbon;

/**
 * Unit tests for WrestlerRetirementFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (retirement period data)
 * - Factory state methods (current, past, ended retirements)
 * - Factory relationship creation (wrestler associations)
 * - Retirement timeline data (started_at, ended_at dates)
 * - Retirement period validation and consistency
 *
 * These tests verify that the WrestlerRetirementFactory generates consistent,
 * realistic retirement data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see \Database\Factories\Wrestlers\WrestlerRetirementFactory
 */
describe('WrestlerRetirementFactory Unit Tests', function () {
    describe('default attribute generation', function () {
        test('creates retirement with correct default attributes', function () {
            // Arrange & Act
            $retirement = WrestlerRetirement::factory()->make();

            // Assert
            expect($retirement->wrestler_id)->toBeInt();
            expect($retirement->started_at)->toBeInstanceOf(Carbon::class);
            expect($retirement->ended_at)->toBeNull(); // Default is current retirement
        });

        test('creates realistic retirement dates', function () {
            // Arrange & Act
            $retirement = WrestlerRetirement::factory()->make();

            // Assert
            expect($retirement->started_at->isToday())->toBeTrue();
        });
    });

    describe('factory state methods', function () {
        test('current retirement state works correctly', function () {
            // Arrange
            $wrestler = Wrestler::factory()->create();
            $retiredDate = now()->subMonths(6);

            // Act
            $retirement = WrestlerRetirement::factory()->make([
                'wrestler_id' => $wrestler->id,
                'started_at' => $retiredDate,
                'ended_at' => null,
            ]);

            // Assert
            expect($retirement->wrestler_id)->toBe($wrestler->id);
            expect($retirement->started_at->format('Y-m-d H:i:s'))->toBe($retiredDate->format('Y-m-d H:i:s'));
            expect($retirement->ended_at)->toBeNull();
        });

        test('ended retirement state works correctly', function () {
            // Arrange
            $wrestler = Wrestler::factory()->create();
            $retiredDate = now()->subYears(2);
            $endedDate = now()->subYear();

            // Act
            $retirement = WrestlerRetirement::factory()->make([
                'wrestler_id' => $wrestler->id,
                'started_at' => $retiredDate,
                'ended_at' => $endedDate,
            ]);

            // Assert
            expect($retirement->wrestler_id)->toBe($wrestler->id);
            expect($retirement->started_at->format('Y-m-d H:i:s'))->toBe($retiredDate->format('Y-m-d H:i:s'));
            expect($retirement->ended_at->format('Y-m-d H:i:s'))->toBe($endedDate->format('Y-m-d H:i:s'));
            expect($retirement->ended_at->isAfter($retirement->started_at))->toBeTrue();
        });
    });

    describe('factory customization', function () {
        test('accepts custom wrestler association', function () {
            // Arrange
            $wrestler = Wrestler::factory()->create();

            // Act
            $retirement = WrestlerRetirement::factory()->make(['wrestler_id' => $wrestler->id]);

            // Assert
            expect($retirement->wrestler_id)->toBe($wrestler->id);
        });

        test('accepts custom date ranges', function () {
            // Arrange
            $retiredDate = now()->subYears(3);
            $endedDate = now()->subYears(2);

            // Act
            $retirement = WrestlerRetirement::factory()->make([
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
            $retirement = WrestlerRetirement::factory()->create();

            // Assert
            expect($retirement->exists)->toBeTrue();
            expect($retirement->id)->toBeGreaterThan(0);
        });

        test('maintains date consistency', function () {
            // Arrange & Act
            $retirement = WrestlerRetirement::factory()->make();

            // Assert
            expect($retirement->started_at)->toBeInstanceOf(Carbon::class);
            if ($retirement->ended_at) {
                expect($retirement->ended_at->isAfter($retirement->started_at))->toBeTrue();
            }
        });
    });
});
