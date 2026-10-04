<?php

declare(strict_types=1);

use App\Actions\Matches\RecordResultAction;
use App\Actions\Titles\DeleteAction as DeleteTitleAction;
use App\Actions\Titles\PullAction;
use App\Actions\Titles\RetireAction as RetireTitleAction;
use App\Actions\Wrestlers\DeleteAction as DeleteWrestlerAction;
use App\Actions\Wrestlers\ReleaseAction as ReleaseWrestlerAction;
use App\Actions\Wrestlers\RetireAction as RetireWrestlerAction;
use App\Data\Matches\MatchEliminationData;
use App\Data\Matches\MatchResultData;
use App\Enums\MatchFinish;
use App\Enums\MatchType;
use App\Enums\Titles\TitleType;
use App\Exceptions\Matches\InvalidMatchOutcomeException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

function sideWithCompetitor(
    EventMatch $match,
    int $position,
    ?int $entryOrder = null,
    Wrestler|TagTeam|null $competitor = null,
): MatchSide {
    $side = MatchSide::factory()->create([
        'match_id' => $match->id,
        'position' => $position,
    ]);

    $matchCompetitorAttributes = [
        'match_id' => $match->id,
        'match_side_id' => $side->id,
    ];

    if ($competitor !== null) {
        $matchCompetitorAttributes += [
            'competitor_type' => $competitor->getMorphClass(),
            'competitor_id' => $competitor->id,
        ];
    }

    $matchCompetitor = MatchCompetitor::factory()->create($matchCompetitorAttributes);
    $matchCompetitor->forceFill(['entry_order' => $entryOrder])->save();

    return $side;
}

/**
 * @return array{EventMatch, list<MatchCompetitor>}
 */
function eliminationMatch(MatchType $matchType, int $competitorCount = 3): array
{
    $match = EventMatch::factory()->create(['match_type' => $matchType]);
    $competitors = [];

    foreach (range(1, $competitorCount) as $position) {
        $side = sideWithCompetitor(
            $match,
            $position,
            $matchType === MatchType::RoyalRumble ? $position : null,
        );
        $competitors[] = $side->competitors()->firstOrFail();
    }

    return [$match, $competitors];
}

/**
 * @param  array<int, MatchEliminationData>  $eliminations
 */
function matchResult(
    MatchFinish $finish,
    ?MatchSide $winningSide,
    array $eliminations = [],
): MatchResultData {
    return new MatchResultData($finish, $winningSide, collect($eliminations));
}

it('records an ordinary finish and winning side', function () {
    $match = EventMatch::factory()->create();
    $winningSide = sideWithCompetitor($match, 1);

    $resultedMatch = resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Pinfall, $winningSide),
    );

    expect($resultedMatch->match_finish)->toBe(MatchFinish::Pinfall)
        ->and($resultedMatch->winningSide?->is($winningSide))->toBeTrue();
});

it('records a draw without a winning side', function () {
    $match = EventMatch::factory()->create();

    $resultedMatch = resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::TimeLimitDraw, null),
    );

    expect($resultedMatch->match_finish)->toBe(MatchFinish::TimeLimitDraw)
        ->and($resultedMatch->winningSide)->toBeNull();
});

it('records a complete battle royal elimination history', function () {
    [$match, $competitors] = eliminationMatch(MatchType::BattleRoyal);
    $winner = $competitors[2];
    $firstEliminated = $competitors[0];
    $secondEliminated = $competitors[1];

    resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Stipulation, $winner->side, [
            new MatchEliminationData($firstEliminated, 1, $winner),
            new MatchEliminationData($secondEliminated, 2, $winner),
        ]),
    );

    expect($firstEliminated->refresh()->elimination_order)->toBe(1)
        ->and($firstEliminated->eliminated_by_match_competitor_id)->toBe($winner->id)
        ->and($secondEliminated->refresh()->elimination_order)->toBe(2)
        ->and($secondEliminated->eliminated_by_match_competitor_id)->toBe($winner->id)
        ->and($winner->refresh()->elimination_order)->toBeNull();
});

it('records a royal rumble outcome with entry and elimination order', function () {
    [$match, $competitors] = eliminationMatch(MatchType::RoyalRumble);
    $winner = $competitors[2];

    resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Stipulation, $winner->side, [
            new MatchEliminationData($competitors[0], 1, $winner),
            new MatchEliminationData($competitors[1], 2, $winner),
        ]),
    );

    expect($match->competitors()->orderBy('entry_order')->pluck('entry_order')->all())->toBe([1, 2, 3])
        ->and($match->competitors()->orderBy('elimination_order')->pluck('elimination_order')->filter()->values()->all())
        ->toBe([1, 2]);
});

it('records a partial elimination history for a no-outcome elimination match', function () {
    [$match, $competitors] = eliminationMatch(MatchType::BattleRoyal);

    $resultedMatch = resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::NoDecision, null, [
            new MatchEliminationData($competitors[0], 1, $competitors[2]),
        ]),
    );

    expect($resultedMatch->match_finish)->toBe(MatchFinish::NoDecision)
        ->and($resultedMatch->winning_side_id)->toBeNull()
        ->and($competitors[0]->refresh()->elimination_order)->toBe(1)
        ->and($competitors[1]->refresh()->elimination_order)->toBeNull();
});

