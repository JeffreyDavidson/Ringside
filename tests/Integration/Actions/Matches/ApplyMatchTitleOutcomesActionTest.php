<?php

declare(strict_types=1);

use App\Actions\Matches\ApplyMatchTitleOutcomesAction;
use App\Data\Matches\MatchResultData;
use App\Enums\Matches\MatchFinish;
use App\Enums\Titles\TitleType;
use App\Exceptions\Matches\InvalidMatchOutcomeException;
use App\Models\Events\Event;
use App\Models\Lifecycle\Employment;
use App\Models\Lifecycle\Retirement;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchSide;
use App\Models\Promotions\Promotion;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

use function Pest\Laravel\travelTo;

/**
 * Book a one-on-one title match for an event in the given time zone, with the winner or title in the given state.
 *
 * @return array{EventMatch, MatchSide, Wrestler, Title}
 */
function titleMatchWithWinner(string $timezone, string $eventDate, string $availability): array
{
    $promotion = Promotion::factory()->create(['timezone' => $timezone]);
    $event = Event::factory()->create(['promotion_id' => $promotion->id, 'date' => Carbon::parse($eventDate)]);
    $match = EventMatch::factory()->forEvent($event)->create();
    $title = $availability === 'title pulled'
        ? Title::factory()->singles()->retired()->create()
        : Title::factory()->singles()->active()->create();
    $winner = $availability === 'winner injured'
        ? Wrestler::factory()->injured()->create()
        : Wrestler::factory()->bookable()->create();
    $loser = Wrestler::factory()->bookable()->create();
    $match->titles()->attach($title);
    $winningSide = MatchSide::factory()->for($match, 'match')->create(['position' => 1]);
    $losingSide = MatchSide::factory()->for($match, 'match')->create(['position' => 2]);
    $match->competitors()->createMany([
        ['match_side_id' => $winningSide->id, 'competitor_type' => $winner->getMorphClass(), 'competitor_id' => $winner->id],
        ['match_side_id' => $losingSide->id, 'competitor_type' => $loser->getMorphClass(), 'competitor_id' => $loser->id],
    ]);

    return [$match, $winningSide, $winner, $title];
}

/**
 * Book a title match on the given date between two competitors, one per side.
 *
 * @return array{EventMatch, MatchSide, MatchSide}
 */
function titleMatchBetween(Title $title, string $eventDate, Wrestler|TagTeam $first, Wrestler|TagTeam $second): array
{
    $event = Event::factory()->create(['date' => Carbon::parse($eventDate)]);
    $match = EventMatch::factory()->forEvent($event)->create();
    $match->titles()->attach($title);
    $firstSide = MatchSide::factory()->for($match, 'match')->create(['position' => 1]);
    $secondSide = MatchSide::factory()->for($match, 'match')->create(['position' => 2]);
    $match->competitors()->createMany([
        ['match_side_id' => $firstSide->id, 'competitor_type' => $first->getMorphClass(), 'competitor_id' => $first->id],
        ['match_side_id' => $secondSide->id, 'competitor_type' => $second->getMorphClass(), 'competitor_id' => $second->id],
    ]);

    return [$match, $firstSide, $secondSide];
}

/** @return Collection<int, Title> The titles whose champion was left unchanged because the winner had left. */
function recordTitleMatchResult(EventMatch $match, MatchSide $winningSide): Collection
{
    return resolve(ApplyMatchTitleOutcomesAction::class)->handle(
        $match,
        new MatchResultData(MatchFinish::Pinfall, $winningSide, new Collection),
        $match->competitors()->with('competitor')->get(),
    );
}

dataset('availability lost after the event', ['winner injured', 'title pulled']);

// Now is 2026-10-04 12:00 UTC. Each event is on an earlier day in its promotion's own time zone.
dataset('events from an earlier local day', [
    'same zone, yesterday' => ['UTC', '2026-10-03 12:00:00'],
    'east of UTC, same UTC day but yesterday locally' => ['Pacific/Auckland', '2026-10-04 05:00:00'],
]);

// Each event is on the same local day as now, or later in it.
dataset('events from the current local day', [
    'same zone, earlier today' => ['UTC', '2026-10-04 06:00:00'],
    'west of UTC, previous UTC day but same day locally' => ['Pacific/Honolulu', '2026-10-04 20:00:00', '2026-10-05 05:00:00'],
]);

