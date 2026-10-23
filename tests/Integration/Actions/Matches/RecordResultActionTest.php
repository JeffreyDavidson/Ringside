<?php

declare(strict_types=1);

use App\Actions\Matches\RecordResultAction;
use App\Actions\TagTeams\ReleaseAction as ReleaseTagTeamAction;
use App\Actions\TagTeams\RetireAction as RetireTagTeamAction;
use App\Actions\Titles\DeleteAction as DeleteTitleAction;
use App\Actions\Titles\PullAction;
use App\Actions\Titles\RetireAction as RetireTitleAction;
use App\Actions\Wrestlers\DeleteAction as DeleteWrestlerAction;
use App\Actions\Wrestlers\ReleaseAction as ReleaseWrestlerAction;
use App\Actions\Wrestlers\RetireAction as RetireWrestlerAction;
use App\Data\Matches\MatchEliminationData;
use App\Data\Matches\MatchResultData;
use App\Enums\Matches\MatchFinish;
use App\Enums\Matches\MatchType;
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

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

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
        administrator(),
    )->match;

    expect($resultedMatch->match_finish)->toBe(MatchFinish::Pinfall)
        ->and($resultedMatch->winningSide?->is($winningSide))->toBeTrue();
});

it('records a draw without a winning side', function () {
    $match = EventMatch::factory()->create();

    $resultedMatch = resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::TimeLimitDraw, null),
        administrator(),
    )->match;

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
        administrator(),
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
        administrator(),
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
        administrator(),
    )->match;

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
        administrator(),
    );

    $correctedMatch = resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Forfeit, $correctedWinner->side, [
            new MatchEliminationData($firstWinner, 1),
            new MatchEliminationData($competitors[0], 2, $correctedWinner),
        ]),
        administrator(),
    )->match;

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
        administrator(),
    );

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::NoDecision, null, [
            new MatchEliminationData($competitors[0], 2),
        ]),
        administrator(),
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
        administrator(),
    ))->toThrow(InvalidMatchOutcomeException::class);
});

it('rejects a winning side for a no-outcome finish', function () {
    $match = EventMatch::factory()->create();
    $side = sideWithCompetitor($match, 1);

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::NoDecision, $side),
        administrator(),
    ))->toThrow(InvalidMatchOutcomeException::class);
});

it('rejects a side belonging to another match', function () {
    $match = EventMatch::factory()->create();
    $otherMatch = EventMatch::factory()->create();
    $otherSide = sideWithCompetitor($otherMatch, 1);

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Pinfall, $otherSide),
        administrator(),
    ))->toThrow(InvalidMatchOutcomeException::class);
});

it('rejects an empty winning side', function () {
    $match = EventMatch::factory()->create();
    $emptySide = MatchSide::factory()->for($match, 'match')->create(['position' => 1]);

    expect(fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Pinfall, $emptySide),
        administrator(),
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
        administrator(),
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
        administrator(),
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
        administrator(),
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
        administrator(),
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
        administrator(),
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
        administrator(),
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
        administrator(),
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
        administrator(),
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
        administrator(),
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
        administrator(),
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
        administrator(),
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
        administrator(),
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
        administrator(),
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
        administrator(),
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
        administrator(),
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
        administrator(),
    );
    resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::TimeLimitDraw, null),
        administrator(),
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
        administrator(),
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
        administrator(),
    ))->toThrow(InvalidMatchOutcomeException::class)
        ->and($match->refresh()->match_finish)->toBe(MatchFinish::Pinfall)
        ->and($title->championships()->current()->sole()->champion->is($laterChampion))->toBeTrue();
});

/**
 * @param  Title|list<Title>  $titles
 * @return array{EventMatch, MatchSide}
 */
function titleMatchOn(Carbon $date, Title|array $titles, Wrestler|TagTeam $winner, Wrestler|TagTeam|null $loser = null): array
{
    $match = EventMatch::factory()
        ->for(Event::factory()->create(['date' => $date]))
        ->create();
    $winningSide = sideWithCompetitor($match, 1, competitor: $winner);
    sideWithCompetitor($match, 2, competitor: $loser ?? ($winner instanceof TagTeam ? TagTeam::factory()->bookable()->create() : Wrestler::factory()->bookable()->create()));
    $match->titles()->attach(Arr::wrap($titles));

    return [$match, $winningSide];
}

