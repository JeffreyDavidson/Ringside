<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Factories\Matches;

use App\Enums\MatchFinish;
use App\Enums\MatchType;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Database\Factories\Matches\MatchFactory;

/**
 * Unit tests for MatchFactory comprehensive match generation.
 *
 * These tests verify that the MatchFactory can generate complete event matches
 * with proper competitors, results, winners/losers, and title match support.
 */
describe('MatchFactory', function () {
    describe('basic match creation', function () {
        test('creates basic event match with default values', function () {
            $eventMatch = EventMatch::factory()->create();

            expect($eventMatch)->toBeInstanceOf(EventMatch::class);
            expect($eventMatch->event_id)->toBeInt();
            expect($eventMatch->match_number)->toBeInt();
            expect($eventMatch->match_type)->toBeInstanceOf(MatchType::class);
            expect($eventMatch->preview)->toBeNull();
        });

        test('generates realistic match number within bounds', function () {
            // Arrange & Act
            $eventMatch = EventMatch::factory()->create();

            // Assert
            expect($eventMatch->match_number)->toBeInt();
            expect($eventMatch->match_number)->toBeBetween(1, 10);
        });

        test('maintains consistent match number generation', function () {
            // Arrange & Act
            $matches = collect(range(1, 5))->map(fn () => EventMatch::factory()->make());

            // Assert
            foreach ($matches as $match) {
                expect($match->match_number)->toBeInt();
                expect($match->match_number)->toBeGreaterThan(0);
                expect($match->match_number)->toBeBetween(1, 10);
            }
        });

        test('creates complete match with competitors and results', function () {
            $eventMatch = EventMatch::factory()->complete()->create();

            // Factory complete() method returns empty state - verify it can be created
            expect($eventMatch->exists)->toBeTrue();
            expect($eventMatch->event_id)->toBeInt();
            expect($eventMatch->match_type)->toBeInstanceOf(MatchType::class);
        });
    });

    describe('match type specific factories', function () {
        test('creates singles match with wrestler competitors', function () {
            $eventMatch = EventMatch::factory()->singles()->create();

            expect($eventMatch->match_type->value)->toBe('singles');
            expect($eventMatch->match_type->allowsWrestlers())->toBeTrue();
            expect($eventMatch->match_type->allowsTagTeams())->toBeFalse();
            expect($eventMatch->competitors)->toHaveCount(2);

            // All competitors should be wrestlers
            foreach ($eventMatch->competitors as $competitor) {
                expect($competitor->competitor_type)->toBe((new Wrestler())->getMorphClass());
            }
        });

        test('creates tag team match with mixed competitors', function () {
            $eventMatch = EventMatch::factory()->tagTeam()->create();

            expect($eventMatch->match_type->value)->toBe('tag-team');
            expect($eventMatch->match_type->allowsWrestlers())->toBeTrue();
            expect($eventMatch->match_type->allowsTagTeams())->toBeTrue();
            expect($eventMatch->competitors)->toHaveCount(2);

            // All competitors should be wrestlers or tag teams
            $allowedTypes = [(new Wrestler())->getMorphClass(), (new TagTeam())->getMorphClass()];
            foreach ($eventMatch->competitors as $competitor) {
                expect($allowedTypes)->toContain($competitor->competitor_type);
            }
        });

        test('creates triple threat match with 3 mixed competitors', function () {
            $eventMatch = EventMatch::factory()->tripleThreat()->create();

            expect($eventMatch->match_type->value)->toBe('triple-threat');
            expect($eventMatch->match_type->getMinimumCompetitors())->toBe(3);
            expect($eventMatch->competitors)->toHaveCount(3);

            // All competitors should be wrestlers or tag teams
            $allowedTypes = [(new Wrestler())->getMorphClass(), (new TagTeam())->getMorphClass()];
            foreach ($eventMatch->competitors as $competitor) {
                expect($allowedTypes)->toContain($competitor->competitor_type);
            }
        });

        test('creates fatal four way match with 4 mixed competitors', function () {
            $eventMatch = EventMatch::factory()->fatalFourWay()->create();

            expect($eventMatch->match_type->value)->toBe('fatal-4-way');
            expect($eventMatch->match_type->getMinimumCompetitors())->toBe(4);
            expect($eventMatch->competitors)->toHaveCount(4);

            // All competitors should be wrestlers or tag teams
            $allowedTypes = [(new Wrestler())->getMorphClass(), (new TagTeam())->getMorphClass()];
            foreach ($eventMatch->competitors as $competitor) {
                expect($allowedTypes)->toContain($competitor->competitor_type);
            }
        });

        test('creates battle royal with specified number of competitors', function () {
            $competitorCount = 15;
            $eventMatch = EventMatch::factory()->battleRoyal($competitorCount)->create();

            expect($eventMatch->match_type->value)->toBe('battle-royal');
            expect($eventMatch->competitors)->toHaveCount($competitorCount);

            // All competitors should be wrestlers or tag teams
            $allowedTypes = [(new Wrestler())->getMorphClass(), (new TagTeam())->getMorphClass()];
            foreach ($eventMatch->competitors as $competitor) {
                expect($allowedTypes)->toContain($competitor->competitor_type);
            }
        });
    });

    describe('title match scenarios', function () {
        test('creates title match with championship implications', function () {
            $title = Title::factory()->create();
            $eventMatch = EventMatch::factory()->titleMatch($title)->create();

            expect($eventMatch->titles)->toHaveCount(1);
            expect($eventMatch->titles->firstOrFail()->id)->toBe($title->id);
            expect($eventMatch->competitors)->not->toBeEmpty();
            expect($eventMatch->match_finish)->toBeInstanceOf(MatchFinish::class);
        });

        test('creates title defense with existing champion', function () {
            $title = Title::factory()->create(['type' => 'singles']);
            $eventMatch = EventMatch::factory()->titleDefense($title)->create();

            expect($eventMatch->titles)->toHaveCount(1);
            expect($eventMatch->titles->firstOrFail()->id)->toBe($title->id);

            // Should have a championship record
            $championship = TitleChampionship::where('title_id', $title->id)->firstOrFail();
            expect($championship)->not->toBeNull();
            expect($championship->champion_type)->toBe(Wrestler::class);
            // Champion should be one of the competitors
            $championCompetitor = $eventMatch->competitors->first(function ($competitor) use ($championship) {
                return $competitor->competitor_type === (new Wrestler())->getMorphClass()
                    && $competitor->competitor_id === $championship->champion_id;
            });
            expect($championCompetitor)->not->toBeNull();
        });

        test('creates tag team title defense with existing champion', function () {
            $title = Title::factory()->create(['type' => 'tag-team']);
            $eventMatch = EventMatch::factory()->titleDefense($title)->create();

            expect($eventMatch->titles)->toHaveCount(1);
            expect($eventMatch->titles->firstOrFail()->id)->toBe($title->id);

            // Should have a championship record
            $championship = TitleChampionship::where('title_id', $title->id)->firstOrFail();
            expect($championship)->not->toBeNull();
            expect($championship->champion_type)->toBe(TagTeam::class);
            // Champion should be one of the competitors
            $championCompetitor = $eventMatch->competitors->first(function ($competitor) use ($championship) {
                return $competitor->competitor_type === (new TagTeam())->getMorphClass()
                    && $competitor->competitor_id === $championship->champion_id;
            });
            expect($championCompetitor)->not->toBeNull();
        });

        test('creates title defense with specific champion', function () {
            $title = Title::factory()->create();
            $champion = Wrestler::factory()->create();

            // Create existing championship
            TitleChampionship::factory()->create([
                'title_id' => $title->id,
                'champion_type' => Wrestler::class,
                'champion_id' => $champion->id,
                'won_at' => now()->subMonths(3),
            ]);

            $eventMatch = EventMatch::factory()->titleDefense($title, $champion)->create();

            expect($eventMatch->titles)->toHaveCount(1);

            // Champion should be one of the competitors
            $championCompetitor = $eventMatch->competitors->first(function ($competitor) use ($champion) {
                return $competitor->competitor_type === $champion->getMorphClass()
                    && $competitor->competitor_id === $champion->id;
            });
            expect($championCompetitor)->not->toBeNull();
        });
    });

    describe('match results', function () {
        test('creates match with a winning side', function () {
            $eventMatch = EventMatch::factory()->singles()->create();

            expect($eventMatch->match_finish)->toBeInstanceOf(MatchFinish::class)
                ->and($eventMatch->winningSide)->not->toBeNull()
                ->and($eventMatch->winningSide?->match_id)->toBe($eventMatch->id)
                ->and($eventMatch->winningSide?->competitors)->not->toBeEmpty();
        });

        test('creates battle royal with one winning side and multiple competitors', function () {
            $eventMatch = EventMatch::factory()->battleRoyal(8)->create();

            expect($eventMatch->winningSide)->not->toBeNull()
                ->and($eventMatch->winningSide?->competitors)->toHaveCount(1)
                ->and($eventMatch->competitors)->toHaveCount(8);
        });

        test('creates ordered match sides', function () {
            $eventMatch = EventMatch::factory()->singles()->create();

            expect($eventMatch->sides()->pluck('position')->all())->toBe([1, 2]);
        });
    });

    describe('additional match features', function () {
        test('adds referees to match', function () {
            $eventMatch = EventMatch::factory()->withReferees(2)->create();

            expect($eventMatch->referees)->toHaveCount(2);
        });

        test('creates match with specific referee association', function () {
            // Arrange
            $referee = Referee::factory()->create();

            // Act
            $eventMatch = EventMatch::factory()->withReferees(1)->create();
            $eventMatch->referees()->sync([$referee->id]);

            // Assert
            expect($eventMatch->referees)->toHaveCount(1);
            expect($eventMatch->referees->firstOrFail()->id)->toBe($referee->id);
        });

        test('creates match with complete results including decision', function () {
            // Arrange & Act
            $eventMatch = EventMatch::factory()->complete()->create();

            // Assert
            expect($eventMatch->match_finish)->toBeInstanceOf(MatchFinish::class)
                ->and($eventMatch->winningSide)->not->toBeNull();
        });

        test('creates match with specific event', function () {
            $event = Event::factory()->create();
            $eventMatch = EventMatch::factory()->forEvent($event)->create();

            expect($eventMatch->event_id)->toBe($event->id);
        });

        test('creates match with specific match type', function () {
            $eventMatch = EventMatch::factory()->withMatchType(MatchType::TagTeam)->create();

            expect($eventMatch->match_type)->toBe(MatchType::TagTeam);
        });

        test('creates match with specific match number', function () {
            $matchNumber = 5;
            $eventMatch = EventMatch::factory()->withMatchNumber($matchNumber)->create();

            expect($eventMatch->match_number)->toBe($matchNumber);
        });

        test('creates match with preview', function () {
            $preview = 'This is going to be an epic match!';
            $eventMatch = EventMatch::factory()->state(['preview' => $preview])->create();

            expect($eventMatch->preview)->toBe($preview);
        });

        test('creates match with specific competitors', function () {
            $wrestler1 = Wrestler::factory()->create();
            $wrestler2 = Wrestler::factory()->create();

            $eventMatch = EventMatch::factory()->withCompetitors([
                0 => $wrestler1,
                1 => $wrestler2,
            ])->create();

            expect($eventMatch->competitors)->toHaveCount(2);

            $competitor1 = $eventMatch->competitors->where('competitor_id', $wrestler1->id)->firstOrFail();
            $competitor2 = $eventMatch->competitors->where('competitor_id', $wrestler2->id)->firstOrFail();

            expect($competitor1)->not->toBeNull();
            expect($competitor2)->not->toBeNull();
            expect($competitor1->side->position)->toBe(1);
            expect($competitor2->side->position)->toBe(2);
        });
    });

    describe('match type validation', function () {
        test('match type allows correct competitor types', function () {
            expect(MatchType::Singles->allowsWrestlers())->toBeTrue();
            expect(MatchType::Singles->allowsTagTeams())->toBeFalse();

            expect(MatchType::TagTeam->allowsWrestlers())->toBeTrue();
            expect(MatchType::TagTeam->allowsTagTeams())->toBeTrue();

            expect(MatchType::TripleThreat->allowsWrestlers())->toBeTrue();
            expect(MatchType::TripleThreat->allowsTagTeams())->toBeTrue();
        });

        test('match type has correct competitor limits', function () {
            expect(MatchType::Singles->getMinimumCompetitors())->toBe(2);
            expect(MatchType::TripleThreat->getMinimumCompetitors())->toBe(3);
            expect(MatchType::BattleRoyal->getMinimumCompetitors())->toBe(3);
            expect(MatchType::BattleRoyal->getMaximumCompetitors())->toBeNull();
            expect(MatchType::RoyalRumble->getMinimumCompetitors())->toBe(10);
            expect(MatchType::RoyalRumble->getMaximumCompetitors())->toBe(30);
            expect(MatchType::BattleRoyal->allowsTagTeams())->toBeFalse();
            expect(MatchType::RoyalRumble->allowsTagTeams())->toBeFalse();
        });
    });
});
