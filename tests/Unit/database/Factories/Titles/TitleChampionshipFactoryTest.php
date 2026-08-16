<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories;

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
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
            expect($championship->champion_type)->toBeIn(['wrestler', 'tag_team']);
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
            expect(requiredDate($championship->won_at)->isAfter(now()->subYear()))->toBeTrue();
        });
    });

    describe('factory state methods', function () {
        test('wrestler championship state works correctly', function () {
            // Arrange
            $wrestler = Wrestler::factory()->create();
            $title = Title::factory()->create();

            // Act
            $championship = TitleChampionship::factory()->forWrestler($wrestler)->make([
                'title_id' => $title->id,
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
            expect($championship->champion_type)->toBe('tag_team');
            expect($championship->champion_id)->toBe($tagTeam->id);
            expect($championship->title_id)->toBe($title->id);
        });

        test('configuring a champion relationship does not persist the champion', function (string $state, string $modelClass) {
            TitleChampionship::factory()->{$state}();

            expect($modelClass::query()->count())->toBe(0);
        })->with([
            ['forWrestler', Wrestler::class],
            ['forTagTeam', TagTeam::class],
        ]);

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
            expect(requiredDate($championship->won_at)->format('Y-m-d H:i:s'))->toBe($wonDate->format('Y-m-d H:i:s'));
            expect(requiredDate($championship->lost_at)->format('Y-m-d H:i:s'))->toBe($lostDate->format('Y-m-d H:i:s'));
            expect($championship->lost_match_id)->toBe($lostMatch->id);
            expect(requiredDate($championship->lost_at)->isAfter($championship->won_at))->toBeTrue();
        });

        test('won at event match creates a scheduled match when one is not supplied', function () {
            // Act
            $championship = TitleChampionship::factory()->wonAtEventMatch()->create();

            // Assert
            expect($championship->wonEventMatch)->toBeInstanceOf(EventMatch::class)
                ->and($championship->won_at)->toEqual($championship->wonEventMatch?->event->date);
        });

        test('lost at event match creates a complete championship timeline', function () {
            // Act
            $championship = TitleChampionship::factory()->lostAtEventMatch()->create();

            // Assert
            expect($championship->wonEventMatch)->toBeInstanceOf(EventMatch::class)
                ->and($championship->lostEventMatch)->toBeInstanceOf(EventMatch::class)
                ->and($championship->won_at)->toEqual($championship->wonEventMatch?->event->date)
                ->and($championship->lost_at)->toEqual($championship->lostEventMatch?->event->date)
                ->and(requiredDate($championship->lost_at)->isAfter($championship->won_at))->toBeTrue();
        });

        test('lost at event match retains supplied winning and losing matches', function () {
            // Arrange
            $wonEventMatch = EventMatch::factory()
                ->for(Event::factory()->state(['date' => now()->subMonth()]))
                ->create();
            $lostEventMatch = EventMatch::factory()
                ->for(Event::factory()->past())
                ->create();

            // Act
            $championship = TitleChampionship::factory()
                ->lostAtEventMatch($lostEventMatch, $wonEventMatch)
                ->create();

            // Assert
            expect($championship->won_match_id)->toBe($wonEventMatch->id)
                ->and($championship->lost_match_id)->toBe($lostEventMatch->id)
                ->and($championship->won_at)->toEqual($wonEventMatch->event->date)
                ->and($championship->lost_at)->toEqual($lostEventMatch->event->date);
        });

        test('current state clears an existing loss date and match', function () {
            $championship = TitleChampionship::factory()
                ->lostAtEventMatch()
                ->current()
                ->make();

            expect($championship->lost_match_id)->toBeNull()
                ->and($championship->lost_at)->toBeNull();
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
