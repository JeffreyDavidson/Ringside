<?php

declare(strict_types=1);

use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Queries\Titles\CurrentChampionQuery;
use Illuminate\Support\Facades\DB;

/**
 * @return array{
 *     title: Title,
 *     firstChampion: Wrestler,
 *     previousChampion: Wrestler,
 *     currentChampion: Wrestler,
 *     firstChampionship: TitleChampionship,
 *     previousChampionship: TitleChampionship,
 *     currentChampionship: TitleChampionship,
 * }
 */
function queriesTitlesCurrentChampionQueryFixtures(): array
{
    $title = Title::factory()->create();
    $firstChampion = Wrestler::factory()->create();
    $previousChampion = Wrestler::factory()->create();
    $currentChampion = Wrestler::factory()->create();

    $firstChampionship = TitleChampionship::factory()
        ->for($title)
        ->forWrestler($firstChampion)
        ->wonOn(now()->subYears(3)->toDateString())
        ->lostOn(now()->subYears(2)->toDateString())
        ->create();

    $previousChampionship = TitleChampionship::factory()
        ->for($title)
        ->forWrestler($previousChampion)
        ->wonOn(now()->subYears(2)->toDateString())
        ->lostOn(now()->subMonth()->toDateString())
        ->create();

    $currentChampionship = TitleChampionship::factory()
        ->for($title)
        ->forWrestler($currentChampion)
        ->wonOn(now()->subWeek()->toDateString())
        ->current()
        ->create();

    return [
        'title' => $title,
        'firstChampion' => $firstChampion,
        'previousChampion' => $previousChampion,
        'currentChampion' => $currentChampion,
        'firstChampionship' => $firstChampionship,
        'previousChampionship' => $previousChampionship,
        'currentChampionship' => $currentChampionship,
    ];
}

test('returns the current champion', function () {
    ['title' => $title, 'currentChampion' => $currentChampion] = queriesTitlesCurrentChampionQueryFixtures();

    expect(resolve(CurrentChampionQuery::class)->get($title)?->is($currentChampion))->toBeTrue();
});

test('uses the eager-loaded current championship', function () {
    ['title' => $titleFixture, 'currentChampion' => $currentChampion] = queriesTitlesCurrentChampionQueryFixtures();

    $title = Title::query()
        ->with('currentChampionship.champion')
        ->findOrFail($titleFixture->id);

    DB::enableQueryLog();
    DB::flushQueryLog();

    $champion = resolve(CurrentChampionQuery::class)->get($title);

    expect($champion?->is($currentChampion))->toBeTrue()
        ->and(DB::getQueryLog())->toBeEmpty();
});

test('returns no champion for a title without reigns', function () {
    queriesTitlesCurrentChampionQueryFixtures();

    expect(resolve(CurrentChampionQuery::class)->get(Title::factory()->create()))->toBeNull();
});