it('replaces a previously recorded outcome atomically', function () {
    [$match, $competitors] = eliminationMatch(MatchType::BattleRoyal);
    $firstWinner = $competitors[2];
    $correctedWinner = $competitors[1];

    resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Stipulation, $firstWinner->side, [
            new MatchEliminationData($competitors[0], 1, $firstWinner),
            new MatchEliminationData($correctedWinner, 2, $firstWinner),
        ]),
    );

    $correctedMatch = resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Forfeit, $correctedWinner->side, [
            new MatchEliminationData($firstWinner, 1),
            new MatchEliminationData($competitors[0], 2, $correctedWinner),
        ]),
    );

    expect($correctedMatch->match_finish)->toBe(MatchFinish::Forfeit)
        ->and($correctedMatch->winning_side_id)->toBe($correctedWinner->match_side_id)
        ->and($firstWinner->refresh()->elimination_order)->toBe(1)
        ->and($firstWinner->eliminated_by_match_competitor_id)->toBeNull()
        ->and($correctedWinner->refresh()->elimination_order)->toBeNull();
});

it('rolls back an invalid correction without changing the recorded outcome', function () {
    [$match, $competitors] = eliminationMatch(MatchType::BattleRoyal);
    $winner = $competitors[2];

    resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Stipulation, $winner->side, [
            new MatchEliminationData($competitors[0], 1, $winner),
            new MatchEliminationData($competitors[1], 2, $winner),
        ]),
    );

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::NoDecision, null, [
            new MatchEliminationData($competitors[0], 2),
        ]),
    ))->toThrow(InvalidMatchOutcomeException::class)
        ->and($match->refresh()->match_finish)->toBe(MatchFinish::Stipulation)
        ->and($match->winning_side_id)->toBe($winner->match_side_id)
        ->and($competitors[0]->refresh()->elimination_order)->toBe(1)
        ->and($competitors[1]->refresh()->elimination_order)->toBe(2);
});

it('requires a winning side for a decisive finish', function () {
    $match = EventMatch::factory()->create();

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Submission, null),
    ))->toThrow(InvalidMatchOutcomeException::class);
});

it('rejects a winning side for a no-outcome finish', function () {
    $match = EventMatch::factory()->create();
    $side = sideWithCompetitor($match, 1);

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::NoDecision, $side),
    ))->toThrow(InvalidMatchOutcomeException::class);
});

it('rejects a side belonging to another match', function () {
    $match = EventMatch::factory()->create();
    $otherMatch = EventMatch::factory()->create();
    $otherSide = sideWithCompetitor($otherMatch, 1);

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Pinfall, $otherSide),
    ))->toThrow(InvalidMatchOutcomeException::class);
});

it('rejects an empty winning side', function () {
    $match = EventMatch::factory()->create();
    $emptySide = MatchSide::factory()->for($match, 'match')->create(['position' => 1]);

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Pinfall, $emptySide),
    ))->toThrow(InvalidMatchOutcomeException::class);
});

it('rejects elimination metadata for an ordinary match', function () {
    $match = EventMatch::factory()->create();
    $winningSide = sideWithCompetitor($match, 1);
    $competitor = $match->competitors()->firstOrFail();

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Pinfall, $winningSide, [
            new MatchEliminationData($competitor, 1),
        ]),
    ))->toThrow(InvalidMatchOutcomeException::class);
});

it('requires every losing competitor in a decisive elimination match', function () {
    [$match, $competitors] = eliminationMatch(MatchType::BattleRoyal);
    $winner = $competitors[2];

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Stipulation, $winner->side, [
            new MatchEliminationData($competitors[0], 1, $winner),
        ]),
    ))->toThrow(InvalidMatchOutcomeException::class);
});

it('rejects eliminating the winning competitor', function () {
    [$match, $competitors] = eliminationMatch(MatchType::BattleRoyal);
    $winner = $competitors[2];

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Stipulation, $winner->side, [
            new MatchEliminationData($competitors[0], 1, $winner),
            new MatchEliminationData($winner, 2, $competitors[1]),
        ]),
    ))->toThrow(InvalidMatchOutcomeException::class);
});

it('rejects an eliminated competitor from another match', function () {
    [$match, $competitors] = eliminationMatch(MatchType::BattleRoyal);
    $otherMatch = EventMatch::factory()->create();
    $otherCompetitor = sideWithCompetitor($otherMatch, 1)->competitors()->firstOrFail();
    $winner = $competitors[2];

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Stipulation, $winner->side, [
            new MatchEliminationData($otherCompetitor, 1, $winner),
            new MatchEliminationData($competitors[1], 2, $winner),
        ]),
    ))->toThrow(InvalidMatchOutcomeException::class);
});

it('rejects a self elimination', function () {
    [$match, $competitors] = eliminationMatch(MatchType::BattleRoyal);
    $winner = $competitors[2];

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Stipulation, $winner->side, [
            new MatchEliminationData($competitors[0], 1, $competitors[0]),
            new MatchEliminationData($competitors[1], 2, $winner),
        ]),
    ))->toThrow(InvalidMatchOutcomeException::class);
});