it('rejects a title result recorded before a later recorded result', function (TitleType $type): void {
    // Arrange
    $title = Title::factory()->active()->create(['type' => $type]);
    $newChampion = fn () => $type === TitleType::Singles ? Wrestler::factory()->bookable()->create() : TagTeam::factory()->bookable()->create();
    [$laterMatch, $laterSide] = titleMatchOn(now()->subDays(5), $title, $newChampion());
    [$earlierMatch, $earlierSide] = titleMatchOn(now()->subDays(10), $title, $newChampion());
    resolve(RecordResultAction::class)->handle($laterMatch, matchResult(MatchFinish::Pinfall, $laterSide), administrator());

    // Act
    $record = fn () => resolve(RecordResultAction::class)->handle(
        $earlierMatch,
        matchResult(MatchFinish::Pinfall, $earlierSide),
        administrator(),
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
    $earlierWinner = Wrestler::factory()->bookable()->create();
    [$earlierMatch, $earlierSide] = titleMatchOn(now()->subDays(10), $title, $earlierWinner);
    [$laterMatch, $laterSide] = titleMatchOn(now()->subDays(5), $title, Wrestler::factory()->bookable()->create(), $earlierWinner);

    // Act
    resolve(RecordResultAction::class)->handle($earlierMatch, matchResult(MatchFinish::Pinfall, $earlierSide), administrator());
    resolve(RecordResultAction::class)->handle($laterMatch, matchResult(MatchFinish::Pinfall, $laterSide), administrator());

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
    [$laterMatch, $laterSide] = titleMatchOn(now()->subDays(5), $title, $secondWinner, $firstWinner);
    $otherSide = $laterMatch->sides()->where('id', '!=', $laterSide->id)->sole();
    resolve(RecordResultAction::class)->handle($earlierMatch, matchResult(MatchFinish::Pinfall, $earlierSide), administrator());
    resolve(RecordResultAction::class)->handle($laterMatch, matchResult(MatchFinish::Pinfall, $laterSide), administrator());

    // Act
    resolve(RecordResultAction::class)->handle($laterMatch, matchResult(MatchFinish::Pinfall, $otherSide), administrator());

    // Assert
    expect($title->championships()->current()->sole()->champion->is($otherSide->competitors()->sole()->competitor))->toBeTrue()
        ->and($title->championships()->current()->sole()->won_match_id)->toBe($earlierMatch->id)
        ->and($title->championships()->count())->toBe(1);
});

it('allows a title result at the same instant as the latest recorded reign', function (): void {
    // Arrange
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    [$firstMatch, $firstSide] = titleMatchOn(now()->subDays(5), $title, Wrestler::factory()->bookable()->create());
    [$secondMatch, $secondSide] = titleMatchOn(now()->subDays(5), $title, Wrestler::factory()->bookable()->create());
    resolve(RecordResultAction::class)->handle($secondMatch, matchResult(MatchFinish::Pinfall, $secondSide), administrator());

    // Act
    resolve(RecordResultAction::class)->handle($firstMatch, matchResult(MatchFinish::Pinfall, $firstSide), administrator());

    // Assert
    expect($firstMatch->refresh()->match_finish)->toBe(MatchFinish::Pinfall);
});

it('does not apply the date order rule to non-title-changing results', function (): void {
    // Arrange
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    [$laterMatch, $laterSide] = titleMatchOn(now()->subDays(5), $title, Wrestler::factory()->bookable()->create());
    [$earlierMatch] = titleMatchOn(now()->subDays(10), $title, Wrestler::factory()->bookable()->create());
    resolve(RecordResultAction::class)->handle($laterMatch, matchResult(MatchFinish::Pinfall, $laterSide), administrator());

    // Act
    resolve(RecordResultAction::class)->handle($earlierMatch, matchResult(MatchFinish::TimeLimitDraw, null), administrator());

    // Assert
    expect($earlierMatch->refresh()->match_finish)->toBe(MatchFinish::TimeLimitDraw)
        ->and($title->championships()->count())->toBe(1);
});

it('rejects a result at an unscheduled event even when a later reign exists', function (): void {
    // Arrange
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    [$laterMatch, $laterSide] = titleMatchOn(now()->subDays(5), $title, Wrestler::factory()->bookable()->create());
    resolve(RecordResultAction::class)->handle($laterMatch, matchResult(MatchFinish::Pinfall, $laterSide), administrator());
    $undatedMatch = EventMatch::factory()->for(Event::factory()->unscheduled())->create();
    $undatedSide = sideWithCompetitor($undatedMatch, 1, competitor: Wrestler::factory()->bookable()->create());
    $undatedMatch->titles()->attach($title);

    // Act
    $record = fn () => resolve(RecordResultAction::class)->handle(
        $undatedMatch,
        matchResult(MatchFinish::Pinfall, $undatedSide),
        administrator(),
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
    resolve(RecordResultAction::class)->handle($laterMatch, matchResult(MatchFinish::Pinfall, $laterSide), administrator());

    // Act
    $record = fn () => resolve(RecordResultAction::class)->handle(
        $earlierMatch,
        matchResult(MatchFinish::Pinfall, $earlierSide),
        administrator(),
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
        administrator(),
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
    resolve(RecordResultAction::class)->handle($match, matchResult(MatchFinish::TimeLimitDraw, null), administrator());

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
        administrator(),
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
        administrator(),
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
    resolve(RecordResultAction::class)->handle($match, matchResult(MatchFinish::Pinfall, $winningSide), administrator());
    resolve(DeleteTitleAction::class)->handle($title);

    // Act
    $record = fn () => resolve(RecordResultAction::class)->handle(
        $match,
        matchResult(MatchFinish::Pinfall, $otherSide),
        administrator(),
    );

    // Assert
    expect($record)->toThrow(InvalidMatchOutcomeException::class, 'A deleted title cannot change hands.')
        ->and($match->refresh()->winning_side_id)->toBe($winningSide->id);
});

it('records a title-changing finish that keeps the champion of a title deleted since', function (): void {
    // Arrange
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    [$match, $winningSide] = titleMatchOn(now()->subDays(2), $title, Wrestler::factory()->bookable()->create());
    resolve(RecordResultAction::class)->handle($match, matchResult(MatchFinish::Pinfall, $winningSide), administrator());
    resolve(DeleteTitleAction::class)->handle($title);

    // Act
    resolve(RecordResultAction::class)->handle($match, matchResult(MatchFinish::Submission, $winningSide), administrator());

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
    resolve(RecordResultAction::class)->handle($match, matchResult(MatchFinish::TimeLimitDraw, null), administrator());

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
        administrator(),
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
    $title->activityPeriods()->whereNull('ended_at')->sole()->update(['ended_at' => now()->subHour()]);

    // Act
    resolve(RecordResultAction::class)->handle($match, matchResult(MatchFinish::Pinfall, $winningSide), administrator());

    // Assert
    expect($match->refresh()->match_finish)->toBe(MatchFinish::Pinfall)
        ->and($title->championships()->count())->toBe(1);
});

it('leaves the title vacant when a corrected result cannot reopen the previous reign', function (Closure $remove): void {
    // Arrange
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $firstChampion = Wrestler::factory()->bookable()->create();
    [$firstMatch, $firstSide] = titleMatchOn(now()->subDays(20), $title, $firstChampion);
    [$secondMatch, $secondSide] = titleMatchOn(now()->subDays(10), $title, Wrestler::factory()->bookable()->create(), $firstChampion);
    resolve(RecordResultAction::class)->handle($firstMatch, matchResult(MatchFinish::Pinfall, $firstSide), administrator());
    resolve(RecordResultAction::class)->handle($secondMatch, matchResult(MatchFinish::Pinfall, $secondSide), administrator());
    $remove($firstChampion, $title);

    // Act
    resolve(RecordResultAction::class)->handle($secondMatch, matchResult(MatchFinish::TimeLimitDraw, null), administrator());

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

it('reopens the original reign of a corrected winner who has since left, ending it on the day they left', function (TitleType $type, Closure $create, Closure $leave): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-11 12:00:00'));
    $title = Title::factory()->active()->create(['type' => $type]);
    $firstChampion = $create();
    [$firstMatch, $firstSide] = titleMatchOn(now()->subDays(20), $title, $firstChampion);
    [$secondMatch, $secondSide] = titleMatchOn(now()->subDays(10), $title, $create(), $firstChampion);
    $otherSide = $secondMatch->sides()->whereKeyNot($secondSide->id)->sole();
    resolve(RecordResultAction::class)->handle($firstMatch, matchResult(MatchFinish::Pinfall, $firstSide), administrator());
    resolve(RecordResultAction::class)->handle($secondMatch, matchResult(MatchFinish::Pinfall, $secondSide), administrator());
    $leave($firstChampion);

    // Act
    resolve(RecordResultAction::class)->handle($secondMatch, matchResult(MatchFinish::Pinfall, $otherSide), administrator());

    // Assert
    $originalReign = $title->championships()->where('won_match_id', $firstMatch->id)->sole();

    expect($title->championships()->count())->toBe(1)
        ->and($originalReign->lost_match_id)->toBeNull()
        ->and($originalReign->lost_at?->toDateTimeString())->toBe('2026-10-11 12:00:00')
        ->and($title->championships()->current()->exists())->toBeFalse()
        ->and(TitleChampionship::onlyTrashed()->where('won_match_id', $secondMatch->id)->count())->toBe(1);
})->with([
    'singles retired' => [TitleType::Singles, fn () => Wrestler::factory()->bookable()->create(), fn (Wrestler $champion) => resolve(RetireWrestlerAction::class)->handle($champion)],
    'singles released' => [TitleType::Singles, fn () => Wrestler::factory()->bookable()->create(), fn (Wrestler $champion) => resolve(ReleaseWrestlerAction::class)->handle($champion)],
    'tag team retired' => [TitleType::TagTeam, fn () => TagTeam::factory()->bookable()->create(), fn (TagTeam $champion) => resolve(RetireTagTeamAction::class)->handle($champion)],
    'tag team released' => [TitleType::TagTeam, fn () => TagTeam::factory()->bookable()->create(), fn (TagTeam $champion) => resolve(ReleaseTagTeamAction::class)->handle($champion)],
]);

it('reopens the original reign of a corrected winner who is still employed without ending it', function (): void {
    // Arrange
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $firstChampion = Wrestler::factory()->bookable()->create();
    [$firstMatch, $firstSide] = titleMatchOn(now()->subDays(20), $title, $firstChampion);
    [$secondMatch, $secondSide] = titleMatchOn(now()->subDays(10), $title, Wrestler::factory()->bookable()->create(), $firstChampion);
    $otherSide = $secondMatch->sides()->whereKeyNot($secondSide->id)->sole();
    resolve(RecordResultAction::class)->handle($firstMatch, matchResult(MatchFinish::Pinfall, $firstSide), administrator());
    resolve(RecordResultAction::class)->handle($secondMatch, matchResult(MatchFinish::Pinfall, $secondSide), administrator());

    // Act
    resolve(RecordResultAction::class)->handle($secondMatch, matchResult(MatchFinish::Pinfall, $otherSide), administrator());

    // Assert
    $originalReign = $title->championships()->where('won_match_id', $firstMatch->id)->sole();

    expect($title->championships()->count())->toBe(1)
        ->and($originalReign->lost_at)->toBeNull()
        ->and($originalReign->lost_match_id)->toBeNull();
});

it('crowns a corrected winner who was not the previous champion even when the previous champion has left', function (): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-11 12:00:00'));
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $firstChampion = Wrestler::factory()->bookable()->create();
    $secondChampion = Wrestler::factory()->bookable()->create();
    $thirdWrestler = Wrestler::factory()->bookable()->create();
    [$firstMatch, $firstSide] = titleMatchOn(now()->subDays(20), $title, $firstChampion);
    [$secondMatch, $secondSide] = titleMatchOn(now()->subDays(10), $title, $secondChampion, $firstChampion);
    $thirdSide = sideWithCompetitor($secondMatch, 3, competitor: $thirdWrestler);
    resolve(RecordResultAction::class)->handle($firstMatch, matchResult(MatchFinish::Pinfall, $firstSide), administrator());
    resolve(RecordResultAction::class)->handle($secondMatch, matchResult(MatchFinish::Pinfall, $secondSide), administrator());
    resolve(RetireWrestlerAction::class)->handle($firstChampion);

    // Act
    resolve(RecordResultAction::class)->handle($secondMatch, matchResult(MatchFinish::Pinfall, $thirdSide), administrator());

    // Assert
    $newReign = $title->championships()->where('won_match_id', $secondMatch->id)->sole();

    expect($newReign->champion_id)->toBe($thirdWrestler->id)
        ->and($newReign->lost_at)->toBeNull()
        ->and($title->championships()->where('won_match_id', $firstMatch->id)->sole()->lost_match_id)->toBe($secondMatch->id);
});

it('crowns a new reign for the corrected winner whose only departure was before the event day', function (): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-11 12:00:00'));
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $firstChampion = Wrestler::factory()->bookable()->create();
    [$firstMatch, $firstSide] = titleMatchOn(now()->subDays(20), $title, $firstChampion);
    [$secondMatch, $secondSide] = titleMatchOn(now()->subDays(10), $title, Wrestler::factory()->bookable()->create(), $firstChampion);
    $otherSide = $secondMatch->sides()->whereKeyNot($secondSide->id)->sole();
    resolve(RecordResultAction::class)->handle($firstMatch, matchResult(MatchFinish::Pinfall, $firstSide), administrator());
    resolve(RecordResultAction::class)->handle($secondMatch, matchResult(MatchFinish::Pinfall, $secondSide), administrator());
    $firstChampion->employments()->sole()->update([
        'started_at' => now()->subDays(60),
        'ended_at' => now()->subDays(40),
    ]);

    // Act
    resolve(RecordResultAction::class)->handle($secondMatch, matchResult(MatchFinish::Pinfall, $otherSide), administrator());

    // Assert
    $correctedReign = $title->championships()->where('won_match_id', $secondMatch->id)->sole();

    expect($correctedReign->champion_id)->toBe($firstChampion->id)
        ->and($correctedReign->lost_at)->toBeNull()
        ->and($title->championships()->where('won_match_id', $firstMatch->id)->sole()->lost_match_id)->toBe($secondMatch->id);
});

it('keeps the previous champion reign closed by the corrected match when the title is no longer active', function (): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-11 12:00:00'));
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $firstChampion = Wrestler::factory()->bookable()->create();
    [$firstMatch, $firstSide] = titleMatchOn(now()->subDays(20), $title, $firstChampion);
    [$secondMatch, $secondSide] = titleMatchOn(now()->subDays(10), $title, Wrestler::factory()->bookable()->create(), $firstChampion);
    $otherSide = $secondMatch->sides()->whereKeyNot($secondSide->id)->sole();
    resolve(RecordResultAction::class)->handle($firstMatch, matchResult(MatchFinish::Pinfall, $firstSide), administrator());
    resolve(RecordResultAction::class)->handle($secondMatch, matchResult(MatchFinish::Pinfall, $secondSide), administrator());
    resolve(RetireWrestlerAction::class)->handle($firstChampion);
    $title->activityPeriods()->whereNull('ended_at')->sole()->update(['ended_at' => now()->subHour()]);

    // Act
    resolve(RecordResultAction::class)->handle($secondMatch, matchResult(MatchFinish::Pinfall, $otherSide), administrator());

    // Assert
    expect($title->championships()->where('won_match_id', $firstMatch->id)->sole()->lost_match_id)->toBe($secondMatch->id)
        ->and($title->championships()->where('won_match_id', $secondMatch->id)->sole()->champion_id)->toBe($firstChampion->id);
});

it('removes the reign a match created when it is corrected to a winner who left before the show', function (): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-11 12:00:00'));
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $firstWinner = Wrestler::factory()->bookable()->create();
    $secondWinner = Wrestler::factory()->bookable()->create();
    [$match, $firstSide] = titleMatchOn(Carbon::parse('2026-10-06 18:00:00'), $title, $firstWinner, $secondWinner);
    $secondSide = $match->sides()->whereKeyNot($firstSide->id)->sole();
    resolve(RecordResultAction::class)->handle($match, matchResult(MatchFinish::Pinfall, $firstSide), administrator());
    $secondWinner->employments()->sole()->update([
        'started_at' => Carbon::parse('2026-09-01'),
        'ended_at' => Carbon::parse('2026-10-06 09:00:00'),
    ]);

    // Act
    $recorded = resolve(RecordResultAction::class)->handle($match, matchResult(MatchFinish::Pinfall, $secondSide), administrator());

    // Assert
    expect($recorded->titlesLeftUnchanged->pluck('id')->all())->toBe([$title->id])
        ->and($recorded->match->winning_side_id)->toBe($secondSide->id)
        ->and($title->championships()->count())->toBe(0)
        ->and(TitleChampionship::onlyTrashed()->where('champion_id', $firstWinner->id)->count())->toBe(1);
});

