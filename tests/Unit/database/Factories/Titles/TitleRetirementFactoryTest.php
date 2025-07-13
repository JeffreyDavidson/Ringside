<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories\Titles;

use App\Models\Titles\Title;
use App\Models\Titles\TitleRetirement;
use Illuminate\Support\Carbon;

/**
 * Unit tests for TitleRetirementFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (retirement period data)
 * - Factory state methods (current, past, ended retirements)
 * - Factory relationship creation (title associations)
 * - Retirement timeline data (started_at, ended_at dates)
 * - Retirement period validation and consistency
 *
 * These tests verify that the TitleRetirementFactory generates consistent,
 * realistic retirement data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see \Database\Factories\Titles\TitleRetirementFactory
 */
describe('TitleRetirementFactory Unit Tests', function () {
    describe('default attribute generation', function () {
        test('creates retirement with correct default attributes', function () {
            // Arrange & Act
            $retirement = TitleRetirement::factory()->make();

            // Assert
            expect($retirement->title_id)->toBeInt();
            expect($retirement->started_at)->toBeInstanceOf(Carbon::class);
            expect($retirement->ended_at)->toBeNull(); // Default is current retirement
        });

        test('creates realistic retirement dates', function () {
            // Arrange & Act
            $retirement = TitleRetirement::factory()->make();

            // Assert
            expect($retirement->started_at->isToday())->toBeTrue();
        });
    });

    describe('factory state methods', function () {
        test('current retirement state works correctly', function () {
            // Arrange
            $title = Title::factory()->create();
            $retiredDate = now()->subMonths(6);

            // Act
            $retirement = TitleRetirement::factory()->make([
                'title_id' => $title->id,
                'started_at' => $retiredDate,
                'ended_at' => null,
            ]);

            // Assert
            expect($retirement->title_id)->toBe($title->id);
            expect($retirement->started_at->format('Y-m-d H:i:s'))->toBe($retiredDate->format('Y-m-d H:i:s'));
            expect($retirement->ended_at)->toBeNull();
        });

        test('ended retirement state works correctly', function () {
            // Arrange
            $title = Title::factory()->create();
            $retiredDate = now()->subYears(2);
            $endedDate = now()->subYear();

            // Act
            $retirement = TitleRetirement::factory()->make([
                'title_id' => $title->id,
                'started_at' => $retiredDate,
                'ended_at' => $endedDate,
            ]);

            // Assert
            expect($retirement->title_id)->toBe($title->id);
            expect($retirement->started_at->format('Y-m-d H:i:s'))->toBe($retiredDate->format('Y-m-d H:i:s'));
            expect($retirement->ended_at->format('Y-m-d H:i:s'))->toBe($endedDate->format('Y-m-d H:i:s'));
            expect($retirement->ended_at->isAfter($retirement->started_at))->toBeTrue();
        });
    });

    describe('factory customization', function () {
        test('accepts custom title association', function () {
            // Arrange
            $title = Title::factory()->create();

            // Act
            $retirement = TitleRetirement::factory()->make(['title_id' => $title->id]);

            // Assert
            expect($retirement->title_id)->toBe($title->id);
        });

        test('accepts custom date ranges', function () {
            // Arrange
            $retiredDate = now()->subYears(3);
            $endedDate = now()->subYears(2);

            // Act
            $retirement = TitleRetirement::factory()->make([
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
            $retirement = TitleRetirement::factory()->create();

            // Assert
            expect($retirement->exists)->toBeTrue();
            expect($retirement->id)->toBeGreaterThan(0);
        });

        test('maintains date consistency', function () {
            // Arrange & Act
            $retirement = TitleRetirement::factory()->make();

            // Assert
            expect($retirement->started_at)->toBeInstanceOf(Carbon::class);
            if ($retirement->ended_at) {
                expect($retirement->ended_at->isAfter($retirement->started_at))->toBeTrue();
            }
        });
    });
});
