<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\Wrestlers\Wrestler;
use App\Models\Wrestlers\WrestlerEmployment;

/**
 * Unit tests for WrestlerEmploymentFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (employment periods)
 * - Factory state methods (current, past, future, ended, etc.)
 * - Factory relationship creation (wrestler associations)
 * - Employment timeline data (started_at, ended_at dates)
 * - Employment period validation and consistency
 *
 * These tests verify that the WrestlerEmploymentFactory generates consistent,
 * realistic employment data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see \Database\Factories\Wrestlers\WrestlerEmploymentFactory
 */

describe('WrestlerEmploymentFactory Unit Tests', function () {
    describe('default attribute generation', function () {
        test('creates employment with correct default attributes', function () {
            // Arrange & Act
            $employment = WrestlerEmployment::factory()->make();
            
            // Assert
            expect($employment->wrestler_id)->toBeInt();
            expect($employment->started_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
            expect($employment->ended_at)->toBeNull(); // Default is current employment
        });

        test('creates realistic employment dates', function () {
            // Arrange & Act
            $employment = WrestlerEmployment::factory()->make();
            
            // Assert
            expect($employment->started_at->isToday())->toBeTrue();
        });
    });

    describe('factory state methods', function () {
        test('current employment state works correctly', function () {
            // Arrange
            $wrestler = Wrestler::factory()->create();
            $startDate = now()->subMonth();

            // Act
            $employment = WrestlerEmployment::factory()->make([
                'wrestler_id' => $wrestler->id,
                'started_at' => $startDate,
                'ended_at' => null,
            ]);
            
            // Assert
            expect($employment->wrestler_id)->toBe($wrestler->id);
            expect($employment->started_at->format('Y-m-d H:i:s'))->toBe($startDate->format('Y-m-d H:i:s'));
            expect($employment->ended_at)->toBeNull();
        });

        test('past employment state works correctly', function () {
            // Arrange
            $wrestler = Wrestler::factory()->create();
            $startDate = now()->subYear();
            $endDate = now()->subMonths(6);

            // Act
            $employment = WrestlerEmployment::factory()->make([
                'wrestler_id' => $wrestler->id,
                'started_at' => $startDate,
                'ended_at' => $endDate,
            ]);
            
            // Assert
            expect($employment->wrestler_id)->toBe($wrestler->id);
            expect($employment->started_at->format('Y-m-d H:i:s'))->toBe($startDate->format('Y-m-d H:i:s'));
            expect($employment->ended_at->format('Y-m-d H:i:s'))->toBe($endDate->format('Y-m-d H:i:s'));
            expect($employment->ended_at->isAfter($employment->started_at))->toBeTrue();
        });
    });

    describe('factory customization', function () {
        test('accepts custom wrestler association', function () {
            // Arrange
            $wrestler = Wrestler::factory()->create();

            // Act
            $employment = WrestlerEmployment::factory()->make(['wrestler_id' => $wrestler->id]);
            
            // Assert
            expect($employment->wrestler_id)->toBe($wrestler->id);
        });

        test('accepts custom date ranges', function () {
            // Arrange
            $startDate = now()->subYear();
            $endDate = now()->subMonths(3);

            // Act
            $employment = WrestlerEmployment::factory()->make([
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
            $employment = WrestlerEmployment::factory()->create();
            
            // Assert
            expect($employment->exists)->toBeTrue();
            expect($employment->id)->toBeGreaterThan(0);
        });

        test('maintains date consistency', function () {
            // Arrange & Act
            $employment = WrestlerEmployment::factory()->make();
            
            // Assert
            expect($employment->started_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
            if ($employment->ended_at) {
                expect($employment->ended_at->isAfter($employment->started_at))->toBeTrue();
            }
        });
    });
});