it('returns the titles left unchanged because the winner left before the show', function (): void {
    // Arrange
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $winner = Wrestler::factory()->bookable()->create();
    [$match, $winningSide] = titleMatchOn(now()->subDays(5), $title, $winner);
    $winner->employments()->sole()->update([
        'started_at' => now()->subDays(30),
        'ended_at' => now()->subDays(5)->subHour(),
    ]);

    // Act
    $recorded = resolve(RecordResultAction::class)->handle($match, matchResult(MatchFinish::Pinfall, $winningSide), administrator());

    // Assert
    expect($recorded->titlesLeftUnchanged->pluck('id')->all())->toBe([$title->id])
        ->and($recorded->match->match_finish)->toBe(MatchFinish::Pinfall)
        ->and($title->championships()->count())->toBe(0);
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
        administrator(),
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
        administrator(),
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
    resolve(RecordResultAction::class)->handle($match, matchResult(MatchFinish::Pinfall, $winningSide), administrator());

    // Assert
    expect($title->championships()->count())->toBe(2);
});

it('allows correcting the finish of an unchanged winner after a later vacancy and reign', function (): void {
    // Arrange
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    [$match, $side] = titleMatchOn(now()->subDays(20), $title, Wrestler::factory()->bookable()->create());
    resolve(RecordResultAction::class)->handle($match, matchResult(MatchFinish::Pinfall, $side), administrator());
    $title->championships()->current()->sole()->update(['lost_at' => now()->subDays(10)]);
    [$laterMatch, $laterSide] = titleMatchOn(now()->subDays(5), $title, Wrestler::factory()->bookable()->create());
    resolve(RecordResultAction::class)->handle($laterMatch, matchResult(MatchFinish::Pinfall, $laterSide), administrator());

    // Act
    resolve(RecordResultAction::class)->handle($match, matchResult(MatchFinish::Submission, $side), administrator());

    // Assert
    expect($match->refresh()->match_finish)->toBe(MatchFinish::Submission)
        ->and($title->championships()->count())->toBe(2);
});

