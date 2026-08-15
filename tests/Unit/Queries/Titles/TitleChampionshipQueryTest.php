<?php

declare(strict_types=1);

use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use App\Models\Wrestlers\Wrestler;
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

test('returns current and previous championship records and champions', function () {
    expect(TitleChampionshipQuery::currentChampionship($this->title)?->is($this->currentChampionship))->toBeTrue()
        ->and(TitleChampionshipQuery::currentChampion($this->title)?->is($this->currentChampion))->toBeTrue()
        ->and(TitleChampionshipQuery::previousChampionship($this->title)?->is($this->previousChampionship))->toBeTrue()
        ->and(TitleChampionshipQuery::previousChampion($this->title)?->is($this->previousChampion))->toBeTrue();
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

test('returns first and longest championship records and champions', function () {
    expect(TitleChampionshipQuery::firstChampionship($this->title)?->is($this->firstChampionship))->toBeTrue()
        ->and(TitleChampionshipQuery::firstChampion($this->title)?->is($this->firstChampion))->toBeTrue()
        ->and(TitleChampionshipQuery::longestChampionship($this->title)?->is($this->previousChampionship))->toBeTrue()
        ->and(TitleChampionshipQuery::longestChampion($this->title)?->is($this->previousChampion))->toBeTrue();
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

test('counts reigns and reports vacancy from the current relationship', function () {
    expect(TitleChampionshipQuery::reignCount($this->title))->toBe(3)
        ->and(TitleChampionshipQuery::isVacant($this->title))->toBeFalse();

    $this->currentChampionship->update(['lost_at' => now()]);

    expect(TitleChampionshipQuery::isVacant($this->title))->toBeTrue();
});

test('returns null records and champions for a title without reigns', function () {
    $title = Title::factory()->create();

    expect(TitleChampionshipQuery::currentChampionship($title))->toBeNull()
        ->and(TitleChampionshipQuery::currentChampion($title))->toBeNull()
        ->and(TitleChampionshipQuery::previousChampionship($title))->toBeNull()
        ->and(TitleChampionshipQuery::previousChampion($title))->toBeNull()
        ->and(TitleChampionshipQuery::firstChampionship($title))->toBeNull()
        ->and(TitleChampionshipQuery::firstChampion($title))->toBeNull()
        ->and(TitleChampionshipQuery::longestChampionship($title))->toBeNull()
        ->and(TitleChampionshipQuery::longestChampion($title))->toBeNull()
        ->and(TitleChampionshipQuery::reignCount($title))->toBe(0)
        ->and(TitleChampionshipQuery::isVacant($title))->toBeTrue();
});