it('rejects a royal rumble with invalid entry order', function () {
    [$match, $competitors] = eliminationMatch(MatchType::RoyalRumble);
    $competitors[1]->forceFill(['entry_order' => null])->save();
    $winner = $competitors[2];

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Stipulation, $winner->side, [
            new MatchEliminationData($competitors[0], 1, $winner),
            new MatchEliminationData($competitors[1], 2, $winner),
        ]),
    ))->toThrow(InvalidMatchOutcomeException::class);
});

it('rejects an elimination credited after the eliminator exited', function () {
    [$match, $competitors] = eliminationMatch(MatchType::BattleRoyal);
    $winner = $competitors[2];

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Stipulation, $winner->side, [
            new MatchEliminationData($competitors[0], 1, $competitors[1]),
            new MatchEliminationData($competitors[1], 2, $competitors[0]),
        ]),
    ))->toThrow(InvalidMatchOutcomeException::class);
});

it('transfers a singles title to the winning challenger', function () {
    $eventDate = now()->subDay()->startOfSecond();
    $event = Event::factory()->create(['date' => $eventDate]);
    $match = EventMatch::factory()->for($event)->create();
    $champion = Wrestler::factory()->bookable()->create();
    $challenger = Wrestler::factory()->bookable()->create();
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $reign = TitleChampionship::factory()
        ->for($title)
        ->forWrestler($champion)
        ->wonOn($eventDate->copy()->subMonth()->toDateTimeString())
        ->create();
    sideWithCompetitor($match, 1, competitor: $champion);
    $winningSide = sideWithCompetitor($match, 2, competitor: $challenger);
    $match->titles()->attach($title);

    resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Pinfall, $winningSide),
    );

    $newReign = $title->championships()->current()->sole();

    expect($reign->refresh()->lost_match_id)->toBe($match->id)
        ->and($reign->lost_at?->equalTo($eventDate))->toBeTrue()
        ->and($newReign->champion->is($challenger))->toBeTrue()
        ->and($newReign->won_match_id)->toBe($match->id)
        ->and($newReign->won_at->equalTo($eventDate))->toBeTrue();
});

it('retains a title when its champion wins', function () {
    $event = Event::factory()->past()->create();
    $match = EventMatch::factory()->for($event)->create();
    $champion = Wrestler::factory()->bookable()->create();
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $reign = TitleChampionship::factory()->for($title)->forWrestler($champion)->create();
    $winningSide = sideWithCompetitor($match, 1, competitor: $champion);
    sideWithCompetitor($match, 2, competitor: Wrestler::factory()->bookable()->create());
    $match->titles()->attach($title);

    resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Submission, $winningSide),
    );

    expect($reign->refresh()->lost_at)->toBeNull()
        ->and($title->championships()->count())->toBe(1);
});

it('does not transfer a title on a disqualification', function () {
    $event = Event::factory()->past()->create();
    $match = EventMatch::factory()->for($event)->create();
    $champion = Wrestler::factory()->bookable()->create();
    $challenger = Wrestler::factory()->bookable()->create();
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $reign = TitleChampionship::factory()->for($title)->forWrestler($champion)->create();
    sideWithCompetitor($match, 1, competitor: $champion);
    $winningSide = sideWithCompetitor($match, 2, competitor: $challenger);
    $match->titles()->attach($title);

    resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Disqualification, $winningSide),
    );

    expect($reign->refresh()->lost_at)->toBeNull()
        ->and($title->championships()->count())->toBe(1);
});

it('crowns the winner of a vacant title match', function () {
    $event = Event::factory()->past()->create();
    $match = EventMatch::factory()->for($event)->create();
    $winner = Wrestler::factory()->bookable()->create();
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $winningSide = sideWithCompetitor($match, 1, competitor: $winner);
    sideWithCompetitor($match, 2, competitor: Wrestler::factory()->bookable()->create());
    $match->titles()->attach($title);

    resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Knockout, $winningSide),
    );

    $reign = $title->championships()->current()->sole();

    expect($reign->champion->is($winner))->toBeTrue()
        ->and($reign->won_match_id)->toBe($match->id);
});

it('transfers every title in a winner-take-all match', function () {
    $event = Event::factory()->past()->create();
    $match = EventMatch::factory()->for($event)->create();
    $winner = Wrestler::factory()->bookable()->create();
    $firstTitle = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $secondTitle = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $firstReign = TitleChampionship::factory()
        ->for($firstTitle)
        ->forWrestler(Wrestler::factory()->bookable()->create())
        ->create();
    $secondReign = TitleChampionship::factory()
        ->for($secondTitle)
        ->forWrestler(Wrestler::factory()->bookable()->create())
        ->create();
    $winningSide = sideWithCompetitor($match, 1, competitor: $winner);
    sideWithCompetitor($match, 2, competitor: $firstReign->champion);
    sideWithCompetitor($match, 3, competitor: $secondReign->champion);
    $match->titles()->attach([$firstTitle->id, $secondTitle->id]);

    resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Stipulation, $winningSide),
    );

    expect($firstTitle->championships()->current()->sole()->champion->is($winner))->toBeTrue()
        ->and($secondTitle->championships()->current()->sole()->champion->is($winner))->toBeTrue();
});

