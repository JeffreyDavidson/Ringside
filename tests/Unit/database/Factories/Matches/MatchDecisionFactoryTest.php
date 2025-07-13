<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories\Matches;

use App\Models\Matches\MatchDecision;

/**
 * Unit tests for MatchDecisionFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (realistic data patterns)
 * - Factory state methods and configurations
 * - Custom factory methods and configurations
 * - Data consistency and business rule compliance
 *
 * These tests verify that the MatchDecisionFactory generates consistent,
 * realistic test data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see \Database\Factories\Matches\MatchDecisionFactory
 */
describe('MatchDecisionFactory Unit Tests', function () {
    describe('default attribute generation', function () {
        test('creates match decision with correct default attributes', function () {
            // Arrange & Act
            $matchDecision = MatchDecision::factory()->make();
            
            // Assert
            expect($matchDecision->name)->toBeString();
            expect($matchDecision->name)->not->toBeEmpty();
        });

        test('generates realistic match decision names', function () {
            // Arrange & Act
            $matchDecision = MatchDecision::factory()->make();
            
            // Assert
            expect($matchDecision->name)->toBeString();
            expect(strlen($matchDecision->name))->toBeGreaterThan(2);
        });
    });

    describe('factory customization', function () {
        test('accepts custom attribute overrides', function () {
            // Arrange & Act
            $matchDecision = MatchDecision::factory()->make([
                'name' => 'Custom Decision',
            ]);
            
            // Assert
            expect($matchDecision->name)->toBe('Custom Decision');
        });

        test('maintains required attributes when overriding', function () {
            // Arrange & Act
            $matchDecision = MatchDecision::factory()->make([
                'name' => 'Override Decision',
            ]);
            
            // Assert
            expect($matchDecision->name)->toBe('Override Decision');
        });
    });

    describe('data consistency', function () {
        test('generates unique match decision names', function () {
            // Arrange & Act
            $decision1 = MatchDecision::factory()->make();
            $decision2 = MatchDecision::factory()->make();
            
            // Assert
            expect($decision1->name)->not->toBe($decision2->name);
        });

        test('database creation works correctly', function () {
            // Arrange & Act
            $matchDecision = MatchDecision::factory()->create();
            
            // Assert
            expect($matchDecision->exists)->toBeTrue();
            expect($matchDecision->id)->toBeGreaterThan(0);
        });
    });
});