<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\Matches\EventMatch;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Models\Wrestlers\Wrestler;
use Database\Factories\Titles\TitleChampionshipFactory;
use Illuminate\Support\Carbon;

/**
 * Unit tests for TitleChampionshipFactory data generation and state management.
 *
 * UNIT TEST SCOPE:
 * - Factory default attribute generation (realistic championship data)
 * - Factory state methods (current, past, wrestler, tagTeam, etc.)
 * - Factory relationship creation (titles, champions, matches)
 * - Champion type handling (wrestler vs tag team)
 * - Championship timeline data (won_at, lost_at dates)
 *
 * These tests verify that the TitleChampionshipFactory generates consistent,
 * realistic championship data that complies with business rules and supports
 * comprehensive testing scenarios across the application.
 *
 * @see TitleChampionshipFactory
 */
describe('TitleChampionshipFactory Unit Tests', function () {
    describe('default attribute generation', function () {
        test('creates championship with correct default attributes', function () {
            // Arrange & Act
            $championship = TitleChampionship::factory()->make();

            // Assert
            expect($championship->title_id)->toBeInt();
            expect($championship->champion_type)->toBeIn(['wrestler', 'tagTeam']);
            expect($championship->champion_id)->toBeInt();
            expect($championship->won_match_id)->toBeNull(); // Default has no match
            expect($championship->lost_match_id)->toBeNull(); // Current championship
            expect($championship->won_at)->toBeInstanceOf(Carbon::class);
            expect($championship->lost_at)->toBeNull(); // Current championship
        });

        test('creates realistic championship timeline', function () {
            // Arrange & Act
            $championship = TitleChampionship::factory()->make();

            // Assert
            expect($championship->won_at->isPast())->toBeTrue();
            expect($championship->won_at->isAfter(now()->subYear()))->toBeTrue();
        });
    });

    describe('factory state methods', function () {
        test('wrestler championship state works correctly', function () {
            // Arrange
            $wrestler = Wrestler::factory()->create();
            $title = Title::factory()->create();

            // Act
            $championship = TitleChampionship::factory()->make([
                'title_id' => $title->id,
                'champion_type' => 'wrestler',
                'champion_id' => $wrestler->id,
            ]);

            // Assert
            expect($championship->champion_type)->toBe('wrestler');
            expect($championship->champion_id)->toBe($wrestler->id);
            expect($championship->title_id)->toBe($title->id);
        });

        test('tag team championship state works correctly', function () {
            // Arrange
            $tagTeam = TagTeam::factory()->create();
            $title = Title::factory()->create();

            // Act
            $championship = TitleChampionship::factory()->forTagTeam($tagTeam)->make([
                'title_id' => $title->id,
            ]);

            // Assert
            expect($championship->champion_type)->toBe('tagTeam');
            expect($championship->champion_id)->toBe($tagTeam->id);
            expect($championship->title_id)->toBe($title->id);
        });

        test('past championship state works correctly', function () {
            // Arrange
            $wonDate = now()->subMonths(6);
            $lostDate = now()->subMonths(2);
            $lostMatch = EventMatch::factory()->create();

            // Act
            $championship = TitleChampionship::factory()->make([
                'won_at' => $wonDate,
                'lost_at' => $lostDate,
                'lost_match_id' => $lostMatch->id,
            ]);

            // Assert
            expect($championship->won_at->format('Y-m-d H:i:s'))->toBe($wonDate->format('Y-m-d H:i:s'));
            expect($championship->lost_at->format('Y-m-d H:i:s'))->toBe($lostDate->format('Y-m-d H:i:s'));
            expect($championship->lost_match_id)->toBe($lostMatch->id);
            expect($championship->lost_at->isAfter($championship->won_at))->toBeTrue();
        });
    });

    describe('data consistency', function () {
        test('database creation works correctly', function () {
            // Arrange & Act
            $championship = TitleChampionship::factory()->create();

            // Assert
            expect($championship->exists)->toBeTrue();
            expect($championship->id)->toBeGreaterThan(0);
        });
    });
});