test('it leaves matches without titles unchanged', function (): void {
    $event = Event::factory()->past()->create();
    $match = EventMatch::factory()->forEvent($event)->create();

    resolve(ApplyMatchTitleOutcomesAction::class)->handle(
        $match,
        new MatchResultData(MatchFinish::Pinfall, null, new Collection),
        $match->competitors,
    );

    expect($match->titles)->toBeEmpty();
});

test('it rejects a title match with multiple eligible winners', function (): void {
    $event = Event::factory()->past()->create();
    $match = EventMatch::factory()->forEvent($event)->create();
    $title = Title::factory()->singles()->active()->create();
    $match->titles()->attach($title);
    $winningSide = MatchSide::factory()->for($match, 'match')->create(['position' => 1]);
    MatchSide::factory()->for($match, 'match')->create(['position' => 2]);
    $firstWinner = Wrestler::factory()->bookable()->create();
    $secondWinner = Wrestler::factory()->bookable()->create();
    $match->competitors()->createMany([
        [
            'match_side_id' => $winningSide->id,
            'competitor_type' => $firstWinner->getMorphClass(),
            'competitor_id' => $firstWinner->id,
        ],
        [
            'match_side_id' => $winningSide->id,
            'competitor_type' => $secondWinner->getMorphClass(),
            'competitor_id' => $secondWinner->id,
        ],
    ]);

    expect(fn () => resolve(ApplyMatchTitleOutcomesAction::class)->handle(
        $match,
        new MatchResultData(MatchFinish::Pinfall, $winningSide, new Collection),
        $match->competitors()->with('competitor')->get(),
    ))->toThrow(InvalidMatchOutcomeException::class);
});

test('it transfers the current singles championship to the winning wrestler', function (): void {
    $event = Event::factory()->past()->create();
    $match = EventMatch::factory()->forEvent($event)->create();
    $title = Title::factory()->singles()->active()->create();
    $match->titles()->attach($title);
    $champion = Wrestler::factory()->bookable()->create();
    $challenger = Wrestler::factory()->bookable()->create();
    $currentSide = MatchSide::factory()->for($match, 'match')->create(['position' => 1]);
    $winningSide = MatchSide::factory()->for($match, 'match')->create(['position' => 2]);
    $match->competitors()->createMany([
        [
            'match_side_id' => $currentSide->id,
            'competitor_type' => $champion->getMorphClass(),
            'competitor_id' => $champion->id,
        ],
        [
            'match_side_id' => $winningSide->id,
            'competitor_type' => $challenger->getMorphClass(),
            'competitor_id' => $challenger->id,
        ],
    ]);
    $currentReign = TitleChampionship::factory()->forWrestler($champion)->current()->create([
        'title_id' => $title->id,
    ]);

    resolve(ApplyMatchTitleOutcomesAction::class)->handle(
        $match,
        new MatchResultData(MatchFinish::Pinfall, $winningSide, new Collection),
        $match->competitors()->with('competitor')->get(),
    );

    expect($currentReign->refresh()->lost_match_id)->toBe($match->id)
        ->and(TitleChampionship::query()
            ->where('title_id', $title->id)
            ->where('champion_id', $challenger->id)
            ->where('won_match_id', $match->id)
            ->exists())->toBeTrue();
});

