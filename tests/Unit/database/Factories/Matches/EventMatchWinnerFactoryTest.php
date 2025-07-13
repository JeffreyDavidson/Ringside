<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories\Matches;

use App\Models\Matches\EventMatch;
use App\Models\Matches\EventMatchWinner;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;

/**
 * Unit tests for EventMatchWinnerFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (winner associations)
 * - Factory state methods (different winner types)
 * - Factory relationship creation (matches, wrestlers, tag teams)
 * - Polymorphic winner data (wrestler vs tag team winners)
 * - Match result configuration and consistency
 *
 * These tests verify that the EventMatchWinnerFactory generates consistent,
 * realistic winner data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see \Database\Factories\Matches\EventMatchWinnerFactory
 */
describe('EventMatchWinnerFactory Unit Tests', function () {
    describe('default attribute generation', function () {
        test('creates winner with correct default attributes', function () {
            // Arrange & Act
            $winner = EventMatchWinner::factory()->make();

            // Assert
            expect($winner->event_match_result_id)->toBeInt();
            expect($winner->winner_id)->toBeInt();
            expect($winner->winner_type)->toBeString();
            expect($winner->winner_type)->toBeIn(['wrestler', 'tag_team']);
        });

        test('generates single winner by default', function () {
            // Arrange & Act
            $winner = EventMatchWinner::factory()->make();

            // Assert
            expect($winner->event_match_result_id)->toBeInt();
            expect($winner->winner_id)->toBeInt();
        });
    });

    describe('factory state methods', function () {
        test('wrestler winner state works correctly', function () {
            // Arrange
            $match = EventMatch::factory()->create();
            $wrestler = Wrestler::factory()->create();

            // Act
            $winner = EventMatchWinner::factory()->make([
                'event_match_id' => $match->id,
                'winner_id' => $wrestler->id,
                'winner_type' => 'wrestler',
            ]);

            // Assert
            expect($winner->event_match_id)->toBe($match->id);
            expect($winner->winner_id)->toBe($wrestler->id);
            expect($winner->winner_type)->toBe('wrestler');
        });

        test('tag team winner state works correctly', function () {
            // Arrange
            $match = EventMatch::factory()->create();
            $tagTeam = TagTeam::factory()->create();

            // Act
            $winner = EventMatchWinner::factory()->make([
                'event_match_id' => $match->id,
                'winner_id' => $tagTeam->id,
                'winner_type' => 'tag_team',
            ]);

            // Assert
            expect($winner->event_match_id)->toBe($match->id);
            expect($winner->winner_id)->toBe($tagTeam->id);
            expect($winner->winner_type)->toBe('tag_team');
        });
    });

    describe('factory customization', function () {
        test('accepts custom match association', function () {
            // Arrange
            $match = EventMatch::factory()->create();

            // Act
            $winner = EventMatchWinner::factory()->make(['event_match_id' => $match->id]);

            // Assert
            expect($winner->event_match_id)->toBe($match->id);
        });

        test('accepts custom winner configuration', function () {
            // Arrange
            $wrestler = Wrestler::factory()->create();

            // Act
            $winner = EventMatchWinner::factory()->make([
                'winner_id' => $wrestler->id,
                'winner_type' => 'wrestler',
            ]);

            // Assert
            expect($winner->winner_id)->toBe($wrestler->id);
            expect($winner->winner_type)->toBe('wrestler');
        });

        test('handles no contest scenarios', function () {
            // Arrange
            $match = EventMatch::factory()->create();

            // Act
            $winner = EventMatchWinner::factory()->make([
                'event_match_id' => $match->id,
                'winner_id' => null,
                'winner_type' => null,
            ]);

            // Assert
            expect($winner->event_match_id)->toBe($match->id);
            expect($winner->winner_id)->toBeNull();
            expect($winner->winner_type)->toBeNull();
        });
    });

    describe('data consistency', function () {
        test('database creation works correctly', function () {
            // Arrange & Act
            $winner = EventMatchWinner::factory()->create();

            // Assert
            expect($winner->exists)->toBeTrue();
            expect($winner->id)->toBeGreaterThan(0);
        });

        test('maintains valid winner types', function () {
            // Arrange & Act
            $winners = collect(range(1, 5))->map(fn() => EventMatchWinner::factory()->make());

            // Assert
            foreach ($winners as $winner) {
                if ($winner->winner_type) {
                    expect($winner->winner_type)->toBeIn(['wrestler', 'tag_team']);
                }
            }
        });

        test('creates winners with valid match result associations', function () {
            // Arrange & Act
            $winner = EventMatchWinner::factory()->make();

            // Assert
            expect($winner->event_match_result_id)->toBeInt();
        });
    });
});
