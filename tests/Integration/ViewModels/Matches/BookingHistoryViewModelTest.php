<?php

declare(strict_types=1);

use App\Actions\Matches\RecordResultAction;
use App\Data\Matches\MatchResultData;
use App\Enums\Matches\MatchFinish;
use App\Enums\Matches\MatchType;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchSide;
use App\Models\Promotions\Promotion;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\ValueObjects\CompetitorSelection;
use App\ViewModels\Matches\BookingHistoryViewModel;

it('shows direct wins and incomplete bookings without inferring a result', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    [$first, $second] = Wrestler::factory()->count(2)->for($promotion, 'promotion')->create()->all();
    $past = Event::factory()->for($promotion, 'promotion')->create(['date' => now()->subDays(4)]);
    $target = Event::factory()->for($promotion, 'promotion')->scheduled()->create();
    $won = EventMatch::factory()->for($past)->withCompetitors([$first, $second])->create();
    $won->update(['match_finish' => MatchFinish::Pinfall, 'winning_side_id' => $won->sides()->firstOrFail()->id]);
    EventMatch::factory()->for($past)->withCompetitors([$first, $second])->create();

    // Act
    $history = resolve(BookingHistoryViewModel::class)->snapshot($target, MatchType::Singles, CompetitorSelection::fromSides(MatchType::Singles, [
        ['wrestlers' => [$first->id]], ['wrestlers' => [$second->id]],
    ]));

    // Assert
    expect($history['direct'])->toMatchArray([
        'firstWins' => 1, 'secondWins' => 0, 'draws' => 0, 'neutral' => 0, 'incomplete' => 1, 'recorded' => 1,
    ])->and($history['subjects'][0]['recent'])->toHaveCount(2)
        ->and($history['subjects'][0]['latestRecordedDate'])->toBe(now()->subDays(4)->toDateString())
        ->and($history['notices'])->toHaveCount(1)
        ->and($history['notices'][0]['recorded'])->toBe(1)
        ->and($history['notices'][0]['incomplete'])->toBe(1);
});

it('counts supported finishes and swapped sides from the stored outcome', function (MatchFinish $finish, array $expected): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    [$first, $second] = Wrestler::factory()->count(2)->for($promotion, 'promotion')->create()->all();
    $event = Event::factory()->for($promotion, 'promotion')->past()->create();
    $match = EventMatch::factory()->for($event)->withCompetitors([$second, $first])->create();
    $match->update(['match_finish' => $finish, 'winning_side_id' => $finish->requiresWinningSide() ? $match->sides()->firstOrFail()->id : null]);
    $target = Event::factory()->for($promotion, 'promotion')->scheduled()->create();

    // Act
    $history = resolve(BookingHistoryViewModel::class)->snapshot($target, MatchType::Singles, CompetitorSelection::fromSides(MatchType::Singles, [
        ['wrestlers' => [$first->id]], ['wrestlers' => [$second->id]],
    ]));

    // Assert
    expect($history['direct'])->toMatchArray($expected + ['recorded' => 1, 'incomplete' => 0]);
})->with([
    'pinfall' => [MatchFinish::Pinfall, ['firstWins' => 0, 'secondWins' => 1, 'draws' => 0, 'neutral' => 0]],
    'submission' => [MatchFinish::Submission, ['firstWins' => 0, 'secondWins' => 1, 'draws' => 0, 'neutral' => 0]],
    'disqualification' => [MatchFinish::Disqualification, ['firstWins' => 0, 'secondWins' => 1, 'draws' => 0, 'neutral' => 0]],
    'countout' => [MatchFinish::Countout, ['firstWins' => 0, 'secondWins' => 1, 'draws' => 0, 'neutral' => 0]],
    'knockout' => [MatchFinish::Knockout, ['firstWins' => 0, 'secondWins' => 1, 'draws' => 0, 'neutral' => 0]],
    'stipulation' => [MatchFinish::Stipulation, ['firstWins' => 0, 'secondWins' => 1, 'draws' => 0, 'neutral' => 0]],
    'forfeit' => [MatchFinish::Forfeit, ['firstWins' => 0, 'secondWins' => 1, 'draws' => 0, 'neutral' => 0]],
    'draw' => [MatchFinish::TimeLimitDraw, ['firstWins' => 0, 'secondWins' => 0, 'draws' => 1, 'neutral' => 0]],
    'no decision' => [MatchFinish::NoDecision, ['firstWins' => 0, 'secondWins' => 0, 'draws' => 0, 'neutral' => 1]],
]);