test('it keeps the existing championship changes when the same result is applied again', function (): void {
    $event = Event::factory()->past()->create();
    $match = EventMatch::factory()->forEvent($event)->create();
    $title = Title::factory()->singles()->active()->create();
    $match->titles()->attach($title);
    $champion = Wrestler::factory()->bookable()->create();
    $challenger = Wrestler::factory()->bookable()->create();
    $currentSide = MatchSide::factory()->for($match, 'match')->create(['position' => 1]);
    $winningSide = MatchSide::factory()->for($match, 'match')->create(['position' => 2]);
    $match->competitors()->createMany([
        [
            'match_side_id' => $currentSide->id,
            'competitor_type' => $champion->getMorphClass(),
            'competitor_id' => $champion->id,
        ],
        [
            'match_side_id' => $winningSide->id,
            'competitor_type' => $challenger->getMorphClass(),
            'competitor_id' => $challenger->id,
        ],
    ]);
    $previousReign = TitleChampionship::factory()->forWrestler($champion)->current()->create([
        'title_id' => $title->id,
    ]);
    $result = new MatchResultData(MatchFinish::Pinfall, $winningSide, new Collection);
    $action = resolve(ApplyMatchTitleOutcomesAction::class);

    $action->handle($match, $result, $match->competitors()->with('competitor')->get());
    $newReign = TitleChampionship::query()->where('won_match_id', $match->id)->sole();

    $action->handle($match, $result, $match->competitors()->with('competitor')->get());

    expect(TitleChampionship::query()->where('title_id', $title->id)->count())->toBe(2)
        ->and(TitleChampionship::query()->where('won_match_id', $match->id)->sole()->is($newReign))->toBeTrue()
        ->and($newReign->refresh()->champion_id)->toBe($challenger->id)
        ->and($newReign->lost_at)->toBeNull()
        ->and($previousReign->refresh()->lost_match_id)->toBe($match->id);
});

test('it records a champion for an earlier local day even though they are no longer available', function (string $availability, string $timezone, string $eventDate): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-04 12:00:00'));
    [$match, $winningSide, $winner, $title] = titleMatchWithWinner($timezone, $eventDate, $availability);

    // Act
    recordTitleMatchResult($match, $winningSide);

    // Assert
    expect(TitleChampionship::query()
        ->where('title_id', $title->id)
        ->where('champion_id', $winner->id)
        ->where('won_match_id', $match->id)
        ->exists())->toBeTrue();
})->with('availability lost after the event')->with('events from an earlier local day');

test('it rejects a champion who is no longer available when the event is on the current local day', function (string $availability, string $timezone, string $eventDate, string $now = '2026-10-04 12:00:00'): void {
    // Arrange
    travelTo($now);
    [$match, $winningSide, , $title] = titleMatchWithWinner($timezone, $eventDate, $availability);

    // Act
    $record = fn () => recordTitleMatchResult($match, $winningSide);

    // Assert
    expect($record)->toThrow(InvalidMatchOutcomeException::class)
        ->and(TitleChampionship::query()->where('title_id', $title->id)->exists())->toBeFalse();
})->with('availability lost after the event')->with('events from the current local day');

test('it still rejects a deleted title for an earlier local day', function (): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-04 12:00:00'));
    [$match, $winningSide, , $title] = titleMatchWithWinner('UTC', '2026-10-03 12:00:00', 'available');
    $title->delete();

    // Act
    $record = fn () => recordTitleMatchResult($match, $winningSide);

    // Assert
    expect($record)->toThrow(InvalidMatchOutcomeException::titleDeleted());
});

test('it still rejects a deleted winner for an earlier local day', function (): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-04 12:00:00'));
    [$match, $winningSide, $winner] = titleMatchWithWinner('UTC', '2026-10-03 12:00:00', 'available');
    $winner->delete();

    // Act
    $record = fn () => recordTitleMatchResult($match, $winningSide);

    // Assert
    expect($record)->toThrow(InvalidMatchOutcomeException::winnerDeleted());
});

test('it refuses a title result when the champion of the event date is not in the match', function (): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-04 12:00:00'));
    $title = Title::factory()->singles()->active()->create();
    [$champion, $firstChallenger, $secondChallenger] = Wrestler::factory()->bookable()->count(3)->create()->all();
    TitleChampionship::factory()->forWrestler($champion)->current()->wonOn('2026-08-01')->create(['title_id' => $title->id]);
    [$firstMatch, $firstWinningSide] = titleMatchBetween($title, '2026-09-01', $firstChallenger, $champion);
    [$secondMatch, $secondWinningSide] = titleMatchBetween($title, '2026-09-15', $secondChallenger, $champion);
    recordTitleMatchResult($firstMatch, $firstWinningSide);

    // Act
    $record = fn () => recordTitleMatchResult($secondMatch, $secondWinningSide);

    // Assert
    expect($record)->toThrow(InvalidMatchOutcomeException::titleChampionNotInMatch($title))
        ->and(TitleChampionship::query()->where('title_id', $title->id)->count())->toBe(2)
        ->and(TitleChampionship::query()->where('title_id', $title->id)->current()->sole())
        ->champion_id->toBe($firstChallenger->id)
        ->lost_at->toBeNull();
});