it('transfers a tag team title to a winning tag team', function () {
    $event = Event::factory()->past()->create();
    $match = EventMatch::factory()->for($event)->create();
    $champion = TagTeam::factory()->bookable()->create();
    $challenger = TagTeam::factory()->bookable()->create();
    $title = Title::factory()->active()->create(['type' => TitleType::TagTeam]);
    TitleChampionship::factory()->for($title)->forTagTeam($champion)->create();
    sideWithCompetitor($match, 1, competitor: $champion);
    $winningSide = sideWithCompetitor($match, 2, competitor: $challenger);
    $match->titles()->attach($title);

    resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Pinfall, $winningSide),
    );

    expect($title->championships()->current()->sole()->champion->is($challenger))->toBeTrue();
});

it('rejects a winner incompatible with the title type', function () {
    $event = Event::factory()->past()->create();
    $match = EventMatch::factory()->for($event)->create();
    $title = Title::factory()->active()->create(['type' => TitleType::TagTeam]);
    $winningSide = sideWithCompetitor($match, 1, competitor: Wrestler::factory()->bookable()->create());
    $match->titles()->attach($title);

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Pinfall, $winningSide),
    ))->toThrow(InvalidMatchOutcomeException::class)
        ->and($match->refresh()->match_finish)->toBeNull()
        ->and($title->championships()->count())->toBe(0);
});

it('rejects a title change at an undated event', function () {
    $event = Event::factory()->unscheduled()->create();
    $match = EventMatch::factory()->for($event)->create();
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $winningSide = sideWithCompetitor($match, 1, competitor: Wrestler::factory()->bookable()->create());
    $match->titles()->attach($title);

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Pinfall, $winningSide),
    ))->toThrow(InvalidMatchOutcomeException::class)
        ->and($match->refresh()->match_finish)->toBeNull()
        ->and($title->championships()->count())->toBe(0);
});

it('restores title lineage when a result is corrected to a draw', function () {
    $event = Event::factory()->past()->create();
    $match = EventMatch::factory()->for($event)->create();
    $champion = Wrestler::factory()->bookable()->create();
    $challenger = Wrestler::factory()->bookable()->create();
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $originalReign = TitleChampionship::factory()->for($title)->forWrestler($champion)->create();
    sideWithCompetitor($match, 1, competitor: $champion);
    $winningSide = sideWithCompetitor($match, 2, competitor: $challenger);
    $match->titles()->attach($title);

    resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Pinfall, $winningSide),
    );
    resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::TimeLimitDraw, null),
    );

    expect($originalReign->refresh()->lost_at)->toBeNull()
        ->and($originalReign->lost_match_id)->toBeNull()
        ->and($title->championships()->current()->sole()->is($originalReign))->toBeTrue()
        ->and(TitleChampionship::onlyTrashed()->where('won_match_id', $match->id)->exists())->toBeTrue();
});

it('rejects correcting a title result after later lineage exists', function () {
    $event = Event::factory()->past()->create();
    $match = EventMatch::factory()->for($event)->create();
    $champion = Wrestler::factory()->bookable()->create();
    $challenger = Wrestler::factory()->bookable()->create();
    $laterChampion = Wrestler::factory()->bookable()->create();
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    TitleChampionship::factory()->for($title)->forWrestler($champion)->create();
    sideWithCompetitor($match, 1, competitor: $champion);
    $winningSide = sideWithCompetitor($match, 2, competitor: $challenger);
    $match->titles()->attach($title);

    resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Pinfall, $winningSide),
    );

    $laterMatch = EventMatch::factory()->for(Event::factory()->past())->create();
    $challengerReign = $title->championships()->current()->sole();
    $challengerReign->update([
        'lost_match_id' => $laterMatch->id,
        'lost_at' => $laterMatch->event->date,
    ]);
    TitleChampionship::factory()
        ->for($title)
        ->forWrestler($laterChampion)
        ->wonAtEventMatch($laterMatch)
        ->create();

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::TimeLimitDraw, null),
    ))->toThrow(InvalidMatchOutcomeException::class)
        ->and($match->refresh()->match_finish)->toBe(MatchFinish::Pinfall)
        ->and($title->championships()->current()->sole()->champion->is($laterChampion))->toBeTrue();
});

/**
 * @param  Title|list<Title>  $titles
 * @return array{EventMatch, MatchSide}
 */
function titleMatchOn(Carbon $date, Title|array $titles, Wrestler|TagTeam $winner): array
{
    $match = EventMatch::factory()
        ->for(Event::factory()->create(['date' => $date]))
        ->create();
    $winningSide = sideWithCompetitor($match, 1, competitor: $winner);
    sideWithCompetitor($match, 2, competitor: $winner instanceof TagTeam ? TagTeam::factory()->bookable()->create() : Wrestler::factory()->bookable()->create());
    $match->titles()->attach(Arr::wrap($titles));

    return [$match, $winningSide];
}