it('keeps wrestler and team identities separate even when their numeric ids match', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $wrestler = Wrestler::factory()->for($promotion, 'promotion')->create();
    $team = TagTeam::factory()->for($promotion, 'promotion')->create(['id' => $wrestler->id]);
    $target = Event::factory()->for($promotion, 'promotion')->scheduled()->create();

    // Act
    $history = resolve(BookingHistoryViewModel::class)->snapshot($target, MatchType::Gauntlet, CompetitorSelection::fromSides(MatchType::Gauntlet, [
        ['wrestlers' => [$wrestler->id]], ['tag_teams' => [$team->id]],
    ]));

    // Assert
    expect(array_column($history['subjects'], 'key'))->toBe(['wrestler:'.$wrestler->id, 'tag_team:'.$team->id])
        ->and($history['direct'])->toBeNull();
});

it('limits each preview independently while calculating totals over the full recorded history', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    [$first, $second] = Wrestler::factory()->count(2)->for($promotion, 'promotion')->create()->all();
    $event = Event::factory()->for($promotion, 'promotion')->past()->create();

    foreach (range(1, 8) as $number) {
        $match = EventMatch::factory()->for($event)->withCompetitors([$first, $second])->create(['match_number' => $number]);
        $match->update(['match_finish' => MatchFinish::Pinfall, 'winning_side_id' => $match->sides()->firstOrFail()->id]);
    }

    $target = Event::factory()->for($promotion, 'promotion')->scheduled()->create();

    // Act
    $history = resolve(BookingHistoryViewModel::class)->snapshot($target, MatchType::Singles, CompetitorSelection::fromSides(MatchType::Singles, [
        ['wrestlers' => [$first->id]], ['wrestlers' => [$second->id]],
    ]));

    // Assert
    expect($history['direct'])->toMatchArray(['firstWins' => 8])
        ->and($history['subjects'][0]['recent'])->toHaveCount(5)
        ->and($history['subjects'][1]['recent'])->toHaveCount(5)
        ->and($history['notices'][0]['recorded'])->toBe(8);
});

it('excludes later and deleted source records and keeps the local 90-day boundary inclusive', function (): void {
    // Arrange
    \Pest\Laravel\travelTo('2026-04-02 20:00:00');
    $promotion = Promotion::factory()->create(['timezone' => 'America/New_York']);
    [$first, $second] = Wrestler::factory()->count(2)->for($promotion, 'promotion')->create()->all();
    $target = Event::factory()->for($promotion, 'promotion')->create(['date' => '2026-04-01 16:00:00']);
    $boundary = Event::factory()->for($promotion, 'promotion')->create(['date' => '2026-01-01 17:00:00']);
    $older = Event::factory()->for($promotion, 'promotion')->create(['date' => '2026-01-01 16:59:59']);

    foreach ([$boundary, $older, $target] as $event) {
        $match = EventMatch::factory()->for($event)->withCompetitors([$first, $second])->create();
        $match->update(['match_finish' => MatchFinish::NoDecision]);
    }

    $deleted = EventMatch::factory()->for($boundary)->withCompetitors([$first, $second])->trashed()->create();
    $deleted->update(['match_finish' => MatchFinish::NoDecision]);
    $deletedEvent = Event::factory()->for($promotion, 'promotion')->past()->trashed()->create();
    EventMatch::factory()->for($deletedEvent)->withCompetitors([$first, $second])->create();

    // Act
    $history = resolve(BookingHistoryViewModel::class)->snapshot($target, MatchType::Singles, CompetitorSelection::fromSides(MatchType::Singles, [
        ['wrestlers' => [$first->id]], ['wrestlers' => [$second->id]],
    ]));

    // Assert
    expect($history['direct'])->toMatchArray(['neutral' => 2])
        ->and($history['notices'][0]['recorded'])->toBe(1)
        ->and($history['subjects'][0]['recent'])->toHaveCount(2);
});