test('it refuses a draw for a title when the champion of the event date is not in the match', function (): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-04 12:00:00'));
    $title = Title::factory()->singles()->active()->create();
    [$champion, $firstChallenger, $secondChallenger] = Wrestler::factory()->bookable()->count(3)->create()->all();
    TitleChampionship::factory()->forWrestler($champion)->current()->wonOn('2026-08-01')->create(['title_id' => $title->id]);
    [$firstMatch, $firstWinningSide] = titleMatchBetween($title, '2026-09-01', $firstChallenger, $champion);
    [$secondMatch] = titleMatchBetween($title, '2026-09-15', $secondChallenger, $champion);
    recordTitleMatchResult($firstMatch, $firstWinningSide);
    $reignCount = TitleChampionship::query()->count();

    // Act
    $record = fn () => resolve(ApplyMatchTitleOutcomesAction::class)->handle(
        $secondMatch,
        new MatchResultData(MatchFinish::TimeLimitDraw, null, new Collection),
        $secondMatch->competitors()->with('competitor')->get(),
    );

    // Assert
    expect($record)->toThrow(InvalidMatchOutcomeException::titleChampionNotInMatch($title))
        ->and(TitleChampionship::query()->count())->toBe($reignCount);
});

test('it does not check the champion for a title match at an event without a date', function (): void {
    // Arrange
    $title = Title::factory()->singles()->active()->create();
    [$champion, $firstChallenger, $secondChallenger] = Wrestler::factory()->bookable()->count(3)->create()->all();
    TitleChampionship::factory()->forWrestler($champion)->current()->wonOn('2026-08-01')->create(['title_id' => $title->id]);
    $match = EventMatch::factory()->forEvent(Event::factory()->unscheduled()->create())->create();
    $match->titles()->attach($title);
    $firstSide = MatchSide::factory()->for($match, 'match')->create(['position' => 1]);
    $secondSide = MatchSide::factory()->for($match, 'match')->create(['position' => 2]);
    $match->competitors()->createMany([
        ['match_side_id' => $firstSide->id, 'competitor_type' => $firstChallenger->getMorphClass(), 'competitor_id' => $firstChallenger->id],
        ['match_side_id' => $secondSide->id, 'competitor_type' => $secondChallenger->getMorphClass(), 'competitor_id' => $secondChallenger->id],
    ]);

    // Act
    resolve(ApplyMatchTitleOutcomesAction::class)->handle(
        $match,
        new MatchResultData(MatchFinish::TimeLimitDraw, null, new Collection),
        $match->competitors()->with('competitor')->get(),
    );

    // Assert
    $reign = TitleChampionship::query()->sole();
    expect($reign->champion->is($champion))->toBeTrue()
        ->and($reign->lost_at)->toBeNull();
});

test('it refuses a tag team title result when the champion tag team is not in the match', function (): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-04 12:00:00'));
    $title = Title::factory()->tagTeam()->active()->create();
    [$champions, $challengers] = TagTeam::factory()->bookable()->count(2)->create()->all();
    $outsiders = TagTeam::factory()->bookable()->create();
    TitleChampionship::factory()->forTagTeam($champions)->current()->wonOn('2026-08-01')->create(['title_id' => $title->id]);
    [$match, $winningSide] = titleMatchBetween($title, '2026-09-15', $challengers, $outsiders);

    // Act
    $record = fn () => recordTitleMatchResult($match, $winningSide);

    // Assert
    expect($record)->toThrow(InvalidMatchOutcomeException::titleChampionNotInMatch($title))
        ->and(TitleChampionship::query()->where('title_id', $title->id)->count())->toBe(1)
        ->and(TitleChampionship::query()->where('title_id', $title->id)->current()->sole()->champion_id)->toBe($champions->id);
});

