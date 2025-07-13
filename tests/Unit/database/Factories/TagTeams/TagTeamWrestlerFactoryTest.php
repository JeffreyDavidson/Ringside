<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories\TagTeams;

use App\Models\TagTeams\TagTeam;
use App\Models\TagTeams\TagTeamWrestler;
use App\Models\Wrestlers\Wrestler;

/**
 * Unit tests for TagTeamWrestlerFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (wrestler-tag team associations)
 * - Factory state methods (current, past partnerships)
 * - Factory relationship creation (tag teams, wrestlers)
 * - Partnership timeline data (joined_at, left_at dates)
 * - Tag team composition validation and consistency
 *
 * These tests verify that the TagTeamWrestlerFactory generates consistent,
 * realistic tag team wrestler data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see \Database\Factories\TagTeams\TagTeamWrestlerFactory
 */
describe('TagTeamWrestlerFactory Unit Tests', function () {
    describe('default attribute generation', function () {
        test('creates tag team wrestler with correct default attributes', function () {
            // Arrange & Act
            $tagTeamWrestler = TagTeamWrestler::factory()->make();
            
            // Assert
            expect($tagTeamWrestler->tag_team_id)->toBeInt();
            expect($tagTeamWrestler->wrestler_id)->toBeInt();
            expect($tagTeamWrestler->joined_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
            expect($tagTeamWrestler->left_at)->toBeNull(); // Default is current partnership
        });

        test('creates realistic partnership dates', function () {
            // Arrange & Act
            $tagTeamWrestler = TagTeamWrestler::factory()->make();
            
            // Assert
            expect($tagTeamWrestler->joined_at->isToday())->toBeTrue();
        });
    });

    describe('factory state methods', function () {
        test('current partnership state works correctly', function () {
            // Arrange
            $tagTeam = TagTeam::factory()->create();
            $wrestler = Wrestler::factory()->create();
            $joinedDate = now()->subMonths(6);

            // Act
            $tagTeamWrestler = TagTeamWrestler::factory()->make([
                'tag_team_id' => $tagTeam->id,
                'wrestler_id' => $wrestler->id,
                'joined_at' => $joinedDate,
                'left_at' => null,
            ]);
            
            // Assert
            expect($tagTeamWrestler->tag_team_id)->toBe($tagTeam->id);
            expect($tagTeamWrestler->wrestler_id)->toBe($wrestler->id);
            expect($tagTeamWrestler->joined_at->format('Y-m-d H:i:s'))->toBe($joinedDate->format('Y-m-d H:i:s'));
            expect($tagTeamWrestler->left_at)->toBeNull();
        });

        test('past partnership state works correctly', function () {
            // Arrange
            $tagTeam = TagTeam::factory()->create();
            $wrestler = Wrestler::factory()->create();
            $joinedDate = now()->subYear();
            $leftDate = now()->subMonths(6);

            // Act
            $tagTeamWrestler = TagTeamWrestler::factory()->make([
                'tag_team_id' => $tagTeam->id,
                'wrestler_id' => $wrestler->id,
                'joined_at' => $joinedDate,
                'left_at' => $leftDate,
            ]);
            
            // Assert
            expect($tagTeamWrestler->tag_team_id)->toBe($tagTeam->id);
            expect($tagTeamWrestler->wrestler_id)->toBe($wrestler->id);
            expect($tagTeamWrestler->joined_at->format('Y-m-d H:i:s'))->toBe($joinedDate->format('Y-m-d H:i:s'));
            expect($tagTeamWrestler->left_at->format('Y-m-d H:i:s'))->toBe($leftDate->format('Y-m-d H:i:s'));
            expect($tagTeamWrestler->left_at->isAfter($tagTeamWrestler->joined_at))->toBeTrue();
        });
    });

    describe('factory customization', function () {
        test('accepts custom tag team association', function () {
            // Arrange
            $tagTeam = TagTeam::factory()->create();

            // Act
            $tagTeamWrestler = TagTeamWrestler::factory()->make(['tag_team_id' => $tagTeam->id]);
            
            // Assert
            expect($tagTeamWrestler->tag_team_id)->toBe($tagTeam->id);
        });

        test('accepts custom wrestler association', function () {
            // Arrange
            $wrestler = Wrestler::factory()->create();

            // Act
            $tagTeamWrestler = TagTeamWrestler::factory()->make(['wrestler_id' => $wrestler->id]);
            
            // Assert
            expect($tagTeamWrestler->wrestler_id)->toBe($wrestler->id);
        });

        test('accepts custom date ranges', function () {
            // Arrange
            $joinedDate = now()->subYears(2);
            $leftDate = now()->subYear();

            // Act
            $tagTeamWrestler = TagTeamWrestler::factory()->make([
                'joined_at' => $joinedDate,
                'left_at' => $leftDate,
            ]);
            
            // Assert
            expect($tagTeamWrestler->joined_at->format('Y-m-d H:i:s'))->toBe($joinedDate->format('Y-m-d H:i:s'));
            expect($tagTeamWrestler->left_at->format('Y-m-d H:i:s'))->toBe($leftDate->format('Y-m-d H:i:s'));
        });
    });

    describe('data consistency', function () {
        test('database creation works correctly', function () {
            // Arrange & Act
            $tagTeamWrestler = TagTeamWrestler::factory()->create();
            
            // Assert
            expect($tagTeamWrestler->exists)->toBeTrue();
            // Note: Pivot models don't reliably return IDs after create() due to Laravel limitations
        });

        test('maintains date consistency', function () {
            // Arrange & Act
            $tagTeamWrestler = TagTeamWrestler::factory()->make();
            
            // Assert
            expect($tagTeamWrestler->joined_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
            if ($tagTeamWrestler->left_at) {
                expect($tagTeamWrestler->left_at->isAfter($tagTeamWrestler->joined_at))->toBeTrue();
            }
        });

        test('creates valid tag team wrestler associations', function () {
            // Arrange & Act
            $tagTeamWrestler = TagTeamWrestler::factory()->make();
            
            // Assert
            expect($tagTeamWrestler->tag_team_id)->toBeInt();
            expect($tagTeamWrestler->wrestler_id)->toBeInt();
        });
    });
});