<?php

declare(strict_types=1);

namespace Tests\Integration\Models\Factories\Matches;

use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Database\Factories\Matches\MatchCompetitorFactory;

/**
 * Integration tests for MatchCompetitorFactory data generation and state management.
 *
 * INTEGRATION TEST SCOPE:
 * - Factory default attribute generation (competitor associations)
 * - Factory state methods (different competitor types)
 * - Factory relationship creation (matches, wrestlers, tag teams)
 * - Polymorphic competitor data (wrestler vs tag team competitors)
 * - Match competitor configuration and consistency
 *
 * These tests verify that the MatchCompetitorFactory generates consistent,
 * realistic competitor data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see MatchCompetitorFactory
 */
describe('MatchCompetitorFactory Integration Tests', function () {
    describe('default attribute generation', function () {
        test('creates competitor with correct default attributes', function () {
            // Arrange & Act
            $competitor = MatchCompetitor::factory()->make();

            // Assert
            expect($competitor->match_id)->toBeInt();
            expect($competitor->competitor_id)->toBeInt();
            expect($competitor->competitor_type)->toBeString();
            expect($competitor->competitor_type)->toBeIn(['wrestler', 'tag_team']);
        });

        test('creates a match side assignment', function () {
            // Arrange & Act
            $competitor = MatchCompetitor::factory()->make();

            // Assert
            expect($competitor->match_side_id)->toBeInt();
        });
    });

    describe('factory state methods', function () {
        test('wrestler competitor state works correctly', function () {
            // Arrange
            $match = EventMatch::factory()->create();
            $wrestler = Wrestler::factory()->create();

            // Act
            $competitor = MatchCompetitor::factory()->make([
                'match_id' => $match->id,
                'competitor_id' => $wrestler->id,
                'competitor_type' => 'wrestler',
            ]);

            // Assert
            expect($competitor->match_id)->toBe($match->id);
            expect($competitor->competitor_id)->toBe($wrestler->id);
            expect($competitor->competitor_type)->toBe('wrestler');
        });

        test('tag team competitor state works correctly', function () {
            // Arrange
            $match = EventMatch::factory()->create();
            $tagTeam = TagTeam::factory()->create();

            // Act
            $competitor = MatchCompetitor::factory()->make([
                'match_id' => $match->id,
                'competitor_id' => $tagTeam->id,
                'competitor_type' => 'tag_team',
            ]);

            // Assert
            expect($competitor->match_id)->toBe($match->id);
            expect($competitor->competitor_id)->toBe($tagTeam->id);
            expect($competitor->competitor_type)->toBe('tag_team');
        });

        test('side assignment state works correctly', function () {
            $match = EventMatch::factory()->create();
            $side1 = MatchSide::factory()->for($match, 'match')->create(['position' => 1]);
            $side2 = MatchSide::factory()->for($match, 'match')->create(['position' => 2]);

            $competitor1 = MatchCompetitor::factory()->make([
                'match_id' => $match->id,
                'match_side_id' => $side1->id,
            ]);
            $competitor2 = MatchCompetitor::factory()->make([
                'match_id' => $match->id,
                'match_side_id' => $side2->id,
            ]);

            expect($competitor1->match_side_id)->toBe($side1->id);
            expect($competitor2->match_side_id)->toBe($side2->id);
        });
    });

    describe('factory customization', function () {
        test('accepts custom match association', function () {
            // Arrange
            $match = EventMatch::factory()->create();

            // Act
            $competitor = MatchCompetitor::factory()->make(['match_id' => $match->id]);

            // Assert
            expect($competitor->match_id)->toBe($match->id);
        });

        test('accepts custom competitor configuration', function () {
            // Arrange
            $wrestler = Wrestler::factory()->create();
            $match = EventMatch::factory()->create();
            $side = MatchSide::factory()->for($match, 'match')->create(['position' => 1]);

            // Act
            $competitor = MatchCompetitor::factory()->make([
                'match_id' => $match->id,
                'match_side_id' => $side->id,
                'competitor_id' => $wrestler->id,
                'competitor_type' => 'wrestler',
            ]);

            // Assert
            expect($competitor->competitor_id)->toBe($wrestler->id);
            expect($competitor->competitor_type)->toBe('wrestler');
            expect($competitor->match_side_id)->toBe($side->id);
        });
    });

    describe('data consistency', function () {
        test('database creation works correctly', function () {
            // Arrange & Act
            $competitor = MatchCompetitor::factory()->create();

            // Assert
            expect($competitor->exists)->toBeTrue();
            // Note: Pivot models don't reliably return IDs after create() due to Laravel limitations
        });

        test('maintains valid competitor types', function () {
            // Arrange & Act
            $competitors = collect(range(1, 5))->map(fn () => MatchCompetitor::factory()->make());

            // Assert
            foreach ($competitors as $competitor) {
                expect($competitor->competitor_type)->toBeIn(['wrestler', 'tag_team']);
                expect($competitor->match_side_id)->toBeInt();
            }
        });

        test('creates competitors with valid match associations', function () {
            // Arrange & Act
            $competitor = MatchCompetitor::factory()->make();

            // Assert
            expect($competitor->match_id)->toBeInt();
            expect($competitor->competitor_id)->toBeInt();
            expect($competitor->competitor_type)->toBeString();
        });
    });
});