test('it records results in date order when the champion of each event date is in the match', function (): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-04 12:00:00'));
    $title = Title::factory()->singles()->active()->create();
    [$champion, $newChampion, $challenger] = Wrestler::factory()->bookable()->count(3)->create()->all();
    TitleChampionship::factory()->forWrestler($champion)->current()->wonOn('2026-08-01')->create(['title_id' => $title->id]);
    [$firstMatch, $firstWinningSide] = titleMatchBetween($title, '2026-09-01', $newChampion, $champion);
    [$secondMatch, $secondWinningSide] = titleMatchBetween($title, '2026-09-15', $challenger, $newChampion);

    // Act
    recordTitleMatchResult($firstMatch, $firstWinningSide);
    recordTitleMatchResult($secondMatch, $secondWinningSide);

    // Assert
    expect(TitleChampionship::query()->where('title_id', $title->id)->current()->sole()->champion_id)->toBe($challenger->id)
        ->and(TitleChampionship::query()->where('title_id', $title->id)->count())->toBe(3);
});

test('it accepts a title result when the title was vacant on the event date', function (): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-04 12:00:00'));
    $title = Title::factory()->singles()->active()->create();
    [$winner, $loser] = Wrestler::factory()->bookable()->count(2)->create()->all();
    [$match, $winningSide] = titleMatchBetween($title, '2026-09-15', $winner, $loser);

    // Act
    recordTitleMatchResult($match, $winningSide);

    // Assert
    expect(TitleChampionship::query()->where('title_id', $title->id)->current()->sole()->champion_id)->toBe($winner->id);
});

test('it accepts the same title result again when the champion won the title at that match', function (): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-04 12:00:00'));
    $title = Title::factory()->singles()->active()->create();
    [$champion, $challenger] = Wrestler::factory()->bookable()->count(2)->create()->all();
    TitleChampionship::factory()->forWrestler($champion)->current()->wonOn('2026-08-01')->create(['title_id' => $title->id]);
    [$match, $winningSide] = titleMatchBetween($title, '2026-09-15', $challenger, $champion);
    recordTitleMatchResult($match, $winningSide);

    // Act
    recordTitleMatchResult($match, $winningSide);

    // Assert
    expect(TitleChampionship::query()->where('title_id', $title->id)->count())->toBe(2)
        ->and(TitleChampionship::query()->where('title_id', $title->id)->current()->sole()->champion_id)->toBe($challenger->id);
});

test('it ends a back-filled reign on the date the winner was released', function (): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-11 12:00:00'));
    $title = Title::factory()->singles()->active()->create();
    $winner = Wrestler::factory()
        ->has(Employment::factory()->started(Carbon::parse('2026-09-01'))->ended(Carbon::parse('2026-10-09')), 'employments')
        ->create();
    $loser = Wrestler::factory()->bookable()->create();
    [$match, $winningSide] = titleMatchBetween($title, '2026-10-06', $winner, $loser);

    // Act
    recordTitleMatchResult($match, $winningSide);

    // Assert
    $reign = TitleChampionship::query()->where('title_id', $title->id)->sole();
    expect($reign->champion_id)->toBe($winner->id)
        ->and($reign->won_match_id)->toBe($match->id)
        ->and($reign->lost_at?->toDateString())->toBe('2026-10-09')
        ->and($reign->lost_match_id)->toBeNull()
        ->and(TitleChampionship::query()->where('title_id', $title->id)->current()->exists())->toBeFalse();
});

test('it ends a back-filled reign on the date the winner was retired', function (): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-11 12:00:00'));
    $title = Title::factory()->singles()->active()->create();
    $winner = Wrestler::factory()
        ->has(Employment::factory()->started(Carbon::parse('2026-09-01'))->ended(Carbon::parse('2026-10-09')), 'employments')
        ->has(Retirement::factory()->started(Carbon::parse('2026-10-09')), 'retirements')
        ->create();
    $loser = Wrestler::factory()->bookable()->create();
    [$match, $winningSide] = titleMatchBetween($title, '2026-10-06', $winner, $loser);

    // Act
    recordTitleMatchResult($match, $winningSide);

    // Assert
    $reign = TitleChampionship::query()->where('title_id', $title->id)->sole();
    expect($reign->lost_at?->toDateString())->toBe('2026-10-09')
        ->and($reign->lost_match_id)->toBeNull();
});

