<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories\Matches;

use App\Models\Matches\MatchType;

/**
 * Unit tests for MatchTypeFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (realistic match type data)
 * - Factory state methods and configurations
 * - Custom factory methods and configurations
 * - Data consistency and business rule compliance
 *
 * These tests verify that the MatchTypeFactory generates consistent,
 * realistic test data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see \Database\Factories\Matches\MatchTypeFactory
 */
describe('MatchTypeFactory Unit Tests', function () {
    describe('default attribute generation', function () {
        test('creates match type with correct default attributes', function () {
            // Arrange & Act
            $matchType = MatchType::factory()->make();
            
            // Assert
            expect($matchType->name)->toBeString();
            expect($matchType->name)->not->toBeEmpty();
        });

        test('generates realistic match type names', function () {
            // Arrange & Act
            $matchType = MatchType::factory()->make();
            
            // Assert
            expect($matchType->name)->toBeString();
            expect(strlen($matchType->name))->toBeGreaterThan(3);
        });
    });

    describe('factory customization', function () {
        test('accepts custom attribute overrides', function () {
            // Arrange & Act
            $matchType = MatchType::factory()->make([
                'name' => 'Custom Match Type',
            ]);
            
            // Assert
            expect($matchType->name)->toBe('Custom Match Type');
        });

        test('maintains required attributes when overriding', function () {
            // Arrange & Act
            $matchType = MatchType::factory()->make([
                'name' => 'Override Type',
            ]);
            
            // Assert
            expect($matchType->name)->toBe('Override Type');
        });
    });

    describe('data consistency', function () {
        test('generates unique match type names', function () {
            // Arrange & Act
            $matchType1 = MatchType::factory()->make();
            $matchType2 = MatchType::factory()->make();
            
            // Assert
            expect($matchType1->name)->not->toBe($matchType2->name);
        });

        test('database creation works correctly', function () {
            // Arrange & Act
            $matchType = MatchType::factory()->create();
            
            // Assert
            expect($matchType->exists)->toBeTrue();
            expect($matchType->id)->toBeGreaterThan(0);
        });

        test('generates consistent data format', function () {
            // Arrange & Act
            $matchTypes = collect(range(1, 5))->map(fn() => MatchType::factory()->make());
            
            // Assert
            foreach ($matchTypes as $matchType) {
                expect($matchType->name)->toBeString();
                expect($matchType->name)->not->toBeEmpty();
            }
        });
    });
});