it('rejects a title result recorded before a later recorded result', function (TitleType $type): void {
    // Arrange
    $title = Title::factory()->active()->create(['type' => $type]);
    $newChampion = fn () => $type === TitleType::Singles ? Wrestler::factory()->bookable()->create() : TagTeam::factory()->bookable()->create();
    [$laterMatch, $laterSide] = titleMatchOn(now()->subDays(5), $title, $newChampion());
    [$earlierMatch, $earlierSide] = titleMatchOn(now()->subDays(10), $title, $newChampion());
    resolve(RecordResultAction::class)->handle($laterMatch, matchResult(MatchFinish::Pinfall, $laterSide));

    // Act
    $record = fn () => resolve(RecordResultAction::class)->handle(
        $earlierMatch,
        matchResult(MatchFinish::Pinfall, $earlierSide),
    );

    // Assert
    expect($record)->toThrow(
        InvalidMatchOutcomeException::class,
        "Title [{$title->name}] already has a result recorded after this event; record results in date order.",
    )
        ->and($earlierMatch->refresh()->match_finish)->toBeNull()
        ->and($earlierMatch->winning_side_id)->toBeNull()
        ->and($title->championships()->count())->toBe(1)
        ->and($title->championships()->sole()->won_match_id)->toBe($laterMatch->id)
        ->and($title->championships()->sole()->lost_at)->toBeNull();
})->with([
    'singles title' => TitleType::Singles,
    'tag team title' => TitleType::TagTeam,
]);

it('records title results in date order with consistent reigns', function (): void {
    // Arrange
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    [$earlierMatch, $earlierSide] = titleMatchOn(now()->subDays(10), $title, Wrestler::factory()->bookable()->create());
    [$laterMatch, $laterSide] = titleMatchOn(now()->subDays(5), $title, Wrestler::factory()->bookable()->create());

    // Act
    resolve(RecordResultAction::class)->handle($earlierMatch, matchResult(MatchFinish::Pinfall, $earlierSide));
    resolve(RecordResultAction::class)->handle($laterMatch, matchResult(MatchFinish::Pinfall, $laterSide));

    // Assert
    $firstReign = $title->championships()->where('won_match_id', $earlierMatch->id)->sole();
    $secondReign = $title->championships()->where('won_match_id', $laterMatch->id)->sole();

    expect($title->championships()->count())->toBe(2)
        ->and($firstReign->lost_match_id)->toBe($laterMatch->id)
        ->and($firstReign->lost_at?->gte($firstReign->won_at))->toBeTrue()
        ->and($secondReign->lost_at)->toBeNull();
});

it('still corrects the result of the latest recorded title match', function (): void {
    // Arrange
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $firstWinner = Wrestler::factory()->bookable()->create();
    $secondWinner = Wrestler::factory()->bookable()->create();
    [$earlierMatch, $earlierSide] = titleMatchOn(now()->subDays(10), $title, $firstWinner);
    [$laterMatch, $laterSide] = titleMatchOn(now()->subDays(5), $title, $secondWinner);
    $otherSide = $laterMatch->sides()->where('id', '!=', $laterSide->id)->sole();
    resolve(RecordResultAction::class)->handle($earlierMatch, matchResult(MatchFinish::Pinfall, $earlierSide));
    resolve(RecordResultAction::class)->handle($laterMatch, matchResult(MatchFinish::Pinfall, $laterSide));

    // Act
    resolve(RecordResultAction::class)->handle($laterMatch, matchResult(MatchFinish::Pinfall, $otherSide));

    // Assert
    expect($title->championships()->current()->sole()->champion->is($otherSide->competitors()->sole()->competitor))->toBeTrue()
        ->and($title->championships()->current()->sole()->won_match_id)->toBe($laterMatch->id)
        ->and($title->championships()->count())->toBe(2);
});

it('allows a title result at the same instant as the latest recorded reign', function (): void {
    // Arrange
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    [$firstMatch, $firstSide] = titleMatchOn(now()->subDays(5), $title, Wrestler::factory()->bookable()->create());
    [$secondMatch, $secondSide] = titleMatchOn(now()->subDays(5), $title, Wrestler::factory()->bookable()->create());
    resolve(RecordResultAction::class)->handle($secondMatch, matchResult(MatchFinish::Pinfall, $secondSide));

    // Act
    resolve(RecordResultAction::class)->handle($firstMatch, matchResult(MatchFinish::Pinfall, $firstSide));

    // Assert
    expect($firstMatch->refresh()->match_finish)->toBe(MatchFinish::Pinfall);
});

it('does not apply the date order rule to non-title-changing results', function (): void {
    // Arrange
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    [$laterMatch, $laterSide] = titleMatchOn(now()->subDays(5), $title, Wrestler::factory()->bookable()->create());
    [$earlierMatch] = titleMatchOn(now()->subDays(10), $title, Wrestler::factory()->bookable()->create());
    resolve(RecordResultAction::class)->handle($laterMatch, matchResult(MatchFinish::Pinfall, $laterSide));

    // Act
    resolve(RecordResultAction::class)->handle($earlierMatch, matchResult(MatchFinish::TimeLimitDraw, null));

    // Assert
    expect($earlierMatch->refresh()->match_finish)->toBe(MatchFinish::TimeLimitDraw)
        ->and($title->championships()->count())->toBe(1);
});