/**
 * Give the title a reign for the champion, with explicit dates.
 */
function reignOn(Title $title, Wrestler|TagTeam $champion, string $wonAt, ?string $lostAt = null): TitleChampionship
{
    $factory = TitleChampionship::factory()->for($title);
    $factory = $champion instanceof TagTeam ? $factory->forTagTeam($champion) : $factory->forWrestler($champion);

    return $factory->create(['won_at' => $wonAt, 'lost_at' => $lostAt]);
}

/** @return array<int, array{int, string, ?string, ?int, ?string}> */
function reignSnapshot(Title $title): array
{
    return $title->championships()
        ->withTrashed()
        ->orderBy('id')
        ->get()
        ->map(fn (TitleChampionship $reign): array => [
            $reign->champion_id,
            $reign->won_at->toDateTimeString(),
            $reign->lost_at?->toDateTimeString(),
            $reign->lost_match_id,
            $reign->deleted_at?->toDateTimeString(),
        ])
        ->all();
}

// In each case the champion (A) held the title on 2026-09-10, after which it moved on or was vacated.
dataset('titles that moved on after the event', [
    'singles, later reign by another champion' => [TitleType::Singles, 'later reign by another champion'],
    'singles, own reign later vacated' => [TitleType::Singles, 'own reign later vacated'],
    'tag team, later reign by another champion' => [TitleType::TagTeam, 'later reign by another champion'],
    'tag team, own reign later vacated' => [TitleType::TagTeam, 'own reign later vacated'],
]);

