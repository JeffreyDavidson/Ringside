<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories\Stables;

use App\Models\Stables\Stable;

/**
 * Unit tests for StableFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (realistic data patterns)
 * - Factory state methods (active, inactive, etc.)
 * - Custom factory methods and configurations
 * - Data consistency and business rule compliance
 *
 * These tests verify that the StableFactory generates consistent,
 * realistic test data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see \Database\Factories\Stables\StableFactory
 */
describe('StableFactory Unit Tests', function () {
    describe('default attribute generation', function () {
        test('creates stable with correct default attributes', function () {
            // Arrange & Act
            $stable = Stable::factory()->make();
            
            // Assert
            expect($stable->name)->toBeString();
            expect($stable->name)->not->toBeEmpty();
        });

        test('generates realistic stable names', function () {
            // Arrange & Act
            $stable = Stable::factory()->make();
            
            // Assert
            expect($stable->name)->toBeString();
            expect(strlen($stable->name))->toBeGreaterThan(3);
        });
    });

    describe('factory state methods', function () {
        test('active state works correctly', function () {
            // Arrange & Act
            $stable = Stable::factory()->active()->create();
            
            // Assert
            expect($stable->isCurrentlyActive())->toBeTrue();
        });

        test('inactive state works correctly', function () {
            // Arrange & Act
            $stable = Stable::factory()->inactive()->create();
            
            // Assert
            expect($stable->isCurrentlyActive())->toBeFalse();
        });

        test('retired state works correctly', function () {
            // Arrange & Act
            $stable = Stable::factory()->retired()->create();
            
            // Assert
            expect($stable->isRetired())->toBeTrue();
        });
    });

    describe('factory customization', function () {
        test('accepts custom attribute overrides', function () {
            // Arrange & Act
            $stable = Stable::factory()->make([
                'name' => 'Custom Stable Name',
            ]);
            
            // Assert
            expect($stable->name)->toBe('Custom Stable Name');
        });

        test('maintains required attributes when overriding', function () {
            // Arrange & Act
            $stable = Stable::factory()->make([
                'name' => 'Override Stable',
            ]);
            
            // Assert
            expect($stable->name)->toBe('Override Stable');
        });
    });

    describe('data consistency', function () {
        test('generates unique stable names', function () {
            // Arrange & Act
            $stable1 = Stable::factory()->make();
            $stable2 = Stable::factory()->make();
            
            // Assert
            expect($stable1->name)->not->toBe($stable2->name);
        });

        test('database creation works correctly', function () {
            // Arrange & Act
            $stable = Stable::factory()->create();
            
            // Assert
            expect($stable->exists)->toBeTrue();
            expect($stable->id)->toBeGreaterThan(0);
        });
    });
});