<?php

declare(strict_types=1);

namespace Tests\Integration\Models\Factories\Matches;

use App\Enums\MatchFinish;
use App\Enums\Titles\TitleType;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Database\Factories\Matches\MatchFactory;
use InvalidArgumentException;

/**
 * Integration tests for Match comprehensive generation capabilities.
 *
 * INTEGRATION TEST SCOPE:
 * - Comprehensive match generation with full configuration
 * - Match type validation and competitor type enforcement
 * - Title match scenarios with championship implications
 * - Winner/loser assignment strategies and no-outcome scenarios
 * - Complex multi-competitor and multi-title scenarios
 *
 * These tests verify that the Match factory can generate complex,
 * realistic wrestling matches that comply with business rules and support
 * all match generation scenarios across the application.
 *
 * @see MatchFactory::generateFullMatch()
 */
describe('Match Comprehensive Generation Integration Tests', function () {
    describe('basic match generation scenarios', function () {
        test('generates simple singles match with minimal config', function () {
            // Arrange & Act
            $match = EventMatch::factory()->generateFullMatch([
                'match_type' => 'singles',
            ])->create();

            // Assert
            expect($match->match_type->value)->toBe('singles');
            expect($match->competitors)->toHaveCount(2);
            expect($match->match_finish)->toBeInstanceOf(MatchFinish::class);

            $competitors = $match->competitors;
            foreach ($competitors as $competitor) {
                expect($competitor->competitor_type)->toBe((new Wrestler())->getMorphClass());
            }
        });

        test('generates tag team match with mixed competitors', function () {
            // Arrange & Act
            $match = EventMatch::factory()->generateFullMatch([
                'match_type' => 'tagteam',
                'competitor_count' => 4,
            ])->create();

            // Assert
            expect($match->match_type->value)->toBe('tag-team');
            expect($match->competitors)->toHaveCount(4);
            expect($match->match_finish)->toBeInstanceOf(MatchFinish::class);

            $allowedTypes = [(new Wrestler())->getMorphClass(), (new TagTeam())->getMorphClass()];
            foreach ($match->competitors as $competitor) {
                expect($allowedTypes)->toContain($competitor->competitor_type);
            }
        });

        test('generates battle royal with multiple competitors', function () {
            // Arrange & Act
            $match = EventMatch::factory()->generateFullMatch([
                'match_type' => 'battleroyal',
                'competitor_count' => 15,
            ])->create();

            // Assert
            expect($match->match_type->value)->toBe('battle-royal');
            expect($match->competitors)->toHaveCount(15);
            expect($match->match_finish)->toBeInstanceOf(MatchFinish::class);
        });
    });

    describe('title match generation scenarios', function () {
        test('generates singles title match with proper validation', function () {
            // Arrange
            $singlesTitle = Title::factory()->create(['type' => TitleType::Singles]);

            // Act
            $match = EventMatch::factory()->generateFullMatch([
                'match_type' => 'singles',
                'titles' => [$singlesTitle],
            ])->create();

            // Assert
            expect($match->titles->pluck('id'))->toContain($singlesTitle->id);
            expect($match->competitors)->toHaveCount(2);

            // All competitors should be wrestlers for singles title
            foreach ($match->competitors as $competitor) {
                expect($competitor->competitor_type)->toBe((new Wrestler())->getMorphClass());
            }
        });

        test('generates tag team title match with proper validation', function () {
            // Arrange
            $tagTeamTitle = Title::factory()->create(['type' => TitleType::TagTeam]);

            // Act
            $match = EventMatch::factory()->generateFullMatch([
                'match_type' => 'tagteam',
                'titles' => [$tagTeamTitle],
                'competitor_count' => 2,
            ])->create();

            // Assert
            expect($match->titles->pluck('id'))->toContain($tagTeamTitle->id);
            expect($match->competitors)->toHaveCount(2);
        });

        test('generates championship defense with existing champion', function () {
            // Arrange
            $title = Title::factory()->create(['type' => TitleType::Singles]);
            $champion = Wrestler::factory()->create();

            TitleChampionship::factory()->create([
                'title_id' => $title->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $champion->id,
                'won_at' => now()->subMonths(3),
                'lost_at' => null,
            ]);

            // Act
            $match = EventMatch::factory()->generateFullMatch([
                'match_type' => 'singles',
                'titles' => [$title],
                'competitor_count' => 2,
            ])->create();

            // Assert
            expect($match->titles->pluck('id'))->toContain($title->id);
            expect($match->competitors)->toHaveCount(2);

            // Champion should be included as competitor
            $championIncluded = $match->competitors
                ->where('competitor_type', (new Wrestler())->getMorphClass())
                ->where('competitor_id', $champion->id)
                ->isNotEmpty();
            expect($championIncluded)->toBeTrue();
        });
    });

    describe('specific competitor configuration', function () {
        test('generates match with specific competitors provided', function () {
            // Arrange
            $wrestler1 = Wrestler::factory()->create();
            $wrestler2 = Wrestler::factory()->create();
            $tagTeam = TagTeam::factory()->create();

            // Act
            $match = EventMatch::factory()->generateFullMatch([
                'match_type' => 'triple',
                'competitors' => [$wrestler1, $wrestler2, $tagTeam],
            ])->create();

            // Assert
            expect($match->competitors)->toHaveCount(3);

            $competitorIds = $match->competitors->pluck('competitor_id');
            expect($competitorIds)->toContain($wrestler1->id);
            expect($competitorIds)->toContain($wrestler2->id);
            expect($competitorIds)->toContain($tagTeam->id);
        });

        test('generates match with competitor type hints', function () {
            // Arrange & Act
            $match = EventMatch::factory()->generateFullMatch([
                'match_type' => 'fatal4way',
                'competitors' => ['wrestler', 'wrestler', 'tag_team', 'tag_team'],
            ])->create();

            // Assert
            expect($match->competitors)->toHaveCount(4);

            $wrestlerCount = $match->competitors
                ->where('competitor_type', (new Wrestler())->getMorphClass())
                ->count();
            $tagTeamCount = $match->competitors
                ->where('competitor_type', (new TagTeam())->getMorphClass())
                ->count();

            expect($wrestlerCount)->toBe(2);
            expect($tagTeamCount)->toBe(2);
        });

        test('generates match with competitor names', function () {
            // Arrange & Act
            $match = EventMatch::factory()->generateFullMatch([
                'match_type' => 'singles',
                'competitors' => ['John Cena', 'The Rock'],
            ])->create();

            // Assert
            expect($match->competitors)->toHaveCount(2);

            // Should create wrestlers with specified names
            $wrestlerNames = Wrestler::whereIn('id', $match->competitors->pluck('competitor_id'))
                ->pluck('name');
            expect($wrestlerNames)->toContain('John Cena');
            expect($wrestlerNames)->toContain('The Rock');
        });
    });

    describe('winning side assignment strategies', function () {
        test('generates match with first competitor side as winner', function () {
            // Arrange & Act
            $match = EventMatch::factory()->generateFullMatch([
                'match_type' => 'singles',
                'winner_strategy' => 'first',
            ])->create();

            // Assert
            $firstCompetitor = $match->competitors->firstOrFail();
            expect($match->winning_side_id)->toBe($firstCompetitor->match_side_id);
        });

        test('generates match with last competitor side as winner', function () {
            // Arrange & Act
            $match = EventMatch::factory()->generateFullMatch([
                'match_type' => 'triple',
                'winner_strategy' => 'last',
            ])->create();

            // Assert
            $lastCompetitor = $match->competitors->reverse()->firstOrFail();
            expect($match->winning_side_id)->toBe($lastCompetitor->match_side_id);
        });

        test('rejects a multiple competitor winner strategy because winners are sides', function () {
            expect(fn () => EventMatch::factory()->generateFullMatch([
                'match_type' => 'fatal4way',
                'winner_strategy' => 'multiple',
            ])->create())->toThrow(InvalidArgumentException::class);
        });

        test('rejects an all but one winner strategy because winners are sides', function () {
            expect(fn () => EventMatch::factory()->generateFullMatch([
                'match_type' => 'fatal4way',
                'winner_strategy' => 'all_but_one',
            ])->create())->toThrow(InvalidArgumentException::class);
        });
    });

    describe('no-outcome match scenarios', function () {
        test('generates time limit draw with no winning side', function () {
            $match = EventMatch::factory()->generateFullMatch([
                'match_type' => 'singles',
                'decision_type' => 'time-limit-draw',
            ])->create();

            // Assert
            expect($match->match_finish)->toBe(MatchFinish::TimeLimitDraw)
                ->and($match->winning_side_id)->toBeNull();
        });

        test('generates no decision match with no winning side', function () {
            $match = EventMatch::factory()->generateFullMatch([
                'match_type' => 'singles',
                'decision_type' => 'no-decision',
            ])->create();

            // Assert
            expect($match->match_finish)->toBe(MatchFinish::NoDecision)
                ->and($match->winning_side_id)->toBeNull();
        });
    });

    describe('referee assignment scenarios', function () {
        test('generates match with specified number of referees', function () {
            // Arrange & Act
            $match = EventMatch::factory()->generateFullMatch([
                'match_type' => 'singles',
                'referees' => 2,
            ])->create();

            // Assert
            expect($match->referees)->toHaveCount(2);
        });

        test('generates match with specific referees', function () {
            // Arrange
            $referee1 = Referee::factory()->create();
            $referee2 = Referee::factory()->create();

            // Act
            $match = EventMatch::factory()->generateFullMatch([
                'match_type' => 'singles',
                'referees' => [$referee1, $referee2],
            ])->create();

            // Assert
            expect($match->referees)->toHaveCount(2);
            expect($match->referees->pluck('id'))->toContain($referee1->id);
            expect($match->referees->pluck('id'))->toContain($referee2->id);
        });
    });

    describe('complex multi-scenario generation', function () {
        test('generates complete championship main event', function () {
            // Arrange
            $event = Event::factory()->create();
            $title = Title::factory()->create(['type' => TitleType::Singles]);
            $champion = Wrestler::factory()->create();
            $challenger = Wrestler::factory()->create();
            $referee = Referee::factory()->create();

            TitleChampionship::factory()->create([
                'title_id' => $title->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $champion->id,
                'won_at' => now()->subMonths(6),
                'lost_at' => null,
            ]);

            // Act
            $match = EventMatch::factory()
                ->forEvent($event)
                ->withMatchNumber(1)
                ->state(['preview' => 'Championship Main Event'])
                ->generateFullMatch([
                    'match_type' => 'singles',
                    'titles' => [$title],
                    'competitors' => [$champion, $challenger],
                    'decision_type' => 'pinfall',
                    'winner_strategy' => 'last',
                    'referees' => [$referee],
                ])
                ->create();

            // Assert
            expect($match->event_id)->toBe($event->id);
            expect($match->match_number)->toBe(1);
            expect($match->preview)->toBe('Championship Main Event');
            expect($match->match_type->value)->toBe('singles');
            expect($match->titles->pluck('id'))->toContain($title->id);
            expect($match->competitors)->toHaveCount(2);
            expect($match->referees->pluck('id'))->toContain($referee->id);
            expect($match->winningSide)->not->toBeNull()
                ->and($match->winningSide?->competitors->sole()->competitor_id)->toBe($challenger->id);
        });

        test('generates multi-title unification match', function () {
            // Arrange
            $title1 = Title::factory()->create(['type' => TitleType::Singles]);
            $title2 = Title::factory()->create(['type' => TitleType::Singles]);
            $champion1 = Wrestler::factory()->create();
            $champion2 = Wrestler::factory()->create();

            TitleChampionship::factory()->create([
                'title_id' => $title1->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $champion1->id,
                'won_at' => now()->subMonths(3),
                'lost_at' => null,
            ]);

            TitleChampionship::factory()->create([
                'title_id' => $title2->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $champion2->id,
                'won_at' => now()->subMonths(2),
                'lost_at' => null,
            ]);

            // Act
            $match = EventMatch::factory()->generateFullMatch([
                'match_type' => 'singles',
                'titles' => [$title1, $title2],
                'competitor_count' => 2,
                'winner_strategy' => 'single',
            ])->create();

            // Assert
            expect($match->titles)->toHaveCount(2);
            expect($match->titles->pluck('id'))->toContain($title1->id);
            expect($match->titles->pluck('id'))->toContain($title2->id);
            expect($match->competitors)->toHaveCount(2);

            // Both champions should be included
            $competitorIds = $match->competitors->pluck('competitor_id');
            expect($competitorIds)->toContain($champion1->id);
            expect($competitorIds)->toContain($champion2->id);
        });
    });

    describe('match type enforcement and validation', function () {
        test('enforces wrestler-only rule for singles matches', function () {
            // Arrange & Act
            $match = EventMatch::factory()->generateFullMatch([
                'match_type' => 'singles',
                'competitor_count' => 2,
            ])->create();

            // Assert
            expect($match->match_type->value)->toBe('singles');
            foreach ($match->competitors as $competitor) {
                expect($competitor->competitor_type)->toBe((new Wrestler())->getMorphClass());
            }
        });

        test('enforces wrestler-only rule for royal rumble matches', function () {
            // Arrange & Act
            $match = EventMatch::factory()->generateFullMatch([
                'match_type' => 'royalrumble',
                'competitor_count' => 10,
            ])->create();

            // Assert
            expect($match->match_type->value)->toBe('royal-rumble');
            foreach ($match->competitors as $competitor) {
                expect($competitor->competitor_type)->toBe((new Wrestler())->getMorphClass());
            }
        });

        test('allows mixed competitors for tag team matches', function () {
            // Arrange & Act
            $match = EventMatch::factory()->generateFullMatch([
                'match_type' => 'tagteam',
                'competitor_count' => 4,
            ])->create();

            // Assert
            expect($match->match_type->value)->toBe('tag-team');

            $allowedTypes = [(new Wrestler())->getMorphClass(), (new TagTeam())->getMorphClass()];
            foreach ($match->competitors as $competitor) {
                expect($allowedTypes)->toContain($competitor->competitor_type);
            }
        });
    });
});