test('it ends a back-filled reign on the date the winning tag team was released', function (): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-11 12:00:00'));
    $title = Title::factory()->tagTeam()->active()->create();
    $winner = TagTeam::factory()
        ->has(Employment::factory()->started(Carbon::parse('2026-09-01'))->ended(Carbon::parse('2026-10-09')), 'employments')
        ->create();
    $loser = TagTeam::factory()->bookable()->create();
    [$match, $winningSide] = titleMatchBetween($title, '2026-10-06', $winner, $loser);

    // Act
    recordTitleMatchResult($match, $winningSide);

    // Assert
    $reign = TitleChampionship::query()->where('title_id', $title->id)->sole();
    expect($reign->champion_id)->toBe($winner->id)
        ->and($reign->lost_at?->toDateString())->toBe('2026-10-09');
});

test('it ends a back-filled reign when the winning tag team was retired', function (): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-11 12:00:00'));
    $title = Title::factory()->tagTeam()->active()->create();
    $winner = TagTeam::factory()
        ->has(Employment::factory()->started(Carbon::parse('2026-09-01'))->ended(Carbon::parse('2026-10-09')), 'employments')
        ->has(Retirement::factory()->started(Carbon::parse('2026-10-09')), 'retirements')
        ->create();
    $loser = TagTeam::factory()->bookable()->create();
    [$match, $winningSide] = titleMatchBetween($title, '2026-10-06', $winner, $loser);

    // Act
    recordTitleMatchResult($match, $winningSide);

    // Assert
    expect(TitleChampionship::query()->where('title_id', $title->id)->sole()->lost_at?->toDateString())->toBe('2026-10-09');
});

test('it ends a back-filled reign at the first release when the winner was rehired', function (): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-11 12:00:00'));
    $title = Title::factory()->singles()->active()->create();
    $winner = Wrestler::factory()
        ->has(Employment::factory()->started(Carbon::parse('2026-09-01'))->ended(Carbon::parse('2026-10-08')), 'employments')
        ->has(Employment::factory()->started(Carbon::parse('2026-10-10')), 'employments')
        ->create();
    $loser = Wrestler::factory()->bookable()->create();
    [$match, $winningSide] = titleMatchBetween($title, '2026-10-06', $winner, $loser);

    // Act
    recordTitleMatchResult($match, $winningSide);

    // Assert
    expect(TitleChampionship::query()->where('title_id', $title->id)->sole()->lost_at?->toDateString())->toBe('2026-10-08');
});

test('it ends a back-filled reign at the first retirement when the winner was unretired', function (): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-11 12:00:00'));
    $title = Title::factory()->singles()->active()->create();
    $winner = Wrestler::factory()
        ->has(Employment::factory()->started(Carbon::parse('2026-09-01'))->ended(Carbon::parse('2026-10-08')), 'employments')
        ->has(Retirement::factory()->started(Carbon::parse('2026-10-08'))->ended(Carbon::parse('2026-10-09')), 'retirements')
        ->has(Employment::factory()->started(Carbon::parse('2026-10-09')), 'employments')
        ->create();
    $loser = Wrestler::factory()->bookable()->create();
    [$match, $winningSide] = titleMatchBetween($title, '2026-10-06', $winner, $loser);

    // Act
    recordTitleMatchResult($match, $winningSide);

    // Assert
    expect(TitleChampionship::query()->where('title_id', $title->id)->sole()->lost_at?->toDateString())->toBe('2026-10-08');
});

test('it ignores a release that happened before the event day when ending a back-filled reign', function (): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-11 12:00:00'));
    $title = Title::factory()->singles()->active()->create();
    $winner = Wrestler::factory()
        ->has(Employment::factory()->started(Carbon::parse('2026-08-01'))->ended(Carbon::parse('2026-08-15')), 'employments')
        ->has(Employment::factory()->started(Carbon::parse('2026-09-01')), 'employments')
        ->create();
    $loser = Wrestler::factory()->bookable()->create();
    [$match, $winningSide] = titleMatchBetween($title, '2026-10-06', $winner, $loser);

    // Act
    recordTitleMatchResult($match, $winningSide);

    // Assert
    expect(TitleChampionship::query()->where('title_id', $title->id)->current()->sole()->champion_id)->toBe($winner->id);
});

