<?php

declare(strict_types=1);

use App\Models\Titles\Title;
use Illuminate\Support\Facades\DB;

test('active titles can be retrieved', function () {
    $activeTitle = Title::factory()->active()->create();
    $futureActivatedTitle = Title::factory()->withFutureActivation()->create();
    $inactiveTitle = Title::factory()->inactive()->create();
    $retiredTitle = Title::factory()->retired()->create();

    $activeTitles = Title::query()->active()->get();

    expect($activeTitles)
        ->toHaveCount(1)
        ->and($activeTitles->contains($activeTitle))->toBeTrue();
});

test('future activated titles can be retrieved', function () {
    $activeTitle = Title::factory()->active()->create();
    $futureActivatedTitle = Title::factory()->withFutureActivation()->create();
    $inactiveTitle = Title::factory()->inactive()->create();
    $retiredTitle = Title::factory()->retired()->create();

    $futureActivatedTitles = Title::query()->withPendingDebut()->get();

    expect($futureActivatedTitles)
        ->toHaveCount(1)
        ->and($futureActivatedTitles->contains($futureActivatedTitle))->toBeTrue();
});

test('inactive titles can be retrieved', function () {
    $activeTitle = Title::factory()->active()->create();
    $futureActivatedTitle = Title::factory()->withFutureActivation()->create();
    $inactiveTitle = Title::factory()->inactive()->create();
    $retiredTitle = Title::factory()->retired()->create();

    $inactiveTitles = Title::query()->inactive()->get();

    expect($inactiveTitles)
        ->toHaveCount(1)
        ->and($inactiveTitles->contains($inactiveTitle))->toBeTrue()
        ->and($inactiveTitles->contains($retiredTitle))->toBeFalse()
        ->and($inactiveTitles->contains($futureActivatedTitle))->toBeFalse();
});

test('retired titles can be retrieved separately', function () {
    $activeTitle = Title::factory()->active()->create();
    $retiredTitle = Title::factory()->retired()->create();

    $retiredTitles = Title::query()->retired()->get();

    expect($retiredTitles)
        ->toHaveCount(1)
        ->and($retiredTitles->contains($retiredTitle))->toBeTrue()
        ->and($retiredTitles->contains($activeTitle))->toBeFalse();
});

test('projected activity status does not query per title', function () {
    Title::factory()->active()->create();
    Title::factory()->retired()->create();

    $titles = Title::query()
        ->withActivityStatusState()
        ->get();

    DB::enableQueryLog();
    DB::flushQueryLog();

    $titles->each(fn (Title $title) => $title->status);

    expect(DB::getQueryLog())->toBeEmpty();
});