it('keeps a finish on an incomplete match structure out of completed history', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    [$first, $second] = Wrestler::factory()->count(2)->for($promotion, 'promotion')->create()->all();
    $event = Event::factory()->for($promotion, 'promotion')->past()->create();
    EventMatch::factory()->for($event)->withCompetitors([$first])->create(['match_finish' => MatchFinish::TimeLimitDraw]);
    $target = Event::factory()->for($promotion, 'promotion')->scheduled()->create();

    // Act
    $history = resolve(BookingHistoryViewModel::class)->snapshot($target, MatchType::Singles, CompetitorSelection::fromSides(MatchType::Singles, [
        ['wrestlers' => [$first->id]], ['wrestlers' => [$second->id]],
    ]));

    // Assert
    expect($history['subjects'][0]['latestRecordedDate'])->toBeNull()
        ->and($history['subjects'][0]['recent'][0]['recorded'])->toBeFalse();
});

it('reports an unowned event as unavailable rather than empty history', function (): void {
    // Arrange
    $event = Event::factory()->unscheduled()->create();

    // Act
    $history = resolve(BookingHistoryViewModel::class)->snapshot($event, MatchType::Singles, CompetitorSelection::fromSides(MatchType::Singles, []));

    // Assert
    expect($history['available'])->toBeFalse()->and($history['direct'])->toBeNull();
});

it('keeps named-team results with the team after a membership changes', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    [$first, $second] = TagTeam::factory()->count(2)->for($promotion, 'promotion')->create()->all();
    [$former, $current] = Wrestler::factory()->count(2)->for($promotion, 'promotion')->create()->all();
    $first->wrestlers()->attach($former, ['joined_at' => now()->subMonths(2), 'left_at' => now()->subDays(2)]);
    $first->wrestlers()->attach($current, ['joined_at' => now()->subDay()]);
    $past = Event::factory()->for($promotion, 'promotion')->create(['date' => now()->subDays(3)]);
    $match = EventMatch::factory()->for($past)->withCompetitors([$first, $second])->create(['match_type' => MatchType::TagTeam]);
    $match->update(['match_finish' => MatchFinish::Countout, 'winning_side_id' => $match->sides()->firstOrFail()->id]);
    $target = Event::factory()->for($promotion, 'promotion')->scheduled()->create();

    // Act
    $teamHistory = resolve(BookingHistoryViewModel::class)->snapshot($target, MatchType::TagTeam, CompetitorSelection::fromSides(MatchType::TagTeam, [
        ['tag_teams' => [$first->id]], ['tag_teams' => [$second->id]],
    ]));
    $individualHistory = resolve(BookingHistoryViewModel::class)->snapshot($target, MatchType::Singles, CompetitorSelection::fromSides(MatchType::Singles, [
        ['wrestlers' => [$former->id]], ['wrestlers' => [$current->id]],
    ]));

    // Assert
    expect($teamHistory['direct'])->toMatchArray(['firstWins' => 1])
        ->and($individualHistory['direct'])->toMatchArray(['recorded' => 0])
        ->and($individualHistory['subjects'][0]['recent'])->toBeEmpty()
        ->and($individualHistory['subjects'][1]['recent'])->toBeEmpty();
});

it('paginates opposing pairs from large individual-entrant formats without turning them into direct wins', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    $wrestlers = Wrestler::factory()->count(4)->for($promotion, 'promotion')->create();
    $past = Event::factory()->for($promotion, 'promotion')->past()->create();
    $match = EventMatch::factory()->for($past)->withCompetitors($wrestlers->all())->create(['match_type' => MatchType::BattleRoyal]);
    $match->update(['match_finish' => MatchFinish::Stipulation, 'winning_side_id' => $match->sides()->firstOrFail()->id]);
    $event = Event::factory()->for($promotion, 'promotion')->scheduled()->create();

    // Act
    $history = resolve(BookingHistoryViewModel::class)->snapshot($event, MatchType::BattleRoyal, CompetitorSelection::fromSides(MatchType::BattleRoyal, [['wrestlers' => $wrestlers->modelKeys()]]), page: 2);

    // Assert
    expect($history['direct'])->toBeNull()->and($history['totalNotices'])->toBe(6)
        ->and($history['pages'])->toBe(2)->and($history['page'])->toBe(2)
        ->and($history['notices'])->toHaveCount(1);
});