it('rejects a result at an unscheduled event even when a later reign exists', function (): void {
    // Arrange
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    [$laterMatch, $laterSide] = titleMatchOn(now()->subDays(5), $title, Wrestler::factory()->bookable()->create());
    resolve(RecordResultAction::class)->handle($laterMatch, matchResult(MatchFinish::Pinfall, $laterSide));
    $undatedMatch = EventMatch::factory()->for(Event::factory()->unscheduled())->create();
    $undatedSide = sideWithCompetitor($undatedMatch, 1, competitor: Wrestler::factory()->bookable()->create());
    $undatedMatch->titles()->attach($title);

    // Act
    $record = fn () => resolve(RecordResultAction::class)->handle(
        $undatedMatch,
        matchResult(MatchFinish::Pinfall, $undatedSide),
    );

    // Assert
    expect($record)->toThrow(InvalidMatchOutcomeException::class, 'A result cannot be recorded for an event that has not taken place yet.');
});

it('rejects the whole result when only one of several titles is out of date order', function (): void {
    // Arrange
    $orderedTitle = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $lateTitle = Title::factory()->active()->create(['type' => TitleType::Singles]);
    [$laterMatch, $laterSide] = titleMatchOn(now()->subDays(5), $lateTitle, Wrestler::factory()->bookable()->create());
    [$earlierMatch, $earlierSide] = titleMatchOn(now()->subDays(10), [$orderedTitle, $lateTitle], Wrestler::factory()->bookable()->create());
    resolve(RecordResultAction::class)->handle($laterMatch, matchResult(MatchFinish::Pinfall, $laterSide));

    // Act
    $record = fn () => resolve(RecordResultAction::class)->handle(
        $earlierMatch,
        matchResult(MatchFinish::Pinfall, $earlierSide),
    );

    // Assert
    expect($record)->toThrow(InvalidMatchOutcomeException::class)
        ->and($earlierMatch->refresh()->match_finish)->toBeNull()
        ->and($orderedTitle->championships()->count())->toBe(0)
        ->and($lateTitle->championships()->count())->toBe(1);
});

it('rejects a result for an event that has not taken place yet', function (?int $daysFromNow): void {
    // Arrange
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $match = EventMatch::factory()->for(Event::factory()->create(['date' => $daysFromNow === null ? null : now()->addDays($daysFromNow)]))->create();
    $winningSide = sideWithCompetitor($match, 1, competitor: Wrestler::factory()->bookable()->create());
    sideWithCompetitor($match, 2, competitor: Wrestler::factory()->bookable()->create());
    $match->titles()->attach($title);

    // Act
    $record = fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Pinfall, $winningSide),
    );

    // Assert
    expect($record)->toThrow(
        InvalidMatchOutcomeException::class,
        'A result cannot be recorded for an event that has not taken place yet.',
    )
        ->and($match->refresh()->match_finish)->toBeNull()
        ->and($match->winning_side_id)->toBeNull()
        ->and(TitleChampionship::withTrashed()->count())->toBe(0);
})->with([
    'future date' => 1,
    'unscheduled' => null,
]);

it('accepts a result for an event happening right now', function (): void {
    // Arrange
    $match = EventMatch::factory()->for(Event::factory()->create(['date' => now()]))->create();

    // Act
    resolve(RecordResultAction::class)->handle($match, matchResult(MatchFinish::TimeLimitDraw, null));

    // Assert
    expect($match->refresh()->match_finish)->toBe(MatchFinish::TimeLimitDraw);
});

it('rejects a title change on a title that is no longer active', function (Closure $deactivate): void {
    // Arrange
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    [$match, $winningSide] = titleMatchOn(now(), $title, Wrestler::factory()->bookable()->create());
    $deactivate($title);

    // Act
    $record = fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Pinfall, $winningSide),
    );

    // Assert
    expect($record)->toThrow(
        InvalidMatchOutcomeException::class,
        "Title [{$title->name}] is no longer active and cannot change hands.",
    )
        ->and($match->refresh()->match_finish)->toBeNull()
        ->and(TitleChampionship::withTrashed()->count())->toBe(0);
})->with([
    'pulled' => fn (Title $title) => resolve(PullAction::class)->handle($title),
    'retired' => fn (Title $title) => resolve(RetireTitleAction::class)->handle($title),
]);

it('rejects a title change on a deleted title', function (): void {
    // Arrange
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    [$match, $winningSide] = titleMatchOn(now()->subDay(), $title, Wrestler::factory()->bookable()->create());
    resolve(DeleteTitleAction::class)->handle($title);

    // Act
    $record = fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Pinfall, $winningSide),
    );

    // Assert
    expect($record)->toThrow(InvalidMatchOutcomeException::class, 'A deleted title cannot change hands.')
        ->and($match->refresh()->match_finish)->toBeNull()
        ->and(TitleChampionship::withTrashed()->count())->toBe(0);
});