test('it records a back-filled result without a title change when the winner was released before the show on the event day', function (): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-11 12:00:00'));
    $promotion = Promotion::factory()->create(['timezone' => 'America/New_York']);
    $event = Event::factory()->create(['promotion_id' => $promotion->id, 'date' => Carbon::parse('2026-10-07 00:30:00')]);
    $title = Title::factory()->singles()->active()->create();
    $winner = Wrestler::factory()
        ->has(Employment::factory()->started(Carbon::parse('2026-09-01'))->ended(Carbon::parse('2026-10-06 12:00:00')), 'employments')
        ->create();
    $loser = Wrestler::factory()->bookable()->create();
    $match = EventMatch::factory()->forEvent($event)->create();
    $match->titles()->attach($title);
    $winningSide = MatchSide::factory()->for($match, 'match')->create(['position' => 1]);
    $losingSide = MatchSide::factory()->for($match, 'match')->create(['position' => 2]);
    $match->competitors()->createMany([
        ['match_side_id' => $winningSide->id, 'competitor_type' => $winner->getMorphClass(), 'competitor_id' => $winner->id],
        ['match_side_id' => $losingSide->id, 'competitor_type' => $loser->getMorphClass(), 'competitor_id' => $loser->id],
    ]);

    // Act
    $unchangedTitles = recordTitleMatchResult($match, $winningSide);

    // Assert
    expect(TitleChampionship::withTrashed()->where('title_id', $title->id)->exists())->toBeFalse()
        ->and($unchangedTitles->pluck('id')->all())->toBe([$title->id]);
});

test('it records a back-filled result without a title change when the winner left before the show', function (TitleType $type, Closure $makeWinner, Closure $makeLoser): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-11 12:00:00'));
    $title = Title::factory()->active()->create(['type' => $type]);
    $winner = $makeWinner();
    [$match, $winningSide] = titleMatchBetween($title, '2026-10-06 18:00:00', $winner, $makeLoser());

    // Act
    $unchangedTitles = recordTitleMatchResult($match, $winningSide);

    // Assert
    expect(TitleChampionship::withTrashed()->where('title_id', $title->id)->exists())->toBeFalse()
        ->and($unchangedTitles->pluck('id')->all())->toBe([$title->id]);
})->with([
    'singles released earlier that day' => [
        TitleType::Singles,
        fn () => Wrestler::factory()->has(Employment::factory()->started(Carbon::parse('2026-09-01'))->ended(Carbon::parse('2026-10-06 09:00:00')), 'employments')->create(),
        fn () => Wrestler::factory()->bookable()->create(),
    ],
    'singles retired earlier that day' => [
        TitleType::Singles,
        fn () => Wrestler::factory()->has(Employment::factory()->started(Carbon::parse('2026-09-01'))->ended(Carbon::parse('2026-10-06 09:00:00')), 'employments')->has(Retirement::factory()->started(Carbon::parse('2026-10-06 09:00:00')), 'retirements')->create(),
        fn () => Wrestler::factory()->bookable()->create(),
    ],
    'singles released at the start of the show' => [
        TitleType::Singles,
        fn () => Wrestler::factory()->has(Employment::factory()->started(Carbon::parse('2026-09-01'))->ended(Carbon::parse('2026-10-06 18:00:00')), 'employments')->create(),
        fn () => Wrestler::factory()->bookable()->create(),
    ],
    'tag team released earlier that day' => [
        TitleType::TagTeam,
        fn () => TagTeam::factory()->has(Employment::factory()->started(Carbon::parse('2026-09-01'))->ended(Carbon::parse('2026-10-06 09:00:00')), 'employments')->create(),
        fn () => TagTeam::factory()->bookable()->create(),
    ],
    'tag team retired earlier that day' => [
        TitleType::TagTeam,
        fn () => TagTeam::factory()->has(Employment::factory()->started(Carbon::parse('2026-09-01'))->ended(Carbon::parse('2026-10-06 09:00:00')), 'employments')->has(Retirement::factory()->started(Carbon::parse('2026-10-06 09:00:00')), 'retirements')->create(),
        fn () => TagTeam::factory()->bookable()->create(),
    ],
]);

