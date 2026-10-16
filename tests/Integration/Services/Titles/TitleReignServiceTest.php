<?php

declare(strict_types=1);

use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Services\Titles\TitleReignService;
use Illuminate\Support\Carbon;

/** @return array{title: Title, firstChampion: Wrestler} */
function servicesTitlesTitleReignServiceFixtures(): array
{
    return ['title' => Title::factory()->create(), 'firstChampion' => Wrestler::factory()->create()];
}

test('calculates the length of an ended championship reign', function () {
    ['title' => $title, 'firstChampion' => $firstChampion] = servicesTitlesTitleReignServiceFixtures();

    $championship = TitleChampionship::factory()
        ->for($title)
        ->forWrestler($firstChampion)
        ->wonOn('2025-01-01')
        ->lostOn('2025-01-11')
        ->make();

    expect(resolve(TitleReignService::class)->lengthInDays($championship))->toBe(10);
});

test('calculates the length of a current championship reign', function () {
    ['title' => $title, 'firstChampion' => $firstChampion] = servicesTitlesTitleReignServiceFixtures();

    Carbon::setTestNow('2025-01-11');

    $championship = TitleChampionship::factory()
        ->for($title)
        ->forWrestler($firstChampion)
        ->wonOn('2025-01-01')
        ->current()
        ->make();

    expect(resolve(TitleReignService::class)->lengthInDays($championship))->toBe(10);

    Carbon::setTestNow();
});

test('calculates current reign length from an explicit as-of date', function () {
    ['title' => $title, 'firstChampion' => $firstChampion] = servicesTitlesTitleReignServiceFixtures();

    $championship = TitleChampionship::factory()
        ->for($title)
        ->forWrestler($firstChampion)
        ->wonOn('2025-01-01')
        ->current()
        ->make();

    expect(resolve(TitleReignService::class)->lengthInDays($championship, Carbon::parse('2025-01-11')))->toBe(10);
});

test('never reports a negative reign length for a reign dated in the future', function () {
    ['title' => $title, 'firstChampion' => $firstChampion] = servicesTitlesTitleReignServiceFixtures();

    $championship = TitleChampionship::factory()
        ->for($title)
        ->forWrestler($firstChampion)
        ->wonOn(now()->addDays(10)->toDateTimeString())
        ->current()
        ->make();

    expect(resolve(TitleReignService::class)->lengthInDays($championship))->toBe(0);
});
