<?php

declare(strict_types=1);

use App\Actions\Matches\ApplyMatchTitleOutcomesAction;
use App\Data\Matches\MatchResultData;
use App\Enums\MatchFinish;
use App\Exceptions\Matches\InvalidMatchOutcomeException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchSide;
use App\Models\Promotions\Promotion;
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

function recordTitleMatchResult(EventMatch $match, MatchSide $winningSide): void
{
    resolve(ApplyMatchTitleOutcomesAction::class)->handle(
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
    travelTo(Carbon::parse('2026-10-04 12:00:00'));
    [$match, $winningSide, $winner, $title] = titleMatchWithWinner($timezone, $eventDate, $availability);

    recordTitleMatchResult($match, $winningSide);

    expect(TitleChampionship::query()
        ->where('title_id', $title->id)
        ->where('champion_id', $winner->id)
        ->where('won_match_id', $match->id)
        ->exists())->toBeTrue();
})->with('availability lost after the event')->with('events from an earlier local day');

test('it rejects a champion who is no longer available when the event is on the current local day', function (string $availability, string $timezone, string $eventDate, string $now = '2026-10-04 12:00:00'): void {
    travelTo($now);
    [$match, $winningSide, , $title] = titleMatchWithWinner($timezone, $eventDate, $availability);

    expect(fn () => recordTitleMatchResult($match, $winningSide))->toThrow(InvalidMatchOutcomeException::class)
        ->and(TitleChampionship::query()->where('title_id', $title->id)->exists())->toBeFalse();
})->with('availability lost after the event')->with('events from the current local day');

test('it still rejects a deleted title for an earlier local day', function (): void {
    travelTo(Carbon::parse('2026-10-04 12:00:00'));
    [$match, $winningSide, , $title] = titleMatchWithWinner('UTC', '2026-10-03 12:00:00', 'available');
    $title->delete();

    expect(fn () => recordTitleMatchResult($match, $winningSide))->toThrow(InvalidMatchOutcomeException::titleDeleted());
});

test('it still rejects a deleted winner for an earlier local day', function (): void {
    travelTo(Carbon::parse('2026-10-04 12:00:00'));
    [$match, $winningSide, $winner] = titleMatchWithWinner('UTC', '2026-10-03 12:00:00', 'available');
    $winner->delete();

    expect(fn () => recordTitleMatchResult($match, $winningSide))->toThrow(InvalidMatchOutcomeException::winnerDeleted());
});
