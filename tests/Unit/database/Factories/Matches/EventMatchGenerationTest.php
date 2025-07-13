<?php

declare(strict_types=1);

use App\Enums\Titles\TitleType;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchDecision;
use App\Models\Matches\MatchType;
use App\Models\Referees\Referee;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Models\Wrestlers\Wrestler;

/**
 * Unit tests for EventMatch comprehensive generation capabilities.
 *
 * UNIT TEST SCOPE:
 * - Comprehensive match generation with full configuration
 * - Match type validation and competitor type enforcement
 * - Title match scenarios with championship implications
 * - Winner/loser assignment strategies and no-outcome scenarios
 * - Complex multi-competitor and multi-title scenarios
 *
 * These tests verify that the EventMatch factory can generate complex,
 * realistic wrestling matches that comply with business rules and support
 * all match generation scenarios across the application.
 *
 * @see \Database\Factories\Matches\EventMatchFactory::generateFullMatch()
 */
describe('EventMatch Comprehensive Generation Unit Tests', function () {
    describe('basic match generation scenarios', function () {
        test('generates simple singles match with minimal config', function () {
            // Arrange & Act
            $match = EventMatch::factory()->generateFullMatch([
                'match_type' => 'singles',
            ])->create();

            // Assert
            expect($match->matchType->slug)->toBe('singles');
            expect($match->competitors)->toHaveCount(2);
            expect($match->result)->not->toBeNull();

            $competitors = $match->competitors;
            foreach ($competitors as $competitor) {
                expect($competitor->competitor_type)->toBe(Wrestler::class);
            }
        });

        test('generates tag team match with mixed competitors', function () {
            // Arrange & Act
            $match = EventMatch::factory()->generateFullMatch([
                'match_type' => 'tagteam',
                'competitor_count' => 4,
            ])->create();

            // Assert
            expect($match->matchType->slug)->toBe('tag-team');
            expect($match->competitors)->toHaveCount(4);
            expect($match->result)->not->toBeNull();

            $allowedTypes = [Wrestler::class, TagTeam::class];
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
            expect($match->matchType->slug)->toBe('battle-royal');
            expect($match->competitors)->toHaveCount(15);
            expect($match->result)->not->toBeNull();
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
                expect($competitor->competitor_type)->toBe(Wrestler::class);
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
                ->where('competitor_type', Wrestler::class)
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
                ->where('competitor_type', Wrestler::class)
                ->count();
            $tagTeamCount = $match->competitors
                ->where('competitor_type', TagTeam::class)
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

    describe('winner and loser assignment strategies', function () {
        test('generates match with first competitor as winner', function () {
            // Arrange & Act
            $match = EventMatch::factory()->generateFullMatch([
                'match_type' => 'singles',
                'winner_strategy' => 'first',
            ])->create();

            // Assert
            expect($match->result)->not->toBeNull();
            expect($match->result->winners)->toHaveCount(1);
            expect($match->result->losers)->toHaveCount(1);

            $firstCompetitor = $match->competitors->first();
            $winner = $match->result->winners->first();

            expect($winner->winner_type)->toBe($firstCompetitor->competitor_type);
            expect($winner->winner_id)->toBe($firstCompetitor->competitor_id);
        });

        test('generates match with last competitor as winner', function () {
            // Arrange & Act
            $match = EventMatch::factory()->generateFullMatch([
                'match_type' => 'triple',
                'winner_strategy' => 'last',
            ])->create();

            // Assert
            expect($match->result->winners)->toHaveCount(1);
            expect($match->result->losers)->toHaveCount(2);

            $lastCompetitor = $match->competitors->last();
            $winner = $match->result->winners->first();

            expect($winner->winner_type)->toBe($lastCompetitor->competitor_type);
            expect($winner->winner_id)->toBe($lastCompetitor->competitor_id);
        });

        test('generates match with multiple winners', function () {
            // Arrange & Act
            $match = EventMatch::factory()->generateFullMatch([
                'match_type' => 'fatal4way',
                'winner_strategy' => 'multiple',
            ])->create();

            // Assert
            expect($match->result->winners->count())->toBeGreaterThan(0);
            expect($match->result->winners->count())->toBeLessThan(4);

            $totalCompetitors = $match->result->winners->count() + $match->result->losers->count();
            expect($totalCompetitors)->toBe(4);
        });

        test('generates match with all but one as winners', function () {
            // Arrange & Act
            $match = EventMatch::factory()->generateFullMatch([
                'match_type' => 'fatal4way',
                'winner_strategy' => 'all_but_one',
            ])->create();

            // Assert
            expect($match->result->winners)->toHaveCount(3);
            expect($match->result->losers)->toHaveCount(1);
        });
    });

    describe('no-outcome match scenarios', function () {
        test('generates time limit draw with no winners or losers', function () {
            // Arrange
            $drawDecision = MatchDecision::factory()->create(['slug' => 'draw']);

            // Act
            $match = EventMatch::factory()->generateFullMatch([
                'match_type' => 'singles',
                'decision_type' => 'draw',
            ])->create();

            // Assert
            expect($match->result)->not->toBeNull();
            expect($match->result->decision->slug)->toBe('draw');
            expect($match->result->winners)->toHaveCount(0);
            expect($match->result->losers)->toHaveCount(0);
        });

        test('generates no decision match with no winners or losers', function () {
            // Arrange - Ensure nodecision exists
            MatchDecision::factory()->create([
                'name' => 'No Decision',
                'slug' => 'nodecision',
            ]);
            
            // Act
            $match = EventMatch::factory()->generateFullMatch([
                'match_type' => 'singles',
                'decision_type' => 'nodecision',
            ])->create();

            // Assert
            expect($match->result)->not->toBeNull();
            expect($match->result->decision->slug)->toBe('nodecision');
            expect($match->result->winners)->toHaveCount(0);
            expect($match->result->losers)->toHaveCount(0);
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
            expect($match->matchType->slug)->toBe('singles');
            expect($match->titles->pluck('id'))->toContain($title->id);
            expect($match->competitors)->toHaveCount(2);
            expect($match->referees->pluck('id'))->toContain($referee->id);
            expect($match->result)->not->toBeNull();

            // Challenger should win (last competitor strategy)
            $winner = $match->result->winners->first();
            expect($winner->winner_id)->toBe($challenger->id);
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
            expect($match->matchType->slug)->toBe('singles');
            foreach ($match->competitors as $competitor) {
                expect($competitor->competitor_type)->toBe(Wrestler::class);
            }
        });

        test('enforces wrestler-only rule for royal rumble matches', function () {
            // Arrange & Act
            $match = EventMatch::factory()->generateFullMatch([
                'match_type' => 'royalrumble',
                'competitor_count' => 10,
            ])->create();

            // Assert
            expect($match->matchType->slug)->toBe('royal-rumble');
            foreach ($match->competitors as $competitor) {
                expect($competitor->competitor_type)->toBe(Wrestler::class);
            }
        });

        test('allows mixed competitors for tag team matches', function () {
            // Arrange & Act
            $match = EventMatch::factory()->generateFullMatch([
                'match_type' => 'tagteam',
                'competitor_count' => 4,
            ])->create();

            // Assert
            expect($match->matchType->slug)->toBe('tag-team');

            $allowedTypes = [Wrestler::class, TagTeam::class];
            foreach ($match->competitors as $competitor) {
                expect($allowedTypes)->toContain($competitor->competitor_type);
            }
        });
    });
});