it('rejects a correction that would change the champion of a title deleted since', function (): void {
    // Arrange
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    [$match, $winningSide] = titleMatchOn(now()->subDays(2), $title, Wrestler::factory()->bookable()->create());
    $otherSide = $match->sides()->whereKeyNot($winningSide->id)->sole();
    resolve(RecordResultAction::class)->handle($match, matchResult(MatchFinish::Pinfall, $winningSide));
    resolve(DeleteTitleAction::class)->handle($title);

    // Act
    $record = fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Pinfall, $otherSide),
    );

    // Assert
    expect($record)->toThrow(InvalidMatchOutcomeException::class, 'A deleted title cannot change hands.')
        ->and($match->refresh()->winning_side_id)->toBe($winningSide->id);
});

it('records a title-changing finish that keeps the champion of a title deleted since', function (): void {
    // Arrange
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    [$match, $winningSide] = titleMatchOn(now()->subDays(2), $title, Wrestler::factory()->bookable()->create());
    resolve(RecordResultAction::class)->handle($match, matchResult(MatchFinish::Pinfall, $winningSide));
    resolve(DeleteTitleAction::class)->handle($title);

    // Act
    resolve(RecordResultAction::class)->handle($match, matchResult(MatchFinish::Submission, $winningSide));

    // Assert
    $reign = TitleChampionship::query()->where('title_id', $title->id)->sole();

    expect($match->refresh()->match_finish)->toBe(MatchFinish::Submission)
        ->and($reign->won_match_id)->toBe($match->id)
        ->and($reign->lost_at)->not->toBeNull();
});

it('still records a result that does not change a deleted or inactive title', function (Closure $deactivate): void {
    // Arrange
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    [$match] = titleMatchOn(now()->subDay(), $title, Wrestler::factory()->bookable()->create());
    $deactivate($title);

    // Act
    resolve(RecordResultAction::class)->handle($match, matchResult(MatchFinish::TimeLimitDraw, null));

    // Assert
    expect($match->refresh()->match_finish)->toBe(MatchFinish::TimeLimitDraw)
        ->and(TitleChampionship::withTrashed()->count())->toBe(0);
})->with([
    'pulled' => fn (Title $title) => resolve(PullAction::class)->handle($title),
    'deleted' => fn (Title $title) => resolve(DeleteTitleAction::class)->handle($title),
]);

it('rejects a title change for a winner who is no longer eligible', function (Closure $change, string $message): void {
    // Arrange
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $winner = Wrestler::factory()->bookable()->create();
    [$match, $winningSide] = titleMatchOn(now(), $title, $winner);
    $change($winner);

    // Act
    $record = fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Pinfall, $winningSide),
    );

    // Assert
    expect($record)->toThrow(InvalidMatchOutcomeException::class, str_replace('{name}', $winner->name, $message))
        ->and($match->refresh()->match_finish)->toBeNull()
        ->and(TitleChampionship::withTrashed()->count())->toBe(0);
})->with([
    'retired' => [
        fn (Wrestler $wrestler) => resolve(RetireWrestlerAction::class)->handle($wrestler),
        '[{name}] is no longer eligible to win a title.',
    ],
    'released' => [
        fn (Wrestler $wrestler) => resolve(ReleaseWrestlerAction::class)->handle($wrestler),
        '[{name}] is no longer eligible to win a title.',
    ],
    'deleted' => [
        // The delete Action refuses booked wrestlers, so soft-delete directly to model a stale booking.
        fn (Wrestler $wrestler) => $wrestler->delete(),
        'A deleted competitor cannot win a title.',
    ],
]);

it('still records a defence by the current champion on a title that was pulled', function (): void {
    // Arrange
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $champion = Wrestler::factory()->bookable()->create();
    [$match, $winningSide] = titleMatchOn(now()->subDay(), $title, $champion);
    TitleChampionship::factory()->for($title)->forWrestler($champion)->create(['won_at' => now()->subDays(30)]);
    $title->activityPeriods()->current()->sole()->update(['ended_at' => now()->subHour()]);

    // Act
    resolve(RecordResultAction::class)->handle($match, matchResult(MatchFinish::Pinfall, $winningSide));

    // Assert
    expect($match->refresh()->match_finish)->toBe(MatchFinish::Pinfall)
        ->and($title->championships()->count())->toBe(1);
});

it('leaves the title vacant when a corrected result cannot reopen the previous reign', function (Closure $remove): void {
    // Arrange
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $firstChampion = Wrestler::factory()->bookable()->create();
    [$firstMatch, $firstSide] = titleMatchOn(now()->subDays(20), $title, $firstChampion);
    [$secondMatch, $secondSide] = titleMatchOn(now()->subDays(10), $title, Wrestler::factory()->bookable()->create());
    resolve(RecordResultAction::class)->handle($firstMatch, matchResult(MatchFinish::Pinfall, $firstSide));
    resolve(RecordResultAction::class)->handle($secondMatch, matchResult(MatchFinish::Pinfall, $secondSide));
    $remove($firstChampion, $title);

    // Act
    resolve(RecordResultAction::class)->handle($secondMatch, matchResult(MatchFinish::TimeLimitDraw, null));

    // Assert
    $firstReign = $title->championships()->where('won_match_id', $firstMatch->id)->sole();

    expect($firstReign->lost_at)->not->toBeNull()
        ->and($firstReign->lost_match_id)->toBe($secondMatch->id)
        ->and($title->championships()->current()->exists())->toBeFalse()
        ->and(TitleChampionship::onlyTrashed()->where('won_match_id', $secondMatch->id)->exists())->toBeTrue();
})->with([
    'retired champion' => fn (Wrestler $wrestler) => resolve(RetireWrestlerAction::class)->handle($wrestler),
    'released champion' => fn (Wrestler $wrestler) => resolve(ReleaseWrestlerAction::class)->handle($wrestler),
    'deleted champion' => fn (Wrestler $wrestler) => resolve(DeleteWrestlerAction::class)->handle($wrestler),
    'pulled title' => fn (Wrestler $wrestler, Title $title) => resolve(PullAction::class)->handle($title),
]);

