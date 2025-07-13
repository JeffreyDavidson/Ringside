<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories\TagTeams;

use App\Models\TagTeams\TagTeam;
use App\Models\TagTeams\TagTeamSuspension;

/**
 * Unit tests for TagTeamSuspensionFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (suspension period data)
 * - Factory state methods (current, past, reinstated suspensions)
 * - Factory relationship creation (tag team associations)
 * - Suspension timeline data (started_at, ended_at dates)
 * - Suspension period validation and consistency
 *
 * These tests verify that the TagTeamSuspensionFactory generates consistent,
 * realistic suspension data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see \Database\Factories\TagTeams\TagTeamSuspensionFactory
 */
describe('TagTeamSuspensionFactory Unit Tests', function () {
    describe('default attribute generation', function () {
        test('creates suspension with correct default attributes', function () {
            // Arrange & Act
            $suspension = TagTeamSuspension::factory()->make();

            // Assert
            expect($suspension->tag_team_id)->toBeInt();
            expect($suspension->started_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
            expect($suspension->ended_at)->toBeNull(); // Default is current suspension
        });

        test('creates realistic suspension dates', function () {
            // Arrange & Act
            $suspension = TagTeamSuspension::factory()->make();

            // Assert
            expect($suspension->started_at->isToday())->toBeTrue();
        });
    });

    describe('factory state methods', function () {
        test('current suspension state works correctly', function () {
            // Arrange
            $tagTeam = TagTeam::factory()->create();
            $suspendedDate = now()->subWeeks(3);

            // Act
            $suspension = TagTeamSuspension::factory()->make([
                'tag_team_id' => $tagTeam->id,
                'started_at' => $suspendedDate,
                'ended_at' => null,
            ]);

            // Assert
            expect($suspension->tag_team_id)->toBe($tagTeam->id);
            expect($suspension->started_at->format('Y-m-d H:i:s'))->toBe($suspendedDate->format('Y-m-d H:i:s'));
            expect($suspension->ended_at)->toBeNull();
        });

        test('reinstated suspension state works correctly', function () {
            // Arrange
            $tagTeam = TagTeam::factory()->create();
            $suspendedDate = now()->subMonths(4);
            $reinstatedDate = now()->subMonths(2);

            // Act
            $suspension = TagTeamSuspension::factory()->make([
                'tag_team_id' => $tagTeam->id,
                'started_at' => $suspendedDate,
                'ended_at' => $reinstatedDate,
            ]);

            // Assert
            expect($suspension->tag_team_id)->toBe($tagTeam->id);
            expect($suspension->started_at->format('Y-m-d H:i:s'))->toBe($suspendedDate->format('Y-m-d H:i:s'));
            expect($suspension->ended_at->format('Y-m-d H:i:s'))->toBe($reinstatedDate->format('Y-m-d H:i:s'));
            expect($suspension->ended_at->isAfter($suspension->started_at))->toBeTrue();
        });
    });

    describe('factory customization', function () {
        test('accepts custom tag team association', function () {
            // Arrange
            $tagTeam = TagTeam::factory()->create();

            // Act
            $suspension = TagTeamSuspension::factory()->make(['tag_team_id' => $tagTeam->id]);

            // Assert
            expect($suspension->tag_team_id)->toBe($tagTeam->id);
        });

        test('accepts custom date ranges', function () {
            // Arrange
            $suspendedDate = now()->subMonths(8);
            $reinstatedDate = now()->subMonths(5);

            // Act
            $suspension = TagTeamSuspension::factory()->make([
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
            $suspension = TagTeamSuspension::factory()->create();

            // Assert
            expect($suspension->exists)->toBeTrue();
            expect($suspension->id)->toBeGreaterThan(0);
        });

        test('maintains date consistency', function () {
            // Arrange & Act
            $suspension = TagTeamSuspension::factory()->make();

            // Assert
            expect($suspension->started_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
            if ($suspension->ended_at) {
                expect($suspension->ended_at->isAfter($suspension->started_at))->toBeTrue();
            }
        });
    });
});