it('records a back-filled defence after the title has moved on', function (TitleType $type, string $scenario): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-04 12:00:00'));
    $newChampion = fn () => $type === TitleType::Singles ? Wrestler::factory()->bookable()->create() : TagTeam::factory()->bookable()->create();
    $title = Title::factory()->active()->create(['type' => $type]);
    $champion = $newChampion();
    reignOn($title, $champion, '2026-09-01 12:00:00', '2026-09-20 12:00:00');

    if ($scenario === 'later reign by another champion') {
        reignOn($title, $newChampion(), '2026-09-20 12:00:00');
    }
    $before = reignSnapshot($title);
    [$match, $winningSide] = titleMatchOn(Carbon::parse('2026-09-10 12:00:00'), $title, $champion);

    // Act
    resolve(RecordResultAction::class)->handle($match, matchResult(MatchFinish::Pinfall, $winningSide), administrator());

    // Assert
    expect($match->refresh()->match_finish)->toBe(MatchFinish::Pinfall)
        ->and($match->winning_side_id)->toBe($winningSide->id)
        ->and(reignSnapshot($title))->toBe($before);
})->with('titles that moved on after the event');

it('records a back-filled defence of a deleted title without treating it as a title change', function (): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-04 12:00:00'));
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $champion = Wrestler::factory()->bookable()->create();
    reignOn($title, $champion, '2026-09-01 12:00:00', '2026-09-20 12:00:00');
    reignOn($title, Wrestler::factory()->bookable()->create(), '2026-09-20 12:00:00');
    $before = reignSnapshot($title);
    [$match, $winningSide] = titleMatchOn(Carbon::parse('2026-09-10 12:00:00'), $title, $champion);
    $title->delete();

    // Act
    resolve(RecordResultAction::class)->handle($match, matchResult(MatchFinish::Pinfall, $winningSide), administrator());

    // Assert
    expect($match->refresh()->match_finish)->toBe(MatchFinish::Pinfall)
        ->and(reignSnapshot($title))->toBe($before);
});