it('crowns the corrected winner when the previous champion can no longer be reinstated', function (): void {
    // Arrange
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $firstChampion = Wrestler::factory()->bookable()->create();
    [$firstMatch, $firstSide] = titleMatchOn(now()->subDays(20), $title, $firstChampion);
    [$secondMatch, $secondSide] = titleMatchOn(now()->subDays(10), $title, Wrestler::factory()->bookable()->create());
    $otherSide = $secondMatch->sides()->whereKeyNot($secondSide->id)->sole();
    resolve(RecordResultAction::class)->handle($firstMatch, matchResult(MatchFinish::Pinfall, $firstSide));
    resolve(RecordResultAction::class)->handle($secondMatch, matchResult(MatchFinish::Pinfall, $secondSide));
    resolve(RetireWrestlerAction::class)->handle($firstChampion);

    // Act
    resolve(RecordResultAction::class)->handle($secondMatch, matchResult(MatchFinish::Pinfall, $otherSide));

    // Assert
    $currentReign = $title->championships()->current()->sole();

    expect($currentReign->won_match_id)->toBe($secondMatch->id)
        ->and($currentReign->champion_id)->toBe($otherSide->competitors()->sole()->competitor_id)
        ->and($title->championships()->where('won_match_id', $firstMatch->id)->sole()->lost_match_id)->toBe($secondMatch->id);
});

it('rejects a title change dated inside a reign that was vacated later', function (): void {
    // Arrange
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    TitleChampionship::factory()->for($title)->forWrestler(Wrestler::factory()->bookable()->create())->create([
        'won_at' => now()->subDays(20),
        'lost_at' => now()->subDays(5),
    ]);
    [$match, $winningSide] = titleMatchOn(now()->subDays(10), $title, Wrestler::factory()->bookable()->create());

    // Act
    $record = fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Pinfall, $winningSide),
    );

    // Assert
    expect($record)->toThrow(InvalidMatchOutcomeException::class, "Title [{$title->name}] already has a result recorded after this event")
        ->and($match->refresh()->match_finish)->toBeNull()
        ->and($title->championships()->count())->toBe(1);
});

it('rejects a title change dated at the instant a later vacated reign began', function (): void {
    // Arrange
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    TitleChampionship::factory()->for($title)->forWrestler(Wrestler::factory()->bookable()->create())->create([
        'won_at' => now()->subDays(20),
        'lost_at' => now()->subDays(5),
    ]);
    [$match, $winningSide] = titleMatchOn(now()->subDays(20), $title, Wrestler::factory()->bookable()->create());

    // Act
    $record = fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Pinfall, $winningSide),
    );

    // Assert
    expect($record)->toThrow(InvalidMatchOutcomeException::class, "Title [{$title->name}] already has a result recorded after this event")
        ->and($match->refresh()->match_finish)->toBeNull()
        ->and($title->championships()->count())->toBe(1);
});

it('allows a title change at the instant a vacated reign ended', function (): void {
    // Arrange
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    TitleChampionship::factory()->for($title)->forWrestler(Wrestler::factory()->bookable()->create())->create([
        'won_at' => now()->subDays(20),
        'lost_at' => now()->subDays(10),
    ]);
    [$match, $winningSide] = titleMatchOn(now()->subDays(10), $title, Wrestler::factory()->bookable()->create());

    // Act
    resolve(RecordResultAction::class)->handle($match, matchResult(MatchFinish::Pinfall, $winningSide));

    // Assert
    expect($title->championships()->count())->toBe(2);
});

it('allows correcting the finish of an unchanged winner after a later vacancy and reign', function (): void {
    // Arrange
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    [$match, $side] = titleMatchOn(now()->subDays(20), $title, Wrestler::factory()->bookable()->create());
    resolve(RecordResultAction::class)->handle($match, matchResult(MatchFinish::Pinfall, $side));
    $title->championships()->current()->sole()->update(['lost_at' => now()->subDays(10)]);
    [$laterMatch, $laterSide] = titleMatchOn(now()->subDays(5), $title, Wrestler::factory()->bookable()->create());
    resolve(RecordResultAction::class)->handle($laterMatch, matchResult(MatchFinish::Pinfall, $laterSide));

    // Act
    resolve(RecordResultAction::class)->handle($match, matchResult(MatchFinish::Submission, $side));

    // Assert
    expect($match->refresh()->match_finish)->toBe(MatchFinish::Submission)
        ->and($title->championships()->count())->toBe(2);
});
