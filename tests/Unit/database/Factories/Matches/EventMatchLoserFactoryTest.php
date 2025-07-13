<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories\Matches;

use App\Models\Matches\EventMatchLoser;
use App\Models\Matches\EventMatchResult;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;

/**
 * Unit tests for EventMatchLoserFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (loser associations)
 * - Factory state methods (different loser types)
 * - Factory relationship creation (match results, wrestlers, tag teams)
 * - Polymorphic loser data (wrestler vs tag team losers)
 * - Match loser configuration and consistency
 *
 * These tests verify that the EventMatchLoserFactory generates consistent,
 * realistic loser data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see \Database\Factories\Matches\EventMatchLoserFactory
 */
describe('EventMatchLoserFactory Unit Tests', function () {
    describe('default attribute generation', function () {
        test('creates loser with correct default attributes', function () {
            // Arrange & Act
            $loser = EventMatchLoser::factory()->make();
            
            // Assert
            expect($loser->event_match_result_id)->toBeInt();
            expect($loser->loser_id)->toBeInt();
            expect($loser->loser_type)->toBeString();
            expect($loser->loser_type)->toBe('wrestler');
        });

        test('creates realistic loser associations', function () {
            // Arrange & Act
            $loser = EventMatchLoser::factory()->make();
            
            // Assert
            expect($loser->loser_type)->toBe('wrestler');
            expect($loser->loser_id)->toBeInt();
        });
    });

    describe('factory state methods', function () {
        test('wrestler loser state works correctly', function () {
            // Arrange
            $result = EventMatchResult::factory()->create();
            $wrestler = Wrestler::factory()->create();

            // Act
            $loser = EventMatchLoser::factory()->make([
                'event_match_result_id' => $result->id,
                'loser_id' => $wrestler->id,
                'loser_type' => 'wrestler',
            ]);
            
            // Assert
            expect($loser->event_match_result_id)->toBe($result->id);
            expect($loser->loser_id)->toBe($wrestler->id);
            expect($loser->loser_type)->toBe('wrestler');
        });

        test('tag team loser state works correctly', function () {
            // Arrange
            $result = EventMatchResult::factory()->create();
            $tagTeam = TagTeam::factory()->create();

            // Act
            $loser = EventMatchLoser::factory()->make([
                'event_match_result_id' => $result->id,
                'loser_id' => $tagTeam->id,
                'loser_type' => 'tag_team',
            ]);
            
            // Assert
            expect($loser->event_match_result_id)->toBe($result->id);
            expect($loser->loser_id)->toBe($tagTeam->id);
            expect($loser->loser_type)->toBe('tag_team');
        });
    });

    describe('factory customization', function () {
        test('accepts custom match result association', function () {
            // Arrange
            $result = EventMatchResult::factory()->create();

            // Act
            $loser = EventMatchLoser::factory()->make(['event_match_result_id' => $result->id]);
            
            // Assert
            expect($loser->event_match_result_id)->toBe($result->id);
        });

        test('accepts custom loser configuration', function () {
            // Arrange
            $wrestler = Wrestler::factory()->create();

            // Act
            $loser = EventMatchLoser::factory()->make([
                'loser_id' => $wrestler->id,
                'loser_type' => 'wrestler',
            ]);
            
            // Assert
            expect($loser->loser_id)->toBe($wrestler->id);
            expect($loser->loser_type)->toBe('wrestler');
        });

        test('accepts tag team loser configuration', function () {
            // Arrange
            $tagTeam = TagTeam::factory()->create();

            // Act
            $loser = EventMatchLoser::factory()->make([
                'loser_id' => $tagTeam->id,
                'loser_type' => 'tag_team',
            ]);
            
            // Assert
            expect($loser->loser_id)->toBe($tagTeam->id);
            expect($loser->loser_type)->toBe('tag_team');
        });
    });

    describe('data consistency', function () {
        test('database creation works correctly', function () {
            // Arrange & Act
            $loser = EventMatchLoser::factory()->create();
            
            // Assert
            expect($loser->exists)->toBeTrue();
            // Note: Pivot models don't reliably return IDs after create() due to Laravel limitations
        });

        test('maintains valid loser types', function () {
            // Arrange & Act
            $losers = collect(range(1, 3))->map(fn() => EventMatchLoser::factory()->make());
            
            // Assert
            foreach ($losers as $loser) {
                expect($loser->loser_type)->toBe('wrestler');
                expect($loser->loser_id)->toBeInt();
            }
        });

        test('creates losers with valid match result associations', function () {
            // Arrange & Act
            $loser = EventMatchLoser::factory()->make();
            
            // Assert
            expect($loser->event_match_result_id)->toBeInt();
            expect($loser->loser_id)->toBeInt();
            expect($loser->loser_type)->toBeString();
        });
    });
});