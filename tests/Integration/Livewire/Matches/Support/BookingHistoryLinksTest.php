<?php

declare(strict_types=1);

use App\Enums\Matches\MatchFinish;
use App\Enums\Matches\MatchType;
use App\Livewire\Matches\Support\BookingHistoryLinks;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Queries\Matches\BookingHistoryQuery;

describe('booking history links', function (): void {
    it('links existing competitors, the events of their matches and their head-to-head history', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        [$first, $second] = Wrestler::factory()->count(2)->for($promotion, 'promotion')->create()->all();
        $past = Event::factory()->for($promotion, 'promotion')->past()->create();
        EventMatch::factory()->for($past)->withCompetitors([$first, $second])->create(['match_finish' => MatchFinish::NoDecision]);
        $event = Event::factory()->for($promotion, 'promotion')->scheduled()->create();
        $snapshot = resolve(BookingHistoryQuery::class)->get($event, MatchType::Singles, [['wrestlers' => [$first->id]], ['wrestlers' => [$second->id]]]);

        // Act
        $linked = resolve(BookingHistoryLinks::class)->add($snapshot);

        // Assert
        expect(data_get($linked, 'subjects.0.url'))->toBe(route('wrestlers.show', $first))
            ->and(data_get($linked, 'subjects.0.recent.0.url'))->toBe(route('events.show', $past))
            ->and(data_get($linked, 'direct.url'))->toBe(route('wrestlers.show', [$first, 'opponent' => 'wrestler:'.$second->id]))
            ->and(data_get($linked, 'notices.0.url'))->toBe(route('wrestlers.show', [$first, 'opponent' => 'wrestler:'.$second->id]))
            ->and(data_get($linked, 'notices.0.sourceUrl'))->toBe(route('events.show', $past));
    });

    it('links tag team profiles to the tag team pages', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        [$first, $second] = TagTeam::factory()->count(2)->for($promotion, 'promotion')->create()->all();
        $past = Event::factory()->for($promotion, 'promotion')->past()->create();
        EventMatch::factory()->for($past)->withCompetitors([$first, $second])->create(['match_type' => MatchType::TagTeam, 'match_finish' => MatchFinish::NoDecision]);
        $event = Event::factory()->for($promotion, 'promotion')->scheduled()->create();
        $snapshot = resolve(BookingHistoryQuery::class)->get($event, MatchType::TagTeam, [['tag_teams' => [$first->id]], ['tag_teams' => [$second->id]]]);

        // Act
        $linked = resolve(BookingHistoryLinks::class)->add($snapshot);

        // Assert
        expect(data_get($linked, 'subjects.0.url'))->toBe(route('tag-teams.show', $first))
            ->and(data_get($linked, 'direct.url'))->toBe(route('tag-teams.show', [$first, 'opponent' => 'tag_team:'.$second->id]));
    });

    it('offers no profile link for a deleted competitor and filters from the one that remains', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        [$first, $second] = Wrestler::factory()->count(2)->for($promotion, 'promotion')->create()->all();
        $past = Event::factory()->for($promotion, 'promotion')->past()->create();
        EventMatch::factory()->for($past)->withCompetitors([$first, $second])->create(['match_finish' => MatchFinish::NoDecision]);
        $event = Event::factory()->for($promotion, 'promotion')->scheduled()->create();
        $selection = [['wrestlers' => [$first->id]], ['wrestlers' => [$second->id]]];
        $first->delete();
        $snapshot = resolve(BookingHistoryQuery::class)->get($event, MatchType::Singles, $selection);

        // Act
        $linked = resolve(BookingHistoryLinks::class)->add($snapshot);

        // Assert
        expect(data_get($linked, 'subjects.0.url'))->toBeNull()
            ->and(data_get($linked, 'direct.url'))->toBe(route('wrestlers.show', [$second, 'opponent' => 'wrestler:'.$first->id]));
    });

    it('offers no head-to-head link when both competitors are deleted', function (): void {
        // Arrange
        $promotion = Promotion::factory()->create();
        [$first, $second] = Wrestler::factory()->count(2)->for($promotion, 'promotion')->create()->all();
        $past = Event::factory()->for($promotion, 'promotion')->past()->create();
        EventMatch::factory()->for($past)->withCompetitors([$first, $second])->create(['match_finish' => MatchFinish::NoDecision]);
        $event = Event::factory()->for($promotion, 'promotion')->scheduled()->create();
        $selection = [['wrestlers' => [$first->id]], ['wrestlers' => [$second->id]]];
        $first->delete();
        $second->delete();
        $snapshot = resolve(BookingHistoryQuery::class)->get($event, MatchType::Singles, $selection);

        // Act
        $linked = resolve(BookingHistoryLinks::class)->add($snapshot);

        // Assert
        expect(data_get($linked, 'direct.url'))->toBeNull()
            ->and(data_get($linked, 'notices.0.url'))->toBeNull();
    });

    it('links nothing for an unavailable snapshot', function (): void {
        // Arrange
        $event = Event::factory()->unscheduled()->create();
        $snapshot = resolve(BookingHistoryQuery::class)->get($event, MatchType::Singles, []);

        // Act
        $linked = resolve(BookingHistoryLinks::class)->add($snapshot);

        // Assert
        expect($linked['available'])->toBeFalse()
            ->and($linked['subjects'])->toBeEmpty()
            ->and($linked['direct'])->toBeNull();
    });
});