test('it still crowns and ends the reign of a winner who left after the show started on the event day', function (): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-11 12:00:00'));
    $title = Title::factory()->singles()->active()->create();
    $winner = Wrestler::factory()
        ->has(Employment::factory()->started(Carbon::parse('2026-09-01'))->ended(Carbon::parse('2026-10-06 20:00:00')), 'employments')
        ->create();
    $loser = Wrestler::factory()->bookable()->create();
    [$match, $winningSide] = titleMatchBetween($title, '2026-10-06 18:00:00', $winner, $loser);

    // Act
    $unchangedTitles = recordTitleMatchResult($match, $winningSide);

    // Assert
    $reign = TitleChampionship::query()->where('title_id', $title->id)->sole();
    expect($reign->champion_id)->toBe($winner->id)
        ->and($reign->lost_at?->toDateTimeString())->toBe('2026-10-06 20:00:00')
        ->and($unchangedTitles)->toBeEmpty();
});

test('it keeps the reign a result already created when the same result is recorded again after the winner left before the show', function (): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-11 12:00:00'));
    $title = Title::factory()->singles()->active()->create();
    $winner = Wrestler::factory()
        ->has(Employment::factory()->started(Carbon::parse('2026-09-01')), 'employments')
        ->create();
    $loser = Wrestler::factory()->bookable()->create();
    [$match, $winningSide] = titleMatchBetween($title, '2026-10-06 18:00:00', $winner, $loser);
    recordTitleMatchResult($match, $winningSide);
    $winner->employments()->sole()->update(['ended_at' => Carbon::parse('2026-10-06 09:00:00')]);

    // Act
    $unchangedTitles = recordTitleMatchResult($match, $winningSide);

    // Assert
    expect(TitleChampionship::query()->where('title_id', $title->id)->where('champion_id', $winner->id)->count())->toBe(1)
        ->and($unchangedTitles)->toBeEmpty();
});

test('it leaves a back-filled reign open for a winner who is still employed', function (): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-11 12:00:00'));
    $title = Title::factory()->singles()->active()->create();
    [$winner, $loser] = Wrestler::factory()->bookable()->count(2)->create()->all();
    [$match, $winningSide] = titleMatchBetween($title, '2026-10-06', $winner, $loser);

    // Act
    recordTitleMatchResult($match, $winningSide);

    // Assert
    expect(TitleChampionship::query()->where('title_id', $title->id)->current()->sole()->champion_id)->toBe($winner->id);
});

test('it keeps a released winner reign closed when the same result is recorded again', function (): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-11 12:00:00'));
    $title = Title::factory()->singles()->active()->create();
    $winner = Wrestler::factory()
        ->has(Employment::factory()->started(Carbon::parse('2026-09-01'))->ended(Carbon::parse('2026-10-09')), 'employments')
        ->create();
    $loser = Wrestler::factory()->bookable()->create();
    [$match, $winningSide] = titleMatchBetween($title, '2026-10-06', $winner, $loser);
    recordTitleMatchResult($match, $winningSide);

    // Act
    recordTitleMatchResult($match, $winningSide);

    // Assert
    expect(TitleChampionship::query()->where('title_id', $title->id)->sole()->lost_at?->toDateString())->toBe('2026-10-09');
});

test('it treats the title as vacant from the release date for a later title match', function (): void {
    // Arrange
    travelTo(Carbon::parse('2026-10-11 12:00:00'));
    $title = Title::factory()->singles()->active()->create();
    $winner = Wrestler::factory()
        ->has(Employment::factory()->started(Carbon::parse('2026-09-01'))->ended(Carbon::parse('2026-10-09')), 'employments')
        ->create();
    [$loser, $nextWinner] = Wrestler::factory()->bookable()->count(2)->create()->all();
    [$match, $winningSide] = titleMatchBetween($title, '2026-10-06', $winner, $loser);
    recordTitleMatchResult($match, $winningSide);
    [$laterMatch, $laterWinningSide] = titleMatchBetween($title, '2026-10-10', $nextWinner, $loser);

    // Act
    recordTitleMatchResult($laterMatch, $laterWinningSide);

    // Assert
    expect(TitleChampionship::query()->where('title_id', $title->id)->current()->sole()->champion_id)->toBe($nextWinner->id)
        ->and(TitleChampionship::query()->where('won_match_id', $match->id)->sole()->lost_at?->toDateString())->toBe('2026-10-09');
});