it('never counts former opponents as a repeated opposing pairing when they are selected as partners', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    [$first, $partner, $other, $otherPartner] = Wrestler::factory()->count(4)->for($promotion, 'promotion')->create()->all();
    $past = Event::factory()->for($promotion, 'promotion')->past()->create();
    EventMatch::factory()->for($past)->withCompetitors([$first, $partner])->create();
    $event = Event::factory()->for($promotion, 'promotion')->scheduled()->create();

    // Act
    $history = resolve(BookingHistoryViewModel::class)->snapshot($event, MatchType::TagTeam, CompetitorSelection::fromSides(MatchType::TagTeam, [
        ['wrestlers' => [$first->id, $partner->id]], ['wrestlers' => [$other->id, $otherPartner->id]],
    ]));

    // Assert
    expect($history['direct'])->toBeNull()->and($history['totalNotices'])->toBe(0);
});

it('uses a bounded number of relation queries as history grows', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    [$first, $second] = Wrestler::factory()->count(2)->for($promotion, 'promotion')->create()->all();
    $past = Event::factory()->for($promotion, 'promotion')->past()->create();
    EventMatch::factory()->for($past)->withCompetitors([$first, $second])->create();
    $target = Event::factory()->for($promotion, 'promotion')->scheduled()->create();
    $target->load('promotion');
    $selection = [['wrestlers' => [$first->id]], ['wrestlers' => [$second->id]]];
    $one = recordStatements(fn (): array => resolve(BookingHistoryViewModel::class)->snapshot($target, MatchType::Singles, CompetitorSelection::fromSides(MatchType::Singles, $selection)));

    foreach (range(1, 25) as $number) {
        EventMatch::factory()->for($past)->withCompetitors([$first, $second])->create();
    }

    // Act
    $many = recordStatements(fn (): array => resolve(BookingHistoryViewModel::class)->snapshot($target, MatchType::Singles, CompetitorSelection::fromSides(MatchType::Singles, $selection)));

    // Assert
    expect($many)->toHaveSameSize($one);

});

it('retains deleted competitors and marks them deleted', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    [$first, $second] = Wrestler::factory()->count(2)->for($promotion, 'promotion')->create()->all();
    $past = Event::factory()->for($promotion, 'promotion')->past()->create();
    EventMatch::factory()->for($past)->withCompetitors([$first, $second])->create(['match_finish' => MatchFinish::NoDecision]);
    $event = Event::factory()->for($promotion, 'promotion')->scheduled()->create();
    $selection = [['wrestlers' => [$first->id]], ['wrestlers' => [$second->id]]];
    $first->delete();

    // Act
    $oneDeleted = resolve(BookingHistoryViewModel::class)->snapshot($event, MatchType::Singles, CompetitorSelection::fromSides(MatchType::Singles, $selection));

    // Assert
    expect($oneDeleted['subjects'][0])->toMatchArray(['deleted' => true, 'type' => 'wrestler', 'id' => $first->id])
        ->and($oneDeleted['direct'])->toMatchArray(['neutral' => 1, 'pair' => [
            'first' => ['type' => 'wrestler', 'id' => $first->id, 'deleted' => true],
            'second' => ['type' => 'wrestler', 'id' => $second->id, 'deleted' => false],
        ]]);

    // Act
    $second->delete();
    $bothDeleted = resolve(BookingHistoryViewModel::class)->snapshot($event, MatchType::Singles, CompetitorSelection::fromSides(MatchType::Singles, $selection));

    // Assert
    expect(data_get($bothDeleted, 'direct.pair'))->toMatchArray([
        'first' => ['type' => 'wrestler', 'id' => $first->id, 'deleted' => true],
        'second' => ['type' => 'wrestler', 'id' => $second->id, 'deleted' => true],
    ])
        ->and($bothDeleted['notices'][0]['pair']['second']['deleted'])->toBeTrue();
});

