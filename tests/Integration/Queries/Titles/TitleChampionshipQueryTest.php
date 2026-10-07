<?php

declare(strict_types=1);

use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Queries\Titles\TitleChampionshipQuery;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->title = Title::factory()->create();
    $this->firstChampion = Wrestler::factory()->create();
    $this->previousChampion = Wrestler::factory()->create();
    $this->currentChampion = Wrestler::factory()->create();

    $this->firstChampionship = TitleChampionship::factory()
        ->for($this->title)
        ->forWrestler($this->firstChampion)
        ->wonOn(now()->subYears(3)->toDateString())
        ->lostOn(now()->subYears(2)->toDateString())
        ->create();

    $this->previousChampionship = TitleChampionship::factory()
        ->for($this->title)
        ->forWrestler($this->previousChampion)
        ->wonOn(now()->subYears(2)->toDateString())
        ->lostOn(now()->subMonth()->toDateString())
        ->create();

    $this->currentChampionship = TitleChampionship::factory()
        ->for($this->title)
        ->forWrestler($this->currentChampion)
        ->wonOn(now()->subWeek()->toDateString())
        ->current()
        ->create();
});

test('returns the current champion', function () {
    expect(TitleChampionshipQuery::currentChampion($this->title)?->is($this->currentChampion))->toBeTrue();
});

test('uses the eager-loaded current championship', function () {
    $title = Title::query()
        ->with('currentChampionship.champion')
        ->findOrFail($this->title->id);

    DB::enableQueryLog();
    DB::flushQueryLog();

    $champion = TitleChampionshipQuery::currentChampion($title);

    expect($champion?->is($this->currentChampion))->toBeTrue()
        ->and(DB::getQueryLog())->toBeEmpty();
});

test('calculates the length of an ended championship reign', function () {
    $championship = TitleChampionship::factory()
        ->for($this->title)
        ->forWrestler($this->firstChampion)
        ->wonOn('2025-01-01')
        ->lostOn('2025-01-11')
        ->make();

    expect(TitleChampionshipQuery::reignLengthInDays($championship))->toBe(10);
});

test('calculates the length of a current championship reign', function () {
    Carbon::setTestNow('2025-01-11');

    $championship = TitleChampionship::factory()
        ->for($this->title)
        ->forWrestler($this->firstChampion)
        ->wonOn('2025-01-01')
        ->current()
        ->make();

    expect(TitleChampionshipQuery::reignLengthInDays($championship))->toBe(10);

    Carbon::setTestNow();
});

test('calculates current reign length from an explicit as-of date', function () {
    $championship = TitleChampionship::factory()
        ->for($this->title)
        ->forWrestler($this->firstChampion)
        ->wonOn('2025-01-01')
        ->current()
        ->make();

    expect(TitleChampionshipQuery::reignLengthInDays($championship, Carbon::parse('2025-01-11')))->toBe(10);
});

test('returns no champion for a title without reigns', function () {
    expect(TitleChampionshipQuery::currentChampion(Title::factory()->create()))->toBeNull();
});

test('never reports a negative reign length for a reign dated in the future', function () {
    $championship = TitleChampionship::factory()
        ->for($this->title)
        ->forWrestler($this->firstChampion)
        ->wonOn(now()->addDays(10)->toDateTimeString())
        ->current()
        ->make();

    expect(TitleChampionshipQuery::reignLengthInDays($championship))->toBe(0);
});
