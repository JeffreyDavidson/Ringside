<?php

declare(strict_types=1);

use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Queries\Titles\TitleChampionshipQuery;
use Illuminate\Support\Carbon;
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
function queriesTitlesTitleChampionshipQueryFixtures(): array
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
    ['title' => $title, 'currentChampion' => $currentChampion] = queriesTitlesTitleChampionshipQueryFixtures();

    expect(TitleChampionshipQuery::currentChampion($title)?->is($currentChampion))->toBeTrue();
});

test('uses the eager-loaded current championship', function () {
    ['title' => $titleFixture, 'currentChampion' => $currentChampion] = queriesTitlesTitleChampionshipQueryFixtures();

    $title = Title::query()
        ->with('currentChampionship.champion')
        ->findOrFail($titleFixture->id);

    DB::enableQueryLog();
    DB::flushQueryLog();

    $champion = TitleChampionshipQuery::currentChampion($title);

    expect($champion?->is($currentChampion))->toBeTrue()
        ->and(DB::getQueryLog())->toBeEmpty();
});

test('calculates the length of an ended championship reign', function () {
    ['title' => $title, 'firstChampion' => $firstChampion] = queriesTitlesTitleChampionshipQueryFixtures();

    $championship = TitleChampionship::factory()
        ->for($title)
        ->forWrestler($firstChampion)
        ->wonOn('2025-01-01')
        ->lostOn('2025-01-11')
        ->make();

    expect(TitleChampionshipQuery::reignLengthInDays($championship))->toBe(10);
});

test('calculates the length of a current championship reign', function () {
    ['title' => $title, 'firstChampion' => $firstChampion] = queriesTitlesTitleChampionshipQueryFixtures();

    Carbon::setTestNow('2025-01-11');

    $championship = TitleChampionship::factory()
        ->for($title)
        ->forWrestler($firstChampion)
        ->wonOn('2025-01-01')
        ->current()
        ->make();

    expect(TitleChampionshipQuery::reignLengthInDays($championship))->toBe(10);

    Carbon::setTestNow();
});

test('calculates current reign length from an explicit as-of date', function () {
    ['title' => $title, 'firstChampion' => $firstChampion] = queriesTitlesTitleChampionshipQueryFixtures();

    $championship = TitleChampionship::factory()
        ->for($title)
        ->forWrestler($firstChampion)
        ->wonOn('2025-01-01')
        ->current()
        ->make();

    expect(TitleChampionshipQuery::reignLengthInDays($championship, Carbon::parse('2025-01-11')))->toBe(10);
});

test('returns no champion for a title without reigns', function () {
    queriesTitlesTitleChampionshipQueryFixtures();

    expect(TitleChampionshipQuery::currentChampion(Title::factory()->create()))->toBeNull();
});

test('never reports a negative reign length for a reign dated in the future', function () {
    ['title' => $title, 'firstChampion' => $firstChampion] = queriesTitlesTitleChampionshipQueryFixtures();

    $championship = TitleChampionship::factory()
        ->for($title)
        ->forWrestler($firstChampion)
        ->wonOn(now()->addDays(10)->toDateTimeString())
        ->current()
        ->make();

    expect(TitleChampionshipQuery::reignLengthInDays($championship))->toBe(0);
});
