<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Enums\Titles\TitleStatus;
use App\Enums\Titles\TitleType;
use App\Models\Titles\Title;
use Database\Factories\Titles\TitleFactory;

/**
 * Unit tests for TitleFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (realistic data patterns)
 * - Factory state methods (active, inactive, retired, etc.)
 * - Custom factory methods and configurations
 * - Data consistency and business rule compliance
 *
 * These tests verify that the TitleFactory generates consistent,
 * realistic test data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see TitleFactory
 */
describe('TitleFactory Unit Tests', function () {
    describe('default attribute generation', function () {
        test('creates title with correct default attributes', function () {
            // Arrange & Act
            $title = Title::factory()->make();

            // Assert
            expect($title->name)->toBeString();
            expect($title->name)->toEndWith($title->type === TitleType::Singles ? 'Title' : 'Titles');
            expect($title->status)->toBeInstanceOf(TitleStatus::class);
            expect($title->type)->toBeInstanceOf(TitleType::class);
        });

        test('generates realistic singles title names', function () {
            // Arrange & Act
            $title = Title::factory()->singles()->make();

            // Assert
            expect($title->name)->toBeString();
            expect(mb_strlen($title->name))->toBeGreaterThan(5);
            expect($title->name)->toEndWith('Title');
        });

        test('generates realistic tag team title names', function () {
            // Arrange & Act
            $title = Title::factory()->tagTeam()->make();

            // Assert
            expect($title->name)->toBeString();
            expect(mb_strlen($title->name))->toBeGreaterThan(6);
            expect($title->name)->toEndWith('Titles');
        });
    });

    describe('factory state methods', function () {
        test('unactivated state works correctly', function () {
            // Arrange & Act
            $title = Title::factory()->unactivated()->create();

            // Assert
            expect($title->status)->toBe(TitleStatus::Undebuted);
            expect($title->activityPeriods)->toBeEmpty();
        });

        test('active state works correctly', function () {
            // Arrange & Act
            $title = Title::factory()->active()->create();

            // Assert
            $title->load('currentActivityPeriod');
            expect($title->currentActivityPeriod)->not->toBeNull();
            expect($title->currentActivityPeriod)->not->toBeNull()->ended_at->toBeNull();
        });

        test('inactive state works correctly', function () {
            // Arrange & Act
            $title = Title::factory()->inactive()->create();

            // Assert
            $title->load(['activityPeriods', 'currentActivityPeriod']);
            expect($title->activityPeriods)->not->toBeEmpty();
            expect($title->currentActivityPeriod)->toBeNull();
        });

        test('retired state works correctly', function () {
            // Arrange & Act
            $title = Title::factory()->retired()->create();

            // Assert
            $title->load('currentRetirement');
            expect($title->currentRetirement)->not->toBeNull();
        });
    });

    describe('factory customization', function () {
        test('accepts custom status values', function () {
            // Arrange & Act - Use factory state methods since status is computed from relationships
            // Note: Activity status requires persisted relationships, so use create() instead of make()
            $undebutedTitle = Title::factory()->create(); // Default is undebuted
            $activeTitle = Title::factory()->active()->create();

            // Assert
            expect($undebutedTitle->status)->toBe(TitleStatus::Undebuted);
            expect($activeTitle->status)->toBe(TitleStatus::Active);
        });

        test('accepts custom title types', function () {
            // Arrange & Act
            $singlesTitle = Title::factory()->make(['type' => TitleType::Singles]);
            $tagTeamTitle = Title::factory()->make(['type' => TitleType::TagTeam]);

            // Assert
            expect($singlesTitle->type)->toBe(TitleType::Singles);
            expect($tagTeamTitle->type)->toBe(TitleType::TagTeam);
        });

        test('accepts custom attribute overrides', function () {
            // Arrange & Act
            $title = Title::factory()->make([
                'name' => 'Custom Championship',
                'type' => TitleType::Singles,
                'status' => TitleStatus::Active,
            ]);

            // Assert
            expect($title->name)->toBe('Custom Championship');
            expect($title->type)->toBe(TitleType::Singles);
            expect($title->status)->toBe(TitleStatus::Undebuted);
        });
    });

    describe('data consistency', function () {
        test('generates unique title names', function () {
            // Arrange & Act
            $title1 = Title::factory()->make();
            $title2 = Title::factory()->make();

            // Assert
            expect($title1->name)->not->toBe($title2->name);
        });

        test('database creation works correctly', function () {
            // Arrange & Act
            $title = Title::factory()->create();

            // Assert
            expect($title->exists)->toBeTrue();
            expect($title->id)->toBeGreaterThan(0);
        });
    });
});
