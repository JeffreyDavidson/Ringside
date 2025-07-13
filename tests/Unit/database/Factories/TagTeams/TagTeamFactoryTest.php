<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories\TagTeams;

use App\Enums\Shared\EmploymentStatus;
use App\Models\TagTeams\TagTeam;

/**
 * Unit tests for TagTeamFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (realistic data patterns)
 * - Factory state methods (employed, unemployed, etc.)
 * - Custom factory methods and configurations
 * - Data consistency and business rule compliance
 *
 * These tests verify that the TagTeamFactory generates consistent,
 * realistic test data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see \Database\Factories\TagTeams\TagTeamFactory
 */
describe('TagTeamFactory Unit Tests', function () {
    describe('default attribute generation', function () {
        test('creates tag team with correct default attributes', function () {
            // Arrange & Act
            $tagTeam = TagTeam::factory()->make();
            
            // Assert
            expect($tagTeam->name)->toBeString();
            expect($tagTeam->name)->not->toBeEmpty();
            expect($tagTeam->status)->toBeInstanceOf(EmploymentStatus::class);
        });

        test('generates realistic tag team names', function () {
            // Arrange & Act
            $tagTeam = TagTeam::factory()->make();
            
            // Assert
            expect($tagTeam->name)->toBeString();
            expect(strlen($tagTeam->name))->toBeGreaterThan(3);
        });
    });

    describe('factory state methods', function () {
        test('employed state works correctly', function () {
            // Arrange & Act
            $tagTeam = TagTeam::factory()->employed()->create();
            
            // Assert
            expect($tagTeam->isEmployed())->toBeTrue();
        });

        test('unemployed state works correctly', function () {
            // Arrange & Act
            $tagTeam = TagTeam::factory()->unemployed()->create();
            
            // Assert
            expect($tagTeam->isEmployed())->toBeFalse();
        });

        test('retired state works correctly', function () {
            // Arrange & Act
            $tagTeam = TagTeam::factory()->retired()->create();
            
            // Assert
            expect($tagTeam->isRetired())->toBeTrue();
        });
    });

    describe('factory customization', function () {
        test('accepts custom attribute overrides', function () {
            // Arrange & Act
            $tagTeam = TagTeam::factory()->make([
                'name' => 'Custom Tag Team',
                'status' => EmploymentStatus::Employed,
            ]);
            
            // Assert
            expect($tagTeam->name)->toBe('Custom Tag Team');
            expect($tagTeam->status)->toBe(EmploymentStatus::Employed);
        });

        test('maintains required attributes when overriding', function () {
            // Arrange & Act
            $tagTeam = TagTeam::factory()->make([
                'name' => 'Override Team',
            ]);
            
            // Assert
            expect($tagTeam->name)->toBe('Override Team');
            expect($tagTeam->status)->toBeInstanceOf(EmploymentStatus::class);
        });
    });

    describe('data consistency', function () {
        test('generates unique tag team names', function () {
            // Arrange & Act
            $tagTeam1 = TagTeam::factory()->make();
            $tagTeam2 = TagTeam::factory()->make();
            
            // Assert
            expect($tagTeam1->name)->not->toBe($tagTeam2->name);
        });

        test('database creation works correctly', function () {
            // Arrange & Act
            $tagTeam = TagTeam::factory()->create();
            
            // Assert
            expect($tagTeam->exists)->toBeTrue();
            expect($tagTeam->id)->toBeGreaterThan(0);
        });
    });
});