<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories\Matches;

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchDecision;
use App\Models\Matches\MatchType;
use App\Models\Referees\Referee;
use App\Models\Shared\Venue;

/**
 * Unit tests for EventMatchFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (realistic match data)
 * - Factory state methods (with referees, decisions, etc.)
 * - Factory relationship creation (events, match types, referees)
 * - Match configuration data (preview, match_number, results)
 * - Business rule compliance for match setup
 *
 * These tests verify that the EventMatchFactory generates consistent,
 * realistic match data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see \Database\Factories\Matches\EventMatchFactory
 */
describe('EventMatchFactory Unit Tests', function () {
    describe('default attribute generation', function () {
        test('creates match with correct default attributes', function () {
            // Arrange & Act
            $match = EventMatch::factory()->make();
            
            // Assert
            expect($match->event_id)->toBeInt();
            expect($match->match_type_id)->toBeInt();
            expect($match->match_number)->toBeInt();
            expect($match->match_number)->toBeGreaterThan(0);
            expect($match->preview)->toBeNull(); // Default no preview
        });

        test('generates realistic match match_number', function () {
            // Arrange & Act
            $match = EventMatch::factory()->make();
            
            // Assert
            expect($match->match_number)->toBeInt();
            expect($match->match_number)->toBeBetween(1, 10);
        });
    });

    describe('factory state methods', function () {
        test('with preview state works correctly', function () {
            // Arrange
            $preview = 'This is an exciting match between two competitors.';

            // Act
            $match = EventMatch::factory()->make(['preview' => $preview]);
            
            // Assert
            expect($match->preview)->toBe($preview);
        });

        test('with referee state works correctly', function () {
            // Arrange
            $referee = Referee::factory()->create();

            // Act
            $match = EventMatch::factory()->make(['referee_id' => $referee->id]);
            
            // Assert
            expect($match->referee_id)->toBe($referee->id);
        });

        test('with decision state works correctly', function () {
            // Arrange
            $decision = MatchDecision::factory()->create();

            // Act
            $match = EventMatch::factory()->make(['match_decision_id' => $decision->id]);
            
            // Assert
            expect($match->match_decision_id)->toBe($decision->id);
        });
    });

    describe('factory customization', function () {
        test('accepts custom event association', function () {
            // Arrange
            $event = Event::factory()->create();

            // Act
            $match = EventMatch::factory()->make(['event_id' => $event->id]);
            
            // Assert
            expect($match->event_id)->toBe($event->id);
        });

        test('accepts custom match type association', function () {
            // Arrange
            $matchType = MatchType::factory()->create();

            // Act
            $match = EventMatch::factory()->make(['match_type_id' => $matchType->id]);
            
            // Assert
            expect($match->match_type_id)->toBe($matchType->id);
        });

        test('accepts custom match match_number', function () {
            // Arrange & Act
            $match = EventMatch::factory()->make(['match_number' => 5]);
            
            // Assert
            expect($match->match_number)->toBe(5);
        });
    });

    describe('data consistency', function () {
        test('database creation works correctly', function () {
            // Arrange & Act
            $match = EventMatch::factory()->create();
            
            // Assert
            expect($match->exists)->toBeTrue();
            expect($match->id)->toBeGreaterThan(0);
        });

        test('maintains proper match match_number', function () {
            // Arrange & Act
            $matches = collect(range(1, 5))->map(fn() => EventMatch::factory()->make());
            
            // Assert
            foreach ($matches as $match) {
                expect($match->match_number)->toBeInt();
                expect($match->match_number)->toBeGreaterThan(0);
            }
        });

        test('creates matches with valid relationships', function () {
            // Arrange & Act
            $match = EventMatch::factory()->make();
            
            // Assert
            expect($match->event_id)->toBeInt();
            expect($match->match_type_id)->toBeInt();
        });
    });
});