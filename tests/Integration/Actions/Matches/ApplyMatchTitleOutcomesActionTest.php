<?php

declare(strict_types=1);

use App\Actions\Matches\ApplyMatchTitleOutcomesAction;
use App\Data\Matches\MatchResultData;
use App\Enums\MatchFinish;
use App\Exceptions\Matches\InvalidMatchOutcomeException;
use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchSide;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Support\Collection;

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
    $title = Title::factory()->singles()->create();
    $match->titles()->attach($title);
    $winningSide = MatchSide::factory()->for($match, 'match')->create(['position' => 1]);
    MatchSide::factory()->for($match, 'match')->create(['position' => 2]);
    $firstWinner = Wrestler::factory()->create();
    $secondWinner = Wrestler::factory()->create();
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
    $title = Title::factory()->singles()->create();
    $match->titles()->attach($title);
    $champion = Wrestler::factory()->create();
    $challenger = Wrestler::factory()->create();
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
    $title = Title::factory()->singles()->create();
    $match->titles()->attach($title);
    $champion = Wrestler::factory()->create();
    $challenger = Wrestler::factory()->create();
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