it('still rejects a title change back-dated before a later reign or inside a vacated one', function (bool $laterReignExists): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-04 12:00:00'));
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    reignOn($title, Wrestler::factory()->bookable()->create(), '2026-09-01 12:00:00', '2026-09-20 12:00:00');

    if ($laterReignExists) {
        reignOn($title, Wrestler::factory()->bookable()->create(), '2026-09-20 12:00:00');
    }
    $before = reignSnapshot($title);
    [$match, $winningSide] = titleMatchOn(Carbon::parse('2026-09-10 12:00:00'), $title, Wrestler::factory()->bookable()->create());

    // Act
    $record = fn () => resolve(RecordResultAction::class)->handle($match, matchResult(MatchFinish::Pinfall, $winningSide), administrator());

    // Assert
    expect($record)->toThrow(InvalidMatchOutcomeException::class, 'record results in date order')
        ->and($match->refresh()->match_finish)->toBeNull()
        ->and(reignSnapshot($title))->toBe($before);
})->with([
    'later reign' => true,
    'vacated reign' => false,
]);

it('corrects a recorded defence into a title change only while no later reign exists', function (bool $laterReignExists): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-04 12:00:00'));
    $title = Title::factory()->active()->create(['type' => TitleType::Singles]);
    $champion = Wrestler::factory()->bookable()->create();
    $firstReign = reignOn($title, $champion, '2026-09-01 12:00:00', $laterReignExists ? '2026-09-20 12:00:00' : null);

    if ($laterReignExists) {
        reignOn($title, Wrestler::factory()->bookable()->create(), '2026-09-20 12:00:00');
    }
    [$match, $championSide] = titleMatchOn(Carbon::parse('2026-09-10 12:00:00'), $title, $champion);
    $challengerSide = $match->sides()->whereKeyNot($championSide->id)->sole();
    resolve(RecordResultAction::class)->handle($match, matchResult(MatchFinish::Pinfall, $championSide), administrator());
    $before = reignSnapshot($title);

    // Act
    $correct = fn () => resolve(RecordResultAction::class)->handle($match, matchResult(MatchFinish::Pinfall, $challengerSide), administrator());

    // Assert
    if ($laterReignExists) {
        expect($correct)->toThrow(InvalidMatchOutcomeException::class, 'record results in date order')
            ->and(reignSnapshot($title))->toBe($before);

        return;
    }

    $correct();

    expect($firstReign->refresh()->lost_match_id)->toBe($match->id)
        ->and($firstReign->lost_at?->toDateTimeString())->toBe('2026-09-10 12:00:00')
        ->and($title->championships()->current()->sole()->won_match_id)->toBe($match->id);
})->with([
    'no later reign' => false,
    'later reign' => true,
]);

test('recording a tournament result names the actor passed in as the audit causer', function (): void {
    // Arrange
    actingAs(administrator());
    $actor = administrator();
    $world = tournamentFixture();
    $match = bookTournamentNode($world, $world['tournament']->nodes()->where('round', 1)->where('position', 1)->firstOrFail());
    $result = new MatchResultData(MatchFinish::Pinfall, $match->sides()->firstOrFail(), collect(), $world['tournament']->refresh()->version);
    $since = lastActivityId();

    // Act
    resolve(RecordResultAction::class)->handle($match, $result, $actor);

    // Assert
    expect(activityEntriesAfter($since, 'tournaments'))->toBe(['tournament.progressed|'.causerTag($actor)]);
});