it('redacts a legacy foreign competitor and does not treat its result as complete', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    [$first, $second] = Wrestler::factory()->count(2)->for($promotion, 'promotion')->create()->all();
    $foreign = Wrestler::factory()->for(Promotion::factory(), 'promotion')->create(['name' => 'Foreign Match Secret']);
    $past = Event::factory()->for($promotion, 'promotion')->past()->create();
    EventMatch::factory()->for($past)->withCompetitors([$first, $foreign])->create(['match_finish' => MatchFinish::NoDecision]);
    $event = Event::factory()->for($promotion, 'promotion')->scheduled()->create();

    // Act
    $history = resolve(BookingHistoryViewModel::class)->snapshot($event, MatchType::Singles, CompetitorSelection::fromSides(MatchType::Singles, [
        ['wrestlers' => [$first->id]], ['wrestlers' => [$second->id]],
    ]));

    // Assert
    expect($history['subjects'][0]['recent'][0]['recorded'])->toBeFalse()
        ->and(array_column($history['subjects'][0]['recent'][0]['sides'], 'name'))->not->toContain('Foreign Match Secret')
        ->toContain(__('matches.history.record_unavailable'));
});

it('reflects a corrected result on the next check without copying historical records', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    [$first, $second] = Wrestler::factory()->count(2)->for($promotion, 'promotion')->create()->all();
    $past = Event::factory()->for($promotion, 'promotion')->past()->create();
    $match = EventMatch::factory()->for($past)->withCompetitors([$first, $second])->create();
    $match->update(['match_finish' => MatchFinish::Pinfall, 'winning_side_id' => $match->sides()->firstOrFail()->id]);
    $event = Event::factory()->for($promotion, 'promotion')->scheduled()->create();
    $selection = [['wrestlers' => [$first->id]], ['wrestlers' => [$second->id]]];
    $before = resolve(BookingHistoryViewModel::class)->snapshot($event, MatchType::Singles, CompetitorSelection::fromSides(MatchType::Singles, $selection));
    resolve(RecordResultAction::class)->handle($match, new MatchResultData(
        MatchFinish::TimeLimitDraw,
        null,
        collect(),
    ), administrator());

    // Act
    $after = resolve(BookingHistoryViewModel::class)->snapshot($event, MatchType::Singles, CompetitorSelection::fromSides(MatchType::Singles, $selection));

    // Assert
    expect($before['direct'])->toMatchArray(['firstWins' => 1, 'draws' => 0])
        ->and($after['direct'])->toMatchArray(['firstWins' => 0, 'draws' => 1])
        ->and(EventMatch::query()->count())->toBe(1);
});

it('links the latest dated meeting rather than a later-created backfill', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    [$first, $second] = Wrestler::factory()->count(2)->for($promotion, 'promotion')->create()->all();
    $latest = Event::factory()->for($promotion, 'promotion')->create(['name' => 'Latest Dated Meeting', 'date' => now()->subDay()]);
    $older = Event::factory()->for($promotion, 'promotion')->create(['name' => 'Later Created Backfill', 'date' => now()->subDays(20)]);
    EventMatch::factory()->for($latest)->withCompetitors([$first, $second])->create(['match_finish' => MatchFinish::NoDecision]);
    EventMatch::factory()->for($older)->withCompetitors([$first, $second])->create(['match_finish' => MatchFinish::TimeLimitDraw]);
    $event = Event::factory()->for($promotion, 'promotion')->scheduled()->create();

    // Act
    $history = resolve(BookingHistoryViewModel::class)->snapshot($event, MatchType::Singles, CompetitorSelection::fromSides(MatchType::Singles, [
        ['wrestlers' => [$first->id]], ['wrestlers' => [$second->id]],
    ]));

    // Assert
    expect($history['notices'][0])->toMatchArray([
        'event' => 'Latest Dated Meeting', 'eventId' => $latest->id, 'finish' => 'No Decision', 'recorded' => 2,
    ]);
});

