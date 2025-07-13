<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\EventMatchCompetitor;
use App\Models\Matches\EventMatchLoser;
use App\Models\Matches\EventMatchResult;
use App\Models\Matches\EventMatchWinner;
use App\Models\Matches\MatchType;
use App\Models\Referees\Referee;
use App\Models\TagTeams\TagTeam;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Models\Wrestlers\Wrestler;
use Database\Factories\Matches\EventMatchFactory;

/**
 * Unit tests for EventMatchFactory comprehensive match generation.
 *
 * These tests verify that the EventMatchFactory can generate complete event matches
 * with proper competitors, results, winners/losers, and title match support.
 */
describe('EventMatchFactory', function () {
    describe('basic match creation', function () {
        test('creates basic event match with default values', function () {
            $eventMatch = EventMatchFactory::new()->create();

            expect($eventMatch)->toBeInstanceOf(EventMatch::class);
            expect($eventMatch->event_id)->toBeInt();
            expect($eventMatch->match_number)->toBeInt();
            expect($eventMatch->match_type_id)->toBeInt();
            expect($eventMatch->preview)->toBeNull();
        });

        test('creates complete match with competitors and results', function () {
            $eventMatch = EventMatchFactory::new()->complete()->create();

            expect($eventMatch->competitors)->not->toBeEmpty();
            expect($eventMatch->result)->not->toBeNull();
            expect($eventMatch->winners)->not->toBeEmpty();
            expect($eventMatch->losers)->not->toBeEmpty();
        });
    });

    describe('match type specific factories', function () {
        test('creates singles match with wrestler competitors', function () {
            $eventMatch = EventMatchFactory::new()->singles()->create();

            expect($eventMatch->matchType->slug)->toBe('singles');
            expect($eventMatch->matchType->allowsWrestlers())->toBeTrue();
            expect($eventMatch->matchType->allowsTagTeams())->toBeFalse();
            expect($eventMatch->competitors)->toHaveCount(2);

            // All competitors should be wrestlers
            foreach ($eventMatch->competitors as $competitor) {
                expect($competitor->competitor_type)->toBe(Wrestler::class);
            }
        });

        test('creates tag team match with mixed competitors', function () {
            $eventMatch = EventMatchFactory::new()->tagTeam()->create();

            expect($eventMatch->matchType->slug)->toBe('tag-team');
            expect($eventMatch->matchType->allowsWrestlers())->toBeTrue();
            expect($eventMatch->matchType->allowsTagTeams())->toBeTrue();
            expect($eventMatch->competitors)->toHaveCount(2);

            // All competitors should be wrestlers or tag teams
            $allowedTypes = [Wrestler::class, TagTeam::class];
            foreach ($eventMatch->competitors as $competitor) {
                expect($allowedTypes)->toContain($competitor->competitor_type);
            }
        });

        test('creates triple threat match with 3 mixed competitors', function () {
            $eventMatch = EventMatchFactory::new()->tripleThreat()->create();

            expect($eventMatch->matchType->slug)->toBe('triple-threat');
            expect($eventMatch->matchType->getMinimumCompetitors())->toBe(3);
            expect($eventMatch->competitors)->toHaveCount(3);

            // All competitors should be wrestlers or tag teams
            $allowedTypes = [Wrestler::class, TagTeam::class];
            foreach ($eventMatch->competitors as $competitor) {
                expect($allowedTypes)->toContain($competitor->competitor_type);
            }
        });

        test('creates fatal four way match with 4 mixed competitors', function () {
            $eventMatch = EventMatchFactory::new()->fatalFourWay()->create();

            expect($eventMatch->matchType->slug)->toBe('fatal-4-way');
            expect($eventMatch->matchType->getMinimumCompetitors())->toBe(4);
            expect($eventMatch->competitors)->toHaveCount(4);

            // All competitors should be wrestlers or tag teams
            $allowedTypes = [Wrestler::class, TagTeam::class];
            foreach ($eventMatch->competitors as $competitor) {
                expect($allowedTypes)->toContain($competitor->competitor_type);
            }
        });

        test('creates battle royal with specified number of competitors', function () {
            $competitorCount = 15;
            $eventMatch = EventMatchFactory::new()->battleRoyal($competitorCount)->create();

            expect($eventMatch->matchType->slug)->toBe('battle-royal');
            expect($eventMatch->competitors)->toHaveCount($competitorCount);

            // All competitors should be wrestlers or tag teams
            $allowedTypes = [Wrestler::class, TagTeam::class];
            foreach ($eventMatch->competitors as $competitor) {
                expect($allowedTypes)->toContain($competitor->competitor_type);
            }
        });
    });

    describe('title match scenarios', function () {
        test('creates title match with championship implications', function () {
            $title = Title::factory()->create();
            $eventMatch = EventMatchFactory::new()->titleMatch($title)->create();

            expect($eventMatch->titles)->toHaveCount(1);
            expect($eventMatch->titles->first()->id)->toBe($title->id);
            expect($eventMatch->competitors)->not->toBeEmpty();
            expect($eventMatch->result)->not->toBeNull();
        });

        test('creates title defense with existing champion', function () {
            $title = Title::factory()->create(['type' => 'singles']);
            $eventMatch = EventMatchFactory::new()->titleDefense($title)->create();

            expect($eventMatch->titles)->toHaveCount(1);
            expect($eventMatch->titles->first()->id)->toBe($title->id);

            // Should have a championship record
            $championship = TitleChampionship::where('title_id', $title->id)->first();
            expect($championship)->not->toBeNull();
            expect($championship->champion_type)->toBe(Wrestler::class);

            // Champion should be one of the competitors
            $championCompetitor = $eventMatch->competitors->first(function ($competitor) use ($championship) {
                return $competitor->competitor_type === $championship->champion_type
                    && $competitor->competitor_id === $championship->champion_id;
            });
            expect($championCompetitor)->not->toBeNull();
        });

        test('creates tag team title defense with existing champion', function () {
            $title = Title::factory()->create(['type' => 'tag-team']);
            $eventMatch = EventMatchFactory::new()->titleDefense($title)->create();

            expect($eventMatch->titles)->toHaveCount(1);
            expect($eventMatch->titles->first()->id)->toBe($title->id);

            // Should have a championship record
            $championship = TitleChampionship::where('title_id', $title->id)->first();
            expect($championship)->not->toBeNull();
            expect($championship->champion_type)->toBe(TagTeam::class);

            // Champion should be one of the competitors
            $championCompetitor = $eventMatch->competitors->first(function ($competitor) use ($championship) {
                return $competitor->competitor_type === $championship->champion_type
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

            $eventMatch = EventMatchFactory::new()->titleDefense($title, $champion)->create();

            expect($eventMatch->titles)->toHaveCount(1);

            // Champion should be one of the competitors
            $championCompetitor = $eventMatch->competitors->first(function ($competitor) use ($champion) {
                return $competitor->competitor_type === Wrestler::class
                    && $competitor->competitor_id === $champion->id;
            });
            expect($championCompetitor)->not->toBeNull();
        });
    });

    describe('match results and winners/losers', function () {
        test('creates match with proper winner and loser distribution', function () {
            $eventMatch = EventMatchFactory::new()->singles()->create();

            expect($eventMatch->result)->not->toBeNull();
            expect($eventMatch->winners)->toHaveCount(1);
            expect($eventMatch->losers)->toHaveCount(1);

            // Winner and loser should be different
            $winner = $eventMatch->winners->first();
            $loser = $eventMatch->losers->first();
            expect($winner->winner_id)->not->toBe($loser->loser_id);
        });

        test('creates battle royal with one winner and multiple losers', function () {
            $eventMatch = EventMatchFactory::new()->battleRoyal(8)->create();

            expect($eventMatch->result)->not->toBeNull();
            expect($eventMatch->winners)->toHaveCount(1);
            expect($eventMatch->competitors)->toHaveCount(8);
            expect($eventMatch->losers)->toHaveCount(7);

            // Winner should not be in losers list (check both type and id)
            $winner = $eventMatch->winners->first();
            $winnerExists = $eventMatch->losers->contains(function ($loser) use ($winner) {
                return $loser->loser_type === $winner->winner_type && 
                       $loser->loser_id === $winner->winner_id;
            });
            
            expect($winnerExists)->toBeFalse();
        });

        test('creates match with proper competitor side numbers', function () {
            $eventMatch = EventMatchFactory::new()->singles()->create();

            $sideNumbers = $eventMatch->competitors->pluck('side_number')->sort()->values();
            expect($sideNumbers->all())->toBe([0, 1]);
        });
    });

    describe('additional match features', function () {
        test('adds referees to match', function () {
            $eventMatch = EventMatchFactory::new()->withReferees(2)->create();

            expect($eventMatch->referees)->toHaveCount(2);
        });

        test('creates match with specific event', function () {
            $event = Event::factory()->create();
            $eventMatch = EventMatchFactory::new()->forEvent($event)->create();

            expect($eventMatch->event_id)->toBe($event->id);
        });

        test('creates match with specific match type', function () {
            $matchType = MatchType::factory()->tagTeam()->create();
            $eventMatch = EventMatchFactory::new()->withMatchType($matchType)->create();

            expect($eventMatch->match_type_id)->toBe($matchType->id);
        });

        test('creates match with specific match number', function () {
            $matchNumber = 5;
            $eventMatch = EventMatchFactory::new()->withMatchNumber($matchNumber)->create();

            expect($eventMatch->match_number)->toBe($matchNumber);
        });

        test('creates match with preview', function () {
            $preview = 'This is going to be an epic match!';
            $eventMatch = EventMatchFactory::new()->state(['preview' => $preview])->create();

            expect($eventMatch->preview)->toBe($preview);
        });

        test('creates match with specific competitors', function () {
            $wrestler1 = Wrestler::factory()->create();
            $wrestler2 = Wrestler::factory()->create();

            $eventMatch = EventMatchFactory::new()->withCompetitors([
                0 => $wrestler1,
                1 => $wrestler2,
            ])->create();

            expect($eventMatch->competitors)->toHaveCount(2);

            $competitor1 = $eventMatch->competitors->where('competitor_id', $wrestler1->id)->first();
            $competitor2 = $eventMatch->competitors->where('competitor_id', $wrestler2->id)->first();

            expect($competitor1)->not->toBeNull();
            expect($competitor2)->not->toBeNull();
            expect($competitor1->side_number)->toBe(0);
            expect($competitor2->side_number)->toBe(1);
        });
    });

    describe('match type validation', function () {
        test('match type allows correct competitor types', function () {
            $singlesMatchType = MatchType::factory()->singles()->create();
            $tagTeamMatchType = MatchType::factory()->tagTeam()->create();
            $mixedMatchType = MatchType::factory()->mixed()->create();

            expect($singlesMatchType->allowsWrestlers())->toBeTrue();
            expect($singlesMatchType->allowsTagTeams())->toBeFalse();

            expect($tagTeamMatchType->allowsWrestlers())->toBeTrue();
            expect($tagTeamMatchType->allowsTagTeams())->toBeTrue();

            expect($mixedMatchType->allowsWrestlers())->toBeTrue();
            expect($mixedMatchType->allowsTagTeams())->toBeTrue();
        });

        test('match type has correct competitor limits', function () {
            $singlesMatchType = MatchType::factory()->singles()->create();
            $tripleThreatMatchType = MatchType::factory()->tripleThreat()->create();
            $battleRoyalMatchType = MatchType::factory()->battleRoyal()->create();

            expect($singlesMatchType->getMinimumCompetitors())->toBe(2);
            expect($tripleThreatMatchType->getMinimumCompetitors())->toBe(3);
            expect($battleRoyalMatchType->getMinimumCompetitors())->toBe(2);
        });
    });
});
