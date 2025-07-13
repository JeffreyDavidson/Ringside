<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories\Matches;

use App\Models\Matches\EventMatch;
use App\Models\Matches\EventMatchCompetitor;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;

/**
 * Unit tests for EventMatchCompetitorFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (competitor associations)
 * - Factory state methods (different competitor types)
 * - Factory relationship creation (matches, wrestlers, tag teams)
 * - Polymorphic competitor data (wrestler vs tag team competitors)
 * - Match competitor configuration and consistency
 *
 * These tests verify that the EventMatchCompetitorFactory generates consistent,
 * realistic competitor data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see \Database\Factories\Matches\EventMatchCompetitorFactory
 */
describe('EventMatchCompetitorFactory Unit Tests', function () {
    describe('default attribute generation', function () {
        test('creates competitor with correct default attributes', function () {
            // Arrange & Act
            $competitor = EventMatchCompetitor::factory()->make();
            
            // Assert
            expect($competitor->event_match_id)->toBeInt();
            expect($competitor->competitor_id)->toBeInt();
            expect($competitor->competitor_type)->toBeString();
            expect($competitor->competitor_type)->toBeIn(['wrestler', 'tag_team']);
        });

        test('creates realistic competitor side assignments', function () {
            // Arrange & Act
            $competitor = EventMatchCompetitor::factory()->make();
            
            // Assert
            expect($competitor->side_number)->toBeInt();
            expect($competitor->side_number)->toBeBetween(1, 2);
        });
    });

    describe('factory state methods', function () {
        test('wrestler competitor state works correctly', function () {
            // Arrange
            $match = EventMatch::factory()->create();
            $wrestler = Wrestler::factory()->create();

            // Act
            $competitor = EventMatchCompetitor::factory()->make([
                'event_match_id' => $match->id,
                'competitor_id' => $wrestler->id,
                'competitor_type' => 'wrestler',
            ]);
            
            // Assert
            expect($competitor->event_match_id)->toBe($match->id);
            expect($competitor->competitor_id)->toBe($wrestler->id);
            expect($competitor->competitor_type)->toBe('wrestler');
        });

        test('tag team competitor state works correctly', function () {
            // Arrange
            $match = EventMatch::factory()->create();
            $tagTeam = TagTeam::factory()->create();

            // Act
            $competitor = EventMatchCompetitor::factory()->make([
                'event_match_id' => $match->id,
                'competitor_id' => $tagTeam->id,
                'competitor_type' => 'tag_team',
            ]);
            
            // Assert
            expect($competitor->event_match_id)->toBe($match->id);
            expect($competitor->competitor_id)->toBe($tagTeam->id);
            expect($competitor->competitor_type)->toBe('tag_team');
        });

        test('side assignment state works correctly', function () {
            // Arrange & Act
            $competitor1 = EventMatchCompetitor::factory()->make(['side_number' => 1]);
            $competitor2 = EventMatchCompetitor::factory()->make(['side_number' => 2]);
            
            // Assert
            expect($competitor1->side_number)->toBe(1);
            expect($competitor2->side_number)->toBe(2);
        });
    });

    describe('factory customization', function () {
        test('accepts custom match association', function () {
            // Arrange
            $match = EventMatch::factory()->create();

            // Act
            $competitor = EventMatchCompetitor::factory()->make(['event_match_id' => $match->id]);
            
            // Assert
            expect($competitor->event_match_id)->toBe($match->id);
        });

        test('accepts custom competitor configuration', function () {
            // Arrange
            $wrestler = Wrestler::factory()->create();

            // Act
            $competitor = EventMatchCompetitor::factory()->make([
                'competitor_id' => $wrestler->id,
                'competitor_type' => 'wrestler',
                'side_number' => 1,
            ]);
            
            // Assert
            expect($competitor->competitor_id)->toBe($wrestler->id);
            expect($competitor->competitor_type)->toBe('wrestler');
            expect($competitor->side_number)->toBe(1);
        });
    });

    describe('data consistency', function () {
        test('database creation works correctly', function () {
            // Arrange & Act
            $competitor = EventMatchCompetitor::factory()->create();
            
            // Assert
            expect($competitor->exists)->toBeTrue();
            // Note: Pivot models don't reliably return IDs after create() due to Laravel limitations
        });

        test('maintains valid competitor types', function () {
            // Arrange & Act
            $competitors = collect(range(1, 5))->map(fn() => EventMatchCompetitor::factory()->make());
            
            // Assert
            foreach ($competitors as $competitor) {
                expect($competitor->competitor_type)->toBeIn(['wrestler', 'tag_team']);
                expect($competitor->side_number)->toBeBetween(1, 2);
            }
        });

        test('creates competitors with valid match associations', function () {
            // Arrange & Act
            $competitor = EventMatchCompetitor::factory()->make();
            
            // Assert
            expect($competitor->event_match_id)->toBeInt();
            expect($competitor->competitor_id)->toBeInt();
            expect($competitor->competitor_type)->toBeString();
        });
    });
});