it('keeps malformed winner metadata out of the known head-to-head record', function (string $scenario): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    [$first, $second] = Wrestler::factory()->count(2)->for($promotion, 'promotion')->create()->all();
    $past = Event::factory()->for($promotion, 'promotion')->past()->create();
    $match = EventMatch::factory()->for($past)->withCompetitors([$first, $second])->create();
    $other = EventMatch::factory()->for($past)->create();
    $foreignSide = MatchSide::factory()->for($other, 'match')->create();
    $side = match ($scenario) {
        'foreign side' => $foreignSide->id,
        'missing side' => null,
        default => $match->sides()->firstOrFail()->id,
    };
    $match->update(['match_finish' => $scenario === 'unexpected neutral winner' ? MatchFinish::NoDecision : MatchFinish::Pinfall, 'winning_side_id' => $side]);
    $target = Event::factory()->for($promotion, 'promotion')->scheduled()->create();

    // Act
    $history = resolve(BookingHistoryViewModel::class)->snapshot($target, MatchType::Singles, CompetitorSelection::fromSides(MatchType::Singles, [
        ['wrestlers' => [$first->id]], ['wrestlers' => [$second->id]],
    ]));

    // Assert
    expect($history['direct'])->toMatchArray(['recorded' => 0, 'firstWins' => 0, 'secondWins' => 0, 'neutral' => 0, 'incomplete' => 1])
        ->and($history['subjects'][0]['latestRecordedDate'])->toBeNull()
        ->and($history['subjects'][0]['recent'][0]['recorded'])->toBeFalse();
})->with(['foreign side', 'missing side', 'unexpected neutral winner']);

it('returns an available but empty history when the selection has never met', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    [$first, $second] = Wrestler::factory()->count(2)->for($promotion, 'promotion')->create()->all();
    $target = Event::factory()->for($promotion, 'promotion')->scheduled()->create();

    // Act
    $history = resolve(BookingHistoryViewModel::class)->snapshot($target, MatchType::Singles, CompetitorSelection::fromSides(MatchType::Singles, [
        ['wrestlers' => [$first->id]], ['wrestlers' => [$second->id]],
    ]));

    // Assert
    expect($history)->toMatchArray(['available' => true, 'notices' => [], 'totalNotices' => 0, 'page' => 1, 'pages' => 1])
        ->and($history['direct'])->toMatchArray(['recorded' => 0, 'incomplete' => 0])
        ->and($history['subjects'][0]['recent'])->toBeEmpty()
        ->and($history['subjects'][0]['latestRecordedDate'])->toBeNull();
});

it('lists the pair that met most recently first, whatever order the meetings were created in', function (): void {
    // Arrange
    $promotion = Promotion::factory()->create();
    [$subject, $older, $newer] = Wrestler::factory()->count(3)->for($promotion, 'promotion')->create()->all();
    $oldEvent = Event::factory()->for($promotion, 'promotion')->create(['date' => now()->subDays(30)]);
    $recentEvent = Event::factory()->for($promotion, 'promotion')->create(['date' => now()->subDays(3)]);
    EventMatch::factory()->for($oldEvent)->withCompetitors([$subject, $older])->create();
    EventMatch::factory()->for($recentEvent)->withCompetitors([$subject, $newer])->create();
    $target = Event::factory()->for($promotion, 'promotion')->scheduled()->create();

    // Act
    $history = resolve(BookingHistoryViewModel::class)->snapshot($target, MatchType::BattleRoyal, CompetitorSelection::fromSides(MatchType::BattleRoyal, [
        ['wrestlers' => [$subject->id, $older->id, $newer->id]],
    ]));

    // Assert
    expect(array_column($history['notices'], 'event'))->toBe([$recentEvent->name, $oldEvent->name])
        ->and(array_column($history['notices'], 'second'))->toBe([$newer->name, $older->name]);
